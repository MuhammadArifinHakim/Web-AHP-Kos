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
        $request->validate([
            'campus_id' => 'required|exists:campuses,id'
        ]);

        $campusId = $request->input('campus_id');
        
        // Get system recommended weights
        $weights = $this->ahpService->getSystemRecommendedWeights($campusId);
        $consistencyRatio = 0; // System weights are assumed consistent

        return $this->calculateAndDisplayResults($campusId, $weights, $consistencyRatio, 'system');
    }

    // private function calculateAndDisplayResults($campusId, $weights, $consistencyRatio, $method, $pairwiseValues = null)
    // {
    //     // Calculate boarding house scores
    //     $results = $this->ahpService->calculateBoardingHouseScores($campusId, $weights);
        
    //     $campus = Campus::findOrFail($campusId);
    //     $criteria = Criteria::orderBy('order')->get();

    //     // Prepare ranking data
    //     $ranking = [];
    //     $rank = 1;
    //     foreach ($results['scores'] as $kosId => $data) {
    //         $ranking[] = [
    //             'rank' => $rank++,
    //             'kos' => $data['kos'],
    //             'score' => $data['total_score'],
    //             'criteria_scores' => $data['criteria_scores']
    //         ];
    //     }

    //     // Save calculation to database
    //     $calculation = AhpCalculation::create([
    //         'user_id' => Auth::id(),
    //         'campus_id' => $campusId,
    //         'criteria_weights' => $weights,
    //         'boarding_house_scores' => $results['criteria_scores'],
    //         'ranking' => $ranking,
    //         'consistency_ratio' => $consistencyRatio,
    //         'weight_method' => $method
    //     ]);

    //     return view('results', compact('campus', 'criteria', 'ranking', 'weights', 'consistencyRatio', 'method'));
    // }
    private function calculateAndDisplayResults($campusId, $weights, $consistencyRatio, $method, $pairwiseValues = null)
    {
        $userId = Auth::id();

        // gunakan orchestrator penuh (sudah menghitung alt weights & menyimpan ke DB)
        $result = $this->ahpService->runFullAhpForCampus($userId, $campusId);

        $campus = Campus::findOrFail($campusId);
        $criteria = Criteria::orderBy('order')->get();

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

        // Tidak perlu lagi create AhpCalculation manual, sudah dilakukan di runFullAhpForCampus()

        return view('results', [
            'campus' => $campus,
            'criteria' => $criteria,
            'ranking' => $ranking,
            'weights' => $weights,  // bobot kriteria (untuk ditampilkan di view)
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