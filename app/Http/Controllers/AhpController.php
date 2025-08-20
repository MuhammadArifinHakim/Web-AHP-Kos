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

        // buat meta yang akan diteruskan ke view
        $meta = [
            'respondent_count' => $res['count'] ?? 0,
            'respondent_ids' => $res['respondent_ids'] ?? []
        ];

        return $this->calculateAndDisplayResults($campusId, $weights, $consistencyRatio, 'system', null, $meta);
    }

    
    private function calculateAndDisplayResults($campusId, $weights, $consistencyRatio, $method, $pairwiseValues = null, $meta = [])
    {
        $userId = Auth::id();

        if ($method === 'manual' && is_array($weights)) {
            $result = $this->ahpService->runFullAhpForCampus($userId, $campusId, $weights);
        } else {
            $result = $this->ahpService->runFullAhpForCampus($userId, $campusId);
        }

        $campus = Campus::findOrFail($campusId);
        $criteria = Criteria::orderBy('order')->get();

        $displayWeights = is_array($weights) && !empty($weights) ? $weights : ($result['criteria_weights'] ?? []);

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

        // ambil meta yang relevan
        $respondentCount = $meta['respondent_count'] ?? null;
        $respondentIds = $meta['respondent_ids'] ?? [];

        return view('results', [
            'campus' => $campus,
            'criteria' => $criteria,
            'ranking' => $ranking,
            'weights' => $displayWeights,
            'consistencyRatio' => $consistencyRatio,
            'method' => $method,
            // kirim meta ke view
            'respondentCount' => $respondentCount,
            'respondentIds' => $respondentIds
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