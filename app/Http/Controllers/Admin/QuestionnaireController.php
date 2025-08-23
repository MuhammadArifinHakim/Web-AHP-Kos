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

    public function create()
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

        return view('admin.questionnaire.create', compact('campuses', 'questions'));
    }

    public function index(Request $request)
    {
        $query = \App\Models\QuestionnaireResponse::with('campus')->latest();

        if ($q = $request->get('q')) {
            $query->where(function($qq) use ($q) {
                $qq->where('id', $q)
                ->orWhere('source', 'like', "%{$q}%");
            });
        }

        if ($campusId = $request->get('campus_id')) {
            $query->where('campus_id', $campusId);
        }

        if ($source = $request->get('source')) {
            $query->where('source', $source);
        }

        // new: filter by CR
        // cr_filter values: '' | 'consistent' | 'inconsistent'
        $crFilter = $request->get('cr_filter');
        if ($crFilter === 'consistent') {
            // only CR <= 0.10 (and non-null)
            $query->whereNotNull('consistency_ratio')->where('consistency_ratio', '<=', 0.10);
        } elseif ($crFilter === 'inconsistent') {
            // only CR > 0.10 (and non-null)
            $query->whereNotNull('consistency_ratio')->where('consistency_ratio', '>', 0.10);
        }

        $responses = $query->paginate(20);

        $campuses = \App\Models\Campus::all();

        return view('admin.questionnaire.index', compact('responses', 'campuses'));
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

        // Check consistency ratio
        if ($consistencyRatio > 0.10) {
            return redirect()->back()
                            ->withErrors(['consistency' => 'Consistency Ratio (' . round($consistencyRatio, 4) . ') melebihi 0.10. Silakan periksa kembali perbandingan Anda.'])
                            ->withInput();
        }

        QuestionnaireResponse::create([
            'campus_id' => $request->campus_id,
            'pairwise_values' => $pairwiseValues,
            'consistency_ratio' => $consistencyRatio, // tambahkan CR
            'source' => 'manual',
        ]);

        return redirect()->route('admin.questionnaire.index')
                            ->with('success', 'Data kuesioner berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        try {
            $resp = QuestionnaireResponse::findOrFail($id);
            $resp->delete(); 
            return redirect()->route('admin.questionnaire.index')
                            ->with('success', 'Data kuesioner berhasil dihapus.');
        } catch (\Exception $e) {
            \Log::error('Failed to delete questionnaire response: '.$e->getMessage(), ['id'=>$id]);
            return redirect()->route('admin.questionnaire.index')
                            ->withErrors(['delete' => 'Gagal menghapus data: '.$e->getMessage()]);
        }
    }
}
