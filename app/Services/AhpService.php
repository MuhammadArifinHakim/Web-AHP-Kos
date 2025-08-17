<?php

namespace App\Services;

use App\Models\BoardingHouse;
use App\Models\Campus;
use App\Models\Criteria;
use App\Models\QuestionnaireResponse;
use App\Models\AhpCalculation; // pastikan model ini ada, kalau tidak ganti dengan DB::table(...)
use Illuminate\Support\Facades\DB;

class AhpService
{
    public function calculateWeights(array $pairwiseMatrix)
    {
        $n = count($pairwiseMatrix);
        
        // Normalize the matrix (column-wise)
        $normalizedMatrix = array_fill(0, $n, array_fill(0, $n, 0.0));
        for ($i = 0; $i < $n; $i++) {
            $columnSum = 0;
            for ($j = 0; $j < $n; $j++) {
                $columnSum += $pairwiseMatrix[$j][$i];
            }
            
            if ($columnSum == 0) $columnSum = 1e-12; // guard
            
            for ($j = 0; $j < $n; $j++) {
                $normalizedMatrix[$j][$i] = $pairwiseMatrix[$j][$i] / $columnSum;
            }
        }
        
        // Calculate weights (row averages)
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $rowSum = 0;
            for ($j = 0; $j < $n; $j++) {
                $rowSum += $normalizedMatrix[$i][$j];
            }
            $weights[$i] = $rowSum / $n;
        }
        
        return $weights;
    }

    public function calculateConsistencyRatio(array $pairwiseMatrix, array $weights)
    {
        $n = count($pairwiseMatrix);
        
        if ($n == 0) return 0;

        // Calculate lambda max
        $weightedSum = [];
        for ($i = 0; $i < $n; $i++) {
            $sum = 0;
            for ($j = 0; $j < $n; $j++) {
                $sum += $pairwiseMatrix[$i][$j] * $weights[$j];
            }
            $weightedSum[$i] = $sum;
        }
        
        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            // guard against division by zero
            if (($weights[$i] ?? 0) == 0) continue;
            $lambdaMax += $weightedSum[$i] / $weights[$i];
        }
        $lambdaMax /= $n;
        
        // Calculate CI and CR
        $CI = ($lambdaMax - $n) / ($n - 1);
        
        // Random Index values for n=1..10 (index n-1)
        $RI = [0, 0, 0.58, 0.90, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49];

        if ($n <= 2) {
            return 0.0;
        }
        
        $riValue = $RI[$n-1] ?? 1.49;
        if ($riValue == 0) return 0.0;
        
        $CR = $CI / $riValue;
        
        return $CR;
    }

    public function buildPairwiseMatrix(array $values)
    {
        // Build NxN matrix from pairwise comparison values for 6 criteria (15 values)
        // This function assumes N=6. If you change criteria count you must update accordingly.
        $n = 6;
        $matrix = array_fill(0, $n, array_fill(0, $n, 0.0));
        for ($i = 0; $i < $n; $i++) {
            $matrix[$i][$i] = 1.0;
        }

        $index = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $val = $values[$index] ?? 1.0;
                if ($val == 0) $val = 1.0;
                $matrix[$i][$j] = $val;
                $matrix[$j][$i] = 1 / $val;
                $index++;
            }
        }

        return $matrix;
    }

    // --- getSystemRecommendedWeights: returns array of weights (same as before) ---
    public function getSystemRecommendedWeights($campusId)
    {
        $responses = QuestionnaireResponse::where('campus_id', $campusId)->get();
        
        if ($responses->isEmpty()) {
            // Default weights if no data available (sum should be ~1)
            return [0.2, 0.25, 0.15, 0.15, 0.15, 0.1];
        }

        $avgPairwise = [];
        $totalResponses = $responses->count();
        
        // there are 15 upper-triangle values for 6x6
        for ($i = 0; $i < 15; $i++) {
            $sum = 0;
            foreach ($responses as $response) {
                // karena kita sudah cast pairwise_values => array di model,
                // biasanya ini sudah array. tetap beri fallback aman.
                $raw = $response->pairwise_values ?? [];

                if (is_array($raw)) {
                    $vals = $raw;
                } elseif (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    $vals = is_array($decoded) ? $decoded : [];
                } else {
                    $vals = [];
                }

                $sum += $vals[$i] ?? 1;
            }
            $avgPairwise[$i] = $sum / $totalResponses;
        }

        $matrix = $this->buildPairwiseMatrix($avgPairwise);
        return $this->calculateWeights($matrix);
    }



    // ========== Scoring per boarding house (existing) but extended ==========
    public function calculateBoardingHouseScores($campusId, array $criteriaWeights, $alternativeWeights = null)
    {
        $campus = Campus::find($campusId);
        $boardingHouses = $campus->boardingHouses;
        $criteria = Criteria::orderBy('order')->get();
        
        // --- 1) Build code => weight mapping (guard terhadap urutan)
        $codeToWeight = [];
        foreach ($criteria as $idx => $c) {
            $codeToWeight[$c->code] = $criteriaWeights[$idx] ?? 0.0;
        }

        // --- 2) Normalize alternativeWeights keys (if diberikan) -> ensure int keys
        if (!empty($alternativeWeights) && is_array($alternativeWeights)) {
            foreach ($alternativeWeights as $critCode => $map) {
                if (!is_array($map)) continue;
                $normalized = [];
                foreach ($map as $k => $v) {
                    // cast numeric keys to int, keep non-numeric as-is (safety)
                    if (is_numeric($k)) {
                        $normalized[intval($k)] = floatval($v);
                    } else {
                        $normalized[$k] = floatval($v);
                    }
                }
                $alternativeWeights[$critCode] = $normalized;
            }
        }

        $scores = [];
        $criteriaScores = [];

        foreach ($boardingHouses as $kos) {
            $totalScore = 0;
            $kosScores = [];

            foreach ($criteria as $index => $criterion) {
                $critCode = $criterion->code;

                // --- 3) Ambil score alternatif (prioritas): coba beberapa varian key
                $score = null;
                if ($alternativeWeights && isset($alternativeWeights[$critCode])) {
                    $map = $alternativeWeights[$critCode];
                    if (array_key_exists($kos->id, $map)) {
                        $score = $map[$kos->id];
                    } elseif (array_key_exists((string)$kos->id, $map)) {
                        $score = $map[(string)$kos->id];
                    }
                    // else tetap null -> fallback
                }

                if ($score === null) {
                    // fallback ke perhitungan lama (normalisasi data)
                    $score = $this->calculateCriteriaScore($kos, $criterion, $campusId, $boardingHouses);
                }

                // --- 4) Gunakan bobot berdasarkan code (bukan index), lebih robust
                $critWeight = $codeToWeight[$critCode] ?? ($criteriaWeights[$index] ?? 0.0);

                $weightedScore = $score * $critWeight;
                $totalScore += $weightedScore;

                $kosScores[$critCode] = [
                    'raw_score' => $score,
                    'weighted_score' => $weightedScore
                ];
            }

            $scores[$kos->id] = [
                'total_score' => $totalScore,
                'criteria_scores' => $kosScores,
                'kos' => $kos
            ];

            $criteriaScores[$kos->id] = $kosScores;
        }

        // Sort descending by total score
        uasort($scores, function ($a, $b) {
            return $b['total_score'] <=> $a['total_score'];
        });

        return [
            'scores' => $scores,
            'criteria_scores' => $criteriaScores
        ];
    }

    private function calculateCriteriaScore(BoardingHouse $kos, Criteria $criterion, $campusId, $allKos)
    {
        switch ($criterion->code) {
            case 'C1': // Lokasi (Jarak)
                $distance = $kos->getDistanceToCampus($campusId);
                return $this->normalizeDistance($distance, $allKos, $campusId);

            case 'C2': // Fasilitas Kamar & Bangunan  
                return $this->calculateFacilityScore($kos, $criterion);

            case 'C3': // Harga/Biaya Sewa  
                return $this->normalizePrice($kos->price, $allKos);

            case 'C4': // Fasilitas Lingkungan
                return $this->calculateEnvironmentScore($kos, $criterion, $allKos);

            case 'C5': // Keamanan & Privasi
                return $this->calculateSecurityScore($kos, $criterion);

            case 'C6': // Peraturan Kos
                return $this->calculateRulesScore($kos, $criterion);

            default:
                return 0;
        }
    }


    private function normalizeDistance($distance, $allKos, $campusId)
    {
        $distances = [];
        foreach ($allKos as $kos) {
            $distances[] = $kos->getDistanceToCampus($campusId);
        }
        
        $min = min($distances);
        $max = max($distances);
        
        if ($max == $min) return 1;
        
        // Inverse normalization (closer is better)
        return ($max - $distance) / ($max - $min);
    }

    private function normalizePrice($price, $allKos)
    {
        $prices = $allKos->pluck('price')->toArray();
        $min = min($prices);
        $max = max($prices);
        
        if ($max == $min) return 1;
        
        // Inverse normalization (cheaper is better)
        return ($max - $price) / ($max - $min);
    }

    private function calculateFacilityScore(BoardingHouse $kos, Criteria $criterion)
    {
        $values = $kos->criteriaValues()
                      ->where('criteria_id', $criterion->id)
                      ->first()?->values ?? [];
        
        if (empty($values)) return 0;
        
        // assume $values is array of 12 items with 1/0
        $total = array_sum($values);
        return $total / 12; // 12 facilities
    }

    private function calculateEnvironmentScore(BoardingHouse $kos, Criteria $criterion, $allKos)
    {
        $values = $kos->criteriaValues()
                      ->where('criteria_id', $criterion->id)
                      ->first()?->values ?? [];
        
        if (empty($values)) return 0;

        // Get all environment distances for normalization
        $allValues = [];
        foreach ($allKos as $k) {
            $kosValues = $k->criteriaValues()
                           ->where('criteria_id', $criterion->id)
                           ->first()?->values ?? [];
            if (!empty($kosValues)) {
                foreach ($kosValues as $key => $value) {
                    $allValues[$key][] = $value;
                }
            }
        }

        $normalizedSum = 0;
        $count = 0;
        
        foreach ($values as $key => $distance) {
            if (isset($allValues[$key])) {
                $min = min($allValues[$key]);
                $max = max($allValues[$key]);
                
                if ($max != $min) {
                    // Inverse normalization (closer is better)
                    $normalized = ($max - $distance) / ($max - $min);
                } else {
                    $normalized = 1;
                }
                
                $normalizedSum += $normalized;
                $count++;
            }
        }
        
        return $count > 0 ? $normalizedSum / $count : 0;
    }

    private function calculateSecurityScore(BoardingHouse $kos, Criteria $criterion)
    {
        $values = $kos->criteriaValues()
                      ->where('criteria_id', $criterion->id)
                      ->first()?->values ?? [];
        
        if (empty($values)) return 0;
        
        $total = array_sum($values);
        return $total / 3; // 3 security features
    }

    // Improved calculateRulesScore that handles mapped JSON and fallback raw text/numeric
    private function calculateRulesScore(BoardingHouse $kos, Criteria $criterion)
    {
        $raw = $kos->criteriaValues()
                   ->where('criteria_id', $criterion->id)
                   ->first()?->values ?? [];

        if (empty($raw)) return 0;

        // If values is stored as associative (as you showed), use keys
        $jam_malam = $raw['jam_malam'] ?? null;
        $membawa_teman = $raw['membawa_teman'] ?? null;
        $ketentuan_bayar = $raw['ketentuan_bayar'] ?? null;
        $tamu_menginap = $raw['tamu_menginap'] ?? null;
        $hewan_peliharaan = $raw['hewan_peliharaan'] ?? null;

        // If values are stored as indexed array, attempt to map them
        if ($jam_malam === null && isset($raw[0])) $jam_malam = $raw[0];
        if ($membawa_teman === null && isset($raw[1])) $membawa_teman = $raw[1];
        if ($ketentuan_bayar === null && isset($raw[2])) $ketentuan_bayar = $raw[2];
        if ($tamu_menginap === null && isset($raw[3])) $tamu_menginap = $raw[3];
        if ($hewan_peliharaan === null && isset($raw[4])) $hewan_peliharaan = $raw[4];

        // jam malam: data already mapped? you said stored as 0/1 in your example
        $vCurfew = is_numeric($jam_malam) ? floatval($jam_malam) : (strtolower($jam_malam ?? '') === 'ya' ? 0.0 : 1.0);

        // membawa teman: Ya=1, Tidak=0
        $vBring = is_numeric($membawa_teman) ? floatval($membawa_teman) : (strtolower($membawa_teman ?? '') === 'ya' ? 1.0 : 0.0);

        // payment mapping: if numeric already (e.g. 0.75), use it; if stored as period like '3' => map
        $vPayment = 0.0;
        if (is_numeric($ketentuan_bayar)) {
            // If it's already mapped as 0.75 etc.
            $vPayment = floatval($ketentuan_bayar);
            // But if it's an integer like 1,3,6,12 map accordingly (1->1,3->0.75,...)
            if (in_array(intval($ketentuan_bayar), [1,3,6,12])) {
                switch (intval($ketentuan_bayar)) {
                    case 1: $vPayment = 1.0; break;
                    case 3: $vPayment = 0.75; break;
                    case 6: $vPayment = 0.5; break;
                    case 12:$vPayment = 0.25; break;
                }
            }
        } else {
            // maybe string like '1', '3' or '6'
            $kp = intval($ketentuan_bayar);
            switch ($kp) {
                case 1: $vPayment = 1.0; break;
                case 3: $vPayment = 0.75; break;
                case 6: $vPayment = 0.5; break;
                case 12:$vPayment = 0.25; break;
                default: $vPayment = 0.0;
            }
        }

        // tamu menginap: Ya=1, Tidak=0
        $vGuest = is_numeric($tamu_menginap) ? floatval($tamu_menginap) : (strtolower($tamu_menginap ?? '') === 'ya' ? 1.0 : 0.0);

        // hewan peliharaan: Ya=1, Tidak=0
        $vPets = is_numeric($hewan_peliharaan) ? floatval($hewan_peliharaan) : (strtolower($hewan_peliharaan ?? '') === 'ya' ? 1.0 : 0.0);

        $total = ($vCurfew ?? 0) + ($vBring ?? 0) + ($vPayment ?? 0) + ($vGuest ?? 0) + ($vPets ?? 0);
        return $total / 5.0;
    }

    // ========== NEW: build pairwise alternative matrix from normalized scores ==========
    private function buildAlternativePairwiseFromScores(array $scoresById)
    {
        // $scoresById: [boardingHouseId => normalizedScore, ...]
        $ids = array_keys($scoresById);
        $n = count($ids);
        $matrix = array_fill(0, $n, array_fill(0, $n, 1.0));

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $si = $scoresById[$ids[$i]] ?? 0.0;
                $sj = $scoresById[$ids[$j]] ?? 0.0;

                // if both zero -> equal
                if ($si == 0 && $sj == 0) {
                    $aij = 1.0;
                } elseif ($sj == 0) {
                    $aij = 9.0;
                } else {
                    $ratio = $si / $sj;
                    $aij = min(9.0, max(1.0/9.0, $ratio));
                }

                $matrix[$i][$j] = $aij;
                $matrix[$j][$i] = 1.0 / $aij;
            }
        }

        return ['matrix' => $matrix, 'ids' => $ids];
    }

    // ========== NEW: compute alternative weights per criterion (AHP) ==========
    public function computeAlternativeWeightsPerCriterion($campusId, array $criteriaWeights)
    {
        $campus = Campus::find($campusId);
        $allKos = $campus->boardingHouses;
        $criteria = Criteria::orderBy('order')->get();

        $alternativeWeights = []; // code => [kosId => weight]
        $alternativesConsistency = []; // code => CR

        foreach ($criteria as $criterion) {
            // collect normalized scores per kos for this criterion
            $scores = [];
            foreach ($allKos as $kos) {
                $score = $this->calculateCriteriaScore($kos, $criterion, $campusId, $allKos);
                $scores[$kos->id] = max(0.0, (float)$score);
            }

            // build pairwise matrix
            $pairRes = $this->buildAlternativePairwiseFromScores($scores);
            $matrix = $pairRes['matrix'];
            $ids = $pairRes['ids'];

            // calculate weights (index-based)
            $weightsVector = $this->calculateWeights($matrix);

            // map back to kos ids
            $mappedWeights = [];
            foreach ($ids as $idx => $kosId) {
                $mappedWeights[$kosId] = $weightsVector[$idx] ?? 0;
            }

            // calculate CR
            $CR = $this->calculateConsistencyRatio($matrix, $weightsVector);

            $alternativeWeights[$criterion->code] = $mappedWeights;
            $alternativesConsistency[$criterion->code] = $CR;
        }

        return ['alternative_weights' => $alternativeWeights, 'alternatives_consistency' => $alternativesConsistency];
    }

    // ========== NEW: Save results to ahp_calculations ==========
    public function saveAlternativeWeightsToCalculation($userId, $campusId, $criteriaWeights, $alternativeWeights, $alternativesConsistency, $otherFields = [])
    {
        $payload = [
            'user_id' => $userId,
            'campus_id' => $campusId,
            'criteria_weights' => json_encode($criteriaWeights),
            'boarding_house_scores' => $otherFields['boarding_house_scores'] ?? json_encode([]),
            'ranking' => $otherFields['ranking'] ?? json_encode([]),
            // store alternative weights and per-criterion CR as JSON
            'alternative_weights' => json_encode($alternativeWeights),
            'alternatives_consistency' => json_encode($alternativesConsistency),
            // overall consistency_ratio: choose max CR among criteria (or average)
            'consistency_ratio' => $otherFields['consistency_ratio'] ?? (count($alternativesConsistency) ? max($alternativesConsistency) : 0),
            'weight_method' => $otherFields['weight_method'] ?? 'ahp_with_alternative_pairwise'
        ];

        // If AhpCalculation model exists:
        if (class_exists(AhpCalculation::class)) {
            AhpCalculation::create($payload);
        } else {
            // fallback raw insert
            DB::table('ahp_calculations')->insert(array_merge($payload, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }

    // ========== NEW: Orchestrator ==========
    public function runFullAhpForCampus($userId, $campusId)
    {
        // 1. Ambil bobot kriteria
        $criteriaWeights = $this->getSystemRecommendedWeights($campusId);

        // 2. Hitung bobot alternatif per kriteria (pakai helper yang sudah ada)
        $altRes = $this->computeAlternativeWeightsPerCriterion($campusId, $criteriaWeights);
        $alternativeWeights = $altRes['alternative_weights'];
        $alternativesConsistency = $altRes['alternatives_consistency'];

        // 3. Hitung skor total dengan alternative weights
        $results = $this->calculateBoardingHouseScores($campusId, $criteriaWeights, $alternativeWeights);

        // 4. Siapkan ranking
        $ranking = [];
        $rank = 1;
        foreach ($results['scores'] as $kosId => $data) {
            $ranking[] = [
                'rank' => $rank++,
                'kos' => $data['kos'],
                'score' => $data['total_score'],
                'criteria_scores' => $data['criteria_scores']
            ];
        }

        // 5. Simpan ke DB
        \App\Models\AhpCalculation::create([
            'user_id' => $userId,
            'campus_id' => $campusId,
            'criteria_weights' => $criteriaWeights,
            'boarding_house_scores' => $results['criteria_scores'],
            'ranking' => $ranking,
            'consistency_ratio' => count($alternativesConsistency) ? max($alternativesConsistency) : 0,
            'weight_method' => 'ahp_with_alternative_pairwise',
            'alternative_weights' => $alternativeWeights,
            'alternatives_consistency' => $alternativesConsistency
        ]);

        return [
            'criteria_weights' => $criteriaWeights,
            'alternative_weights' => $alternativeWeights,
            'alternatives_consistency' => $alternativesConsistency,
            'scores' => $results['scores'],
            'criteria_scores' => $results['criteria_scores'],
            'ranking' => $ranking
        ];
    }
}
