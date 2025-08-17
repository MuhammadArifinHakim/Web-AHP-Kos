<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Campus;
use App\Models\Criteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BoardingHouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    // public function index()
    // {
    //     // $boardingHouses = BoardingHouse::with('campuses','boarding_house_campus','boarding_house_criteria')->get();
    //     // return view('admin.boarding-houses.index', compact('boardingHouses'));
    //     // return view('boarding-houses', compact('boardingHouses'));
    //     // Mengambil semua kos beserta relasi kampus dan nilai kriteria
    //     $boardingHouses = BoardingHouse::with(['campuses', 'criteriaValues'])->get();

    //     // Mengirim data ke view
    //     return view('admin.boarding-houses.index', compact('boardingHouses'));
    //     // return response()->json($boardingHouses);
    // }
    public function index(Request $request)
    {
        $query = BoardingHouse::with('campuses', 'criteriaValues');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('campuses', function($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        $boardingHouses = $query->get();

        // Definisikan variabel $search agar compact tidak error
        $search = $request->search;
        $campus_id = $request->campus_id;

        return view('admin.boarding-houses.index', compact('boardingHouses', 'search', 'campus_id'));
        // return response()->json([
        //     'boardingHouses' => $boardingHouses,
        //     'search' => $search,
        //     'campus_id' => $campus_id,
        // ]);

    }

    

// public function index()
// {
//     $boardingHouses = BoardingHouse::with('campuses')->get();
//     $campus = Campus::first(); // Akan null jika tabel kosong
//     $weightMethod = 'manual';

//     if (!$campus) {
//         return redirect()->back()->with('error', 'Data kampus belum tersedia.');
//     }

//     return view('boarding-houses', compact('boardingHouses', 'campus', 'weightMethod'));
// }

    public function create()
    {
        $campuses = Campus::all();
        $criteria = Criteria::with('subcriteria')->orderBy('order')->get();
        
        return view('admin.boarding-houses.create', compact('campuses', 'criteria'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'campuses' => 'required|array',
            'campuses.*' => 'exists:campuses,id',
            'distances' => 'required|array',
            'distances.*' => 'integer|min:0'
        ]);

        $kosData = $request->only(['name', 'description', 'address', 'price']);
        
        if ($request->hasFile('image')) {
            $kosData['image'] = $request->file('image')->store('kos', 'public');
        }

        $kos = BoardingHouse::create($kosData);

        // Attach campuses with distances
        foreach ($request->campuses as $index => $campusId) {
            $kos->campuses()->attach($campusId, [
                'distance' => $request->distances[$index]
            ]);
        }

        // Save criteria values
        $this->saveCriteriaValues($kos, $request);

        return redirect()->route('admin.boarding-houses.index')
                        ->with('success', 'Kos berhasil ditambahkan.');
    }

    public function edit(BoardingHouse $boardingHouse)
    {
        $campuses = Campus::all();
        $criteria = Criteria::with('subcriteria')->orderBy('order')->get();
        
        return view('admin.boarding-houses.edit', compact('boardingHouse', 'campuses', 'criteria'));
    }

    public function update(Request $request, BoardingHouse $boardingHouse)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'campuses' => 'required|array',
            'campuses.*' => 'exists:campuses,id',
            'distances' => 'required|array',
            'distances.*' => 'integer|min:0'
        ]);

        $kosData = $request->only(['name', 'description', 'address', 'price']);
        
        if ($request->hasFile('image')) {
            if ($boardingHouse->image) {
                Storage::disk('public')->delete($boardingHouse->image);
            }
            $kosData['image'] = $request->file('image')->store('kos', 'public');
        }

        $boardingHouse->update($kosData);

        // Sync campuses with distances
        $syncData = [];
        foreach ($request->campuses as $index => $campusId) {
            $syncData[$campusId] = ['distance' => $request->distances[$index]];
        }
        $boardingHouse->campuses()->sync($syncData);

        // Update criteria values
        $boardingHouse->criteriaValues()->delete();
        $this->saveCriteriaValues($boardingHouse, $request);

        return redirect()->route('admin.boarding-houses.index')
                        ->with('success', 'Kos berhasil diperbarui.');
    }

    public function destroy(BoardingHouse $boardingHouse)
    {
        if ($boardingHouse->image) {
            Storage::disk('public')->delete($boardingHouse->image);
        }
        
        $boardingHouse->delete();

        return redirect()->route('admin.boarding-houses.index')
                        ->with('success', 'Kos berhasil dihapus.');
    }

    private function saveCriteriaValues(BoardingHouse $kos, Request $request)
    {
        $criteria = Criteria::all();
        
        foreach ($criteria as $criterion) {
            $values = [];
            
            switch ($criterion->code) {
                case 'C3': // Fasilitas Kamar & Bangunan
                    $values = [
                        'kasur' => $request->input('c3_kasur', 0),
                        'lemari' => $request->input('c3_lemari', 0),
                        'meja' => $request->input('c3_meja', 0),
                        'kursi' => $request->input('c3_kursi', 0),
                        'kipas_angin' => $request->input('c3_kipas_angin', 0),
                        'ac' => $request->input('c3_ac', 0),
                        'tv' => $request->input('c3_tv', 0),
                        'wifi' => $request->input('c3_wifi', 0),
                        'kamar_mandi_dalam' => $request->input('c3_kamar_mandi_dalam', 0),
                        'dapur' => $request->input('c3_dapur', 0),
                        'parkiran' => $request->input('c3_parkiran', 0),
                        'termasuk_listrik' => $request->input('c3_termasuk_listrik', 0),
                    ];
                    break;

                case 'C4': // Fasilitas Lingkungan
                    $values = [
                        'warung' => $request->input('c4_warung', 0),
                        'laundry' => $request->input('c4_laundry', 0),
                        'klinik' => $request->input('c4_klinik', 0),
                        'atm' => $request->input('c4_atm', 0),
                        'minimarket' => $request->input('c4_minimarket', 0),
                        'fotocopy' => $request->input('c4_fotocopy', 0),
                        'tempat_ibadah' => $request->input('c4_tempat_ibadah', 0),
                        'pasar' => $request->input('c4_pasar', 0),
                    ];
                    break;

                case 'C5': // Keamanan & Privasi
                    $values = [
                        'cctv' => $request->input('c5_cctv', 0),
                        'pagar' => $request->input('c5_pagar', 0),
                        'penjaga' => $request->input('c5_penjaga', 0),
                    ];
                    break;

                case 'C6': // Peraturan Kos
                    $values = [
                        'jam_malam' => $request->input('c6_jam_malam') == 'ya' ? 0 : 1,
                        'membawa_teman' => $request->input('c6_membawa_teman', 0),
                        'ketentuan_bayar' => (float)$request->input('c6_ketentuan_bayar', 1),
                        'tamu_menginap' => $request->input('c6_tamu_menginap', 0),
                        'hewan_peliharaan' => $request->input('c6_hewan_peliharaan', 0),
                    ];
                    break;
            }

            if (!empty($values)) {
                $kos->criteriaValues()->create([
                    'criteria_id' => $criterion->id,
                    'values' => $values
                ]);
            }
        }
    }
}