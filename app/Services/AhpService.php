<?php

namespace App\Services;

use App\Models\BoardingHouse;
use App\Models\Campus;
use App\Models\Criteria;
use App\Models\QuestionnaireResponse;

class AhpService
{
    public function calculateWeights(array $pairwiseMatrix)
    {
        $n = count($pairwiseMatrix);
        
        // Normalize the matrix
        $normalizedMatrix = [];
        for ($i = 0; $i < $n; $i++) {
            $columnSum = 0;
            for ($j = 0; $j < $n; $j++) {
                $columnSum += $pairwiseMatrix[$j][$i];
            }
            
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
            $lambdaMax += $weightedSum[$i] / $weights[$i];
        }
        $lambdaMax /= $n;
        
        // Calculate CI and CR
        $CI = ($lambdaMax - $n) / ($n - 1);
        
        // Random Index values for n=1 to 10
        $RI = [0, 0, 0.58, 0.90, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49];
        
        if ($n <= 2 || $RI[$n-1] == 0) {
            return 0;
        }
        
        $CR = $CI / $RI[$n-1];
        
        return $CR;
    }

    public function buildPairwiseMatrix(array $values)
    {
        // Build 6x6 matrix from 15 pairwise comparison values
        $matrix = [
            [1, 0, 0, 0, 0, 0],
            [0, 1, 0, 0, 0, 0],
            [0, 0, 1, 0, 0, 0],
            [0, 0, 0, 1, 0, 0],
            [0, 0, 0, 0, 1, 0],
            [0, 0, 0, 0, 0, 1]
        ];

        $index = 0;
        for ($i = 0; $i < 6; $i++) {
            for ($j = $i + 1; $j < 6; $j++) {
                $matrix[$i][$j] = $values[$index];
                $matrix[$j][$i] = 1 / $values[$index];
                $index++;
            }
        }

        return $matrix;
    }

    public function getSystemRecommendedWeights($campusId)
    {
        $responses = QuestionnaireResponse::where('campus_id', $campusId)->get();
        
        if ($responses->isEmpty()) {
            // Default weights if no data available
            return [0.2, 0.25, 0.15, 0.15, 0.15, 0.1];
        }

        // Calculate average pairwise values
        $avgPairwise = [];
        $totalResponses = $responses->count();
        
        for ($i = 0; $i < 15; $i++) {
            $sum = 0;
            foreach ($responses as $response) {
                $sum += $response->pairwise_values[$i] ?? 1;
            }
            $avgPairwise[$i] = $sum / $totalResponses;
        }

        $matrix = $this->buildPairwiseMatrix($avgPairwise);
        return $this->calculateWeights($matrix);
    }

    public function calculateBoardingHouseScores($campusId, array $criteriaWeights)
    {
        $campus = Campus::find($campusId);
        $boardingHouses = $campus->boardingHouses;
        $criteria = Criteria::orderBy('order')->get();
        
        $scores = [];
        $criteriaScores = [];

        foreach ($boardingHouses as $kos) {
            $totalScore = 0;
            $kosScores = [];

            foreach ($criteria as $index => $criterion) {
                $score = $this->calculateCriteriaScore($kos, $criterion, $campusId, $boardingHouses);
                $weightedScore = $score * $criteriaWeights[$index];
                $totalScore += $weightedScore;
                
                $kosScores[$criterion->code] = [
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

        // Sort by total score descending
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

            case 'C2': // Harga
                return $this->normalizePrice($kos->price, $allKos);

            case 'C3': // Fasilitas Kamar & Bangunan
                return $this->calculateFacilityScore($kos, $criterion);

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

    private function calculateRulesScore(BoardingHouse $kos, Criteria $criterion)
    {
        $values = $kos->criteriaValues()
                      ->where('criteria_id', $criterion->id)
                      ->first()?->values ?? [];
        
        if (empty($values)) return 0;
        
        $total = array_sum($values);
        return $total / 5; // 5 rule items
    }
}