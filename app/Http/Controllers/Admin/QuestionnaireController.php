<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\QuestionnaireResponse;
use Illuminate\Http\Request;
use App\Services\AhpService;

class QuestionnaireController extends Controller
{
    protected $ahpService;

    public function __construct(AhpService $ahpService)
    {
        $this->middleware('admin');
        $this->ahpService = $ahpService;
    }

    public function index()
    {
        $campuses = Campus::all();
        $questions = [
            "Seberapa penting Lokasi dibandingkan Fasilitas Kamar & Bangunan?",
            "Lokasi vs Harga/Biaya Sewa?",
            "Lokasi vs Fasilitas Lingkungan?",
            "Lokasi vs Keamanan & Privasi?",
            "Lokasi vs Peraturan Kos?",
            "Fasilitas Kamar & Bangunan vs Harga/Biaya Sewa?",
            "Fasilitas Kamar & Bangunan vs Fasilitas Lingkungan?",
            "Fasilitas Kamar & Bangunan vs Keamanan & Privasi?",
            "Fasilitas Kamar & Bangunan vs Peraturan Kos?",
            "Harga/Biaya Sewa vs Fasilitas Lingkungan?",
            "Harga/Biaya Sewa vs Keamanan & Privasi?",
            "Harga/Biaya Sewa vs Peraturan Kos?",
            "Fasilitas Lingkungan vs Keamanan & Privasi?",
            "Fasilitas Lingkungan vs Peraturan Kos?",
            "Keamanan & Privasi vs Peraturan Kos?",
        ];

        return view('admin.questionnaire.index', compact('campuses', 'questions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'input_type' => 'required|in:manual',
            'pairwise' => 'required|array|size:15',
            'pairwise.*' => 'required|integer|min:1|max:9',
        ]);

        $pairwiseValues = array_values($request->pairwise);

        // Bangun matriks dan hitung bobot
        $matrix = $this->ahpService->buildPairwiseMatrix($pairwiseValues);
        $weights = $this->ahpService->calculateWeights($matrix);
        $consistencyRatio = $this->ahpService->calculateConsistencyRatio($matrix, $weights);

        QuestionnaireResponse::create([
            'campus_id' => $request->campus_id,
            'pairwise_values' => $pairwiseValues,
            'consistency_ratio' => $consistencyRatio, // tambahkan CR
            'source' => 'manual',
        ]);

        return redirect()->route('admin.questionnaire.index')
                         ->with('success', 'Data kuesioner berhasil ditambahkan.');
    }

    public function destroy(QuestionnaireResponse $questionnaireResponse)
    {
        $questionnaireResponse->delete();
        return redirect()->route('admin.questionnaire.index')
                         ->with('success', 'Data kuesioner berhasil dihapus.');
    }
}
