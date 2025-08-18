<?php

namespace App\Http\Controllers;

use App\Models\AhpCalculation;
use App\Models\Campus;
use App\Models\Criteria;
use App\Services\AhpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AhpController extends Controller
{
    protected $ahpService;

    public function __construct(AhpService $ahpService)
    {
        $this->ahpService = $ahpService;
    }

    public function showPairwise(Request $request)
    {
        $campusId = $request->input('campus_id');
        $campus = Campus::findOrFail($campusId);
        $criteria = Criteria::orderBy('order')->get();
        
        // Generate pairwise comparison questions
        $questions = $this->generatePairwiseQuestions($criteria);
        
        return view('pairwise', compact('campus', 'criteria', 'questions'));
        // return response()->json([
        //     'campus' => $campus,
        //     'criteria' => $criteria,
        //     'questions' => $questions,
        // ]);

    }

    public function processPairwise(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'pairwise' => 'required|array|size:15',
            'pairwise.*' => 'required|numeric|min:0.111|max:9'
        ]);

        $campusId = $request->input('campus_id');
        $pairwiseValues = array_values($request->input('pairwise'));

        // Build pairwise matrix and calculate weights
        $matrix = $this->ahpService->buildPairwiseMatrix($pairwiseValues);
        $weights = $this->ahpService->calculateWeights($matrix);
        $consistencyRatio = $this->ahpService->calculateConsistencyRatio($matrix, $weights);

        // Check consistency ratio
        if ($consistencyRatio > 0.10) {
            return redirect()->back()
                           ->withErrors(['consistency' => 'Consistency Ratio (' . round($consistencyRatio, 4) . ') melebihi 0.10. Silakan periksa kembali perbandingan Anda.'])
                           ->withInput();
        }

        return $this->calculateAndDisplayResults($campusId, $weights, $consistencyRatio, 'manual', $pairwiseValues);
    }

    public function processSystem(Request $request)
    {
        $request->validate(['campus_id' => 'required|exists:campuses,id']);
        $campusId = $request->input('campus_id');

        $res = $this->ahpService->getSystemRecommendedWeightsWithCR($campusId);
        $weights = $res['weights'];
        $consistencyRatio = $res['cr'];

        return $this->calculateAndDisplayResults($campusId, $weights, $consistencyRatio, 'system');
    }

    
    private function calculateAndDisplayResults($campusId, $weights, $consistencyRatio, $method, $pairwiseValues = null)
    {
        $userId = Auth::id();

        // Jika manual -> pass bobot yang sudah dihitung user ke orchestrator.
        // Jika system -> biarkan orchestrator mengambil bobot sistem sendiri (agar tidak mengubah perhitungan sistem).
        if ($method === 'manual' && is_array($weights)) {
            $result = $this->ahpService->runFullAhpForCampus($userId, $campusId, $weights);
        } else {
            // system: jangan pass $weights, biarkan service memanggil getSystemRecommendedWeights()
            $result = $this->ahpService->runFullAhpForCampus($userId, $campusId);
        }

        $campus = Campus::findOrFail($campusId);
        $criteria = Criteria::orderBy('order')->get();

        // Pilih bobot yang ditampilkan: jika caller memberikan $weights (manual), pakai itu,
        // kalau tidak, gunakan bobot yang dihitung service (untuk sistem).
        $displayWeights = is_array($weights) && !empty($weights) ? $weights : ($result['criteria_weights'] ?? []);

        // Siapkan ranking untuk ditampilkan
        $ranking = [];
        $rank = 1;
        foreach ($result['scores'] as $kosId => $data) {
            $ranking[] = [
                'rank' => $rank++,
                'kos' => $data['kos'],
                'score' => $data['total_score'],
                'criteria_scores' => $data['criteria_scores']
            ];
        }

        return view('results', [
            'campus' => $campus,
            'criteria' => $criteria,
            'ranking' => $ranking,
            'weights' => $displayWeights,  // bobot yang benar untuk ditampilkan
            'consistencyRatio' => $consistencyRatio,
            'method' => $method
        ]);
    }



    private function generatePairwiseQuestions($criteria)
    {
        $questions = [];
        $criteriaArray = $criteria->toArray();
        
        for ($i = 0; $i < count($criteriaArray); $i++) {
            for ($j = $i + 1; $j < count($criteriaArray); $j++) {
                $questions[] = [
                    'text' => "Seberapa penting {$criteriaArray[$i]['name']} dibandingkan {$criteriaArray[$j]['name']}?",
                    'criteria1' => $criteriaArray[$i]['name'],
                    'criteria2' => $criteriaArray[$j]['name']
                ];
            }
        }
        
        return $questions;
    }
}