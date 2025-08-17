<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Campus;
use App\Models\Criteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BoardingHouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

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

        $search = $request->search;
        $campus_id = $request->campus_id;

        return view('admin.boarding-houses.index', compact('boardingHouses', 'search', 'campus_id'));
    }

    public function create()
    {
        $campuses = Campus::all();
        $criteria = Criteria::with('subcriteria')->orderBy('order')->get();

        return view('admin.boarding-houses.create', compact('campuses', 'criteria'));
    }

    public function store(Request $request)
    {
        // accept both 'campuses' or 'campus_id' arrays to be flexible with different forms
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'campuses' => 'required_without:campus_id|array',
            'campuses.*' => 'integer|exists:campuses,id',
            'campus_id' => 'required_without:campuses|array',
            'campus_id.*' => 'integer|exists:campuses,id',
            'distances' => 'required_without:distance|array',
            'distances.*' => 'nullable|numeric|min:0',
            'distance' => 'required_without:distances|array',
            'distance.*' => 'nullable|numeric|min:0',
        ]);

        $kosData = $request->only(['name', 'description', 'address', 'price']);

        if ($request->hasFile('image')) {
            $kosData['image'] = $request->file('image')->store('kos', 'public');
        }

        DB::beginTransaction();
        try {
            $kos = BoardingHouse::create($kosData);

            // pick campus array whichever exists
            $campusArray = $request->input('campuses') ?? $request->input('campus_id') ?? [];
            $distanceArray = $request->input('distances') ?? $request->input('distance') ?? [];

            // Attach campuses with distances (handle mismatched lengths gracefully)
            foreach ($campusArray as $index => $campusId) {
                $dist = isset($distanceArray[$index]) && $distanceArray[$index] !== '' ? floatval($distanceArray[$index]) : null;
                $kos->campuses()->attach($campusId, ['distance' => $dist]);
            }

            // Save criteria values
            $this->saveCriteriaValues($kos, $request);

            DB::commit();

            return redirect()->route('admin.boarding-houses.index')->with('success', 'Kos berhasil ditambahkan.');

        } catch (\Throwable $e) {
            DB::rollBack(); 
            \Log::error('Error create boarding house: '.$e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data.']);
        }
    }

    public function edit(BoardingHouse $boardingHouse)
    {
        // eager load to avoid extra queries in the view
        $boardingHouse->load(['campuses', 'criteriaValues']);

        // campuses list for select options (ordered)
        $campuses = Campus::orderBy('name')->get();

        // criteria (with subcriteria if you use them)
        $criteria = Criteria::with('subcriteria')->orderBy('order')->get();

        // build map: criteria code => values (array)
        $cvMap = [];
        $criteriaById = $criteria->keyBy('id');
        foreach ($boardingHouse->criteriaValues as $cv) {
            // make sure $cv->values is array (cast or decode)
            $vals = $cv->values;
            if (!is_array($vals)) {
                $vals = is_string($vals) ? json_decode($vals, true) ?? [] : (array)$vals;
            }

            $crit = $criteriaById[$cv->criteria_id] ?? null;
            if ($crit) {
                $cvMap[$crit->code] = $vals;
            } else {
                // fallback use id if code not found
                $cvMap[$cv->criteria_id] = $vals;
            }
        }

        // normalize C6 ketentuan_bayar -> convert stored mapped value (0.5 / 0.25 / 0.75 / 1.0)
        // back to months 1|3|6|12 so the <select> in the blade can match correctly
        if (!empty($cvMap['C6']['ketentuan_bayar'])) {
            $stored = $cvMap['C6']['ketentuan_bayar'];

            // mapping value -> months
            $reverse = [
                1  => 1.0,
                3  => 0.75,
                6  => 0.5,
                12 => 0.25,
            ];

            // if stored is numeric and matches one of the mapped floats, replace with the month key
            if (is_numeric($stored)) {
                $storedFloat = (float)$stored;
                $foundKey = array_search($storedFloat, $reverse, true);
                if ($foundKey !== false) {
                    $cvMap['C6']['ketentuan_bayar'] = (int)$foundKey;
                } else {
                    // stored might already be month (e.g. 6) — cast to int to be safe
                    $cvMap['C6']['ketentuan_bayar'] = (int)$storedFloat;
                }
            }
        }

        // map campus distances for pre-filling the campus rows
        $campusDistances = $boardingHouse->campuses->map(function($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'distance' => $c->pivot->distance ?? null,
            ];
        })->toArray();

        return view('admin.boarding-houses.edit', compact(
            'boardingHouse',
            'campuses',
            'criteria',
            'cvMap',
            'campusDistances'
        ));
        // return response()->json([
        //     'boardingHouse' => $boardingHouse,
        //     'campuses' => $campuses,
        //     'criteria' => $criteria,
        //     'cvMap' => $cvMap,
        //     'campusDistances' => $campusDistances,
        // ]);
    }


    public function update(Request $request, BoardingHouse $boardingHouse)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'price' => 'required|numeric',
            'campuses' => 'nullable|array',
            'campuses.*' => 'required|exists:campuses,id',
            'distances' => 'nullable|array',
            'distances.*' => 'nullable|numeric',
            // add validations for c2_*, c4_*, c5_*, c6_* as needed
        ]);

        \DB::transaction(function() use ($request, $boardingHouse) {

            // update basic
            $boardingHouse->update($request->only(['name','description','address','price']));

            // sync campuses with distances (pivot)
            $campuses = $request->input('campuses', []);
            $distances = $request->input('distances', []);
            $sync = [];
            foreach ($campuses as $i => $campusId) {
                $distance = $distances[$i] ?? null;
                $sync[$campusId] = ['distance' => $distance];
            }
            $boardingHouse->campuses()->sync($sync);

            // prepare criteria values (example for C2, C4, C5, C6)
            // C2 (binary list)
            $c2Keys = ['kasur','lemari','meja','kursi','kipas_angin','ac','tv','wifi','kamar_mandi_dalam','dapur','parkiran','termasuk_listrik'];
            $c2 = [];
            foreach ($c2Keys as $k) {
                $c2[$k] = $request->has('c2_'.$k) ? 1 : 0;
            }
            // upsert into boarding_house_criteria
            \App\Models\BoardingHouseCriteria::updateOrCreate(
                ['boarding_house_id' => $boardingHouse->id, 'criteria_id' => 2],
                ['values' => $c2]
            );

            // C4 distances
            $c4Keys = ['warung','laundry','klinik','atm','minimarket','fotocopy','tempat_ibadah','pasar'];
            $c4 = [];
            foreach ($c4Keys as $k) {
                $c4[$k] = $request->input('c4_'.$k, null);
            }
            \App\Models\BoardingHouseCriteria::updateOrCreate(
                ['boarding_house_id' => $boardingHouse->id, 'criteria_id' => 4],
                ['values' => $c4]
            );

            // C5 and C6 similar...
            // e.g. C5
            $c5 = [
                'cctv' => $request->has('c5_cctv') ? 1 : 0,
                'pagar' => $request->has('c5_pagar') ? 1 : 0,
                'penjaga' => $request->has('c5_penjaga') ? 1 : 0,
            ];
            \App\Models\BoardingHouseCriteria::updateOrCreate(
                ['boarding_house_id' => $boardingHouse->id, 'criteria_id' => 5],
                ['values' => $c5]
            );

            // C6
            // mapping input months -> stored mapped value
            $ketentuan_bayar_raw = $request->input('c6_ketentuan_bayar', 1);
            $mapPayment = 1.0;
            switch (intval($ketentuan_bayar_raw)) {
                case 1:
                    $mapPayment = 1.0;
                    break;
                case 3:
                    $mapPayment = 0.75;
                    break;
                case 6:
                    $mapPayment = 0.5;
                    break;
                case 12:
                    $mapPayment = 0.25;
                    break;
                default:
                    // if user somehow passed a numeric mapped value already, keep it
                    if (is_numeric($ketentuan_bayar_raw)) {
                        $mapPayment = (float)$ketentuan_bayar_raw;
                    }
                    break;
            }

            $c6 = [
                'jam_malam' => $request->input('c6_jam_malam') === 'ya' ? 1 : 0,
                'membawa_teman' => $request->has('c6_membawa_teman') ? 1 : 0,
                'ketentuan_bayar' => $mapPayment,
                'tamu_menginap' => $request->has('c6_tamu_menginap') ? 1 : 0,
                'hewan_peliharaan' => $request->has('c6_hewan_peliharaan') ? 1 : 0,
            ];
            \App\Models\BoardingHouseCriteria::updateOrCreate(
                ['boarding_house_id' => $boardingHouse->id, 'criteria_id' => 6],
                ['values' => $c6]
            );
        });

        return redirect()->route('admin.boarding-houses.index')->with('success','Data kos berhasil diperbarui.');
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
        $criteria = Criteria::all()->keyBy('code');

        // --- C1: Jarak
        // Jarak disimpan pada pivot boarding_house_campus (already handled)
        // optionally, you can create a criteriaValues entry for C1 if desired:
        if (isset($criteria['C1'])) {
            // collect distances per campus and store as assoc campus_id => distance
            $campusArray = $request->input('campuses') ?? $request->input('campus_id') ?? [];
            $distanceArray = $request->input('distances') ?? $request->input('distance') ?? [];
            $distMap = [];
            foreach ($campusArray as $idx => $cid) {
                $distMap[$cid] = isset($distanceArray[$idx]) && $distanceArray[$idx] !== '' ? floatval($distanceArray[$idx]) : null;
            }
            $kos->criteriaValues()->create([
                'criteria_id' => $criteria['C1']->id,
                'values' => $distMap
            ]);
        }

        // --- C2: Fasilitas Kamar & Bangunan (YA/TIDAK)
        if (isset($criteria['C2'])) {
            $c2_values = [
                'kasur' => (int)$request->input('c2_kasur', 0),
                'lemari' => (int)$request->input('c2_lemari', 0),
                'meja' => (int)$request->input('c2_meja', 0),
                'kursi' => (int)$request->input('c2_kursi', 0),
                'kipas_angin' => (int)$request->input('c2_kipas_angin', 0),
                'ac' => (int)$request->input('c2_ac', 0),
                'tv' => (int)$request->input('c2_tv', 0),
                'wifi' => (int)$request->input('c2_wifi', 0),
                'kamar_mandi_dalam' => (int)$request->input('c2_kamar_mandi_dalam', 0),
                'dapur' => (int)$request->input('c2_dapur', 0),
                'parkiran' => (int)$request->input('c2_parkiran', 0),
                'termasuk_listrik' => (int)$request->input('c2_termasuk_listrik', 0),
            ];
            $kos->criteriaValues()->create([
                'criteria_id' => $criteria['C2']->id,
                'values' => $c2_values
            ]);
        }

        // --- C3: Harga/Biaya Sewa (store numeric price)
        if (isset($criteria['C3'])) {
            $kos->criteriaValues()->create([
                'criteria_id' => $criteria['C3']->id,
                'values' => ['price' => (float)$request->input('price', 0)]
            ]);
        }

        // --- C4: Fasilitas Lingkungan (jarak ke kos)
        if (isset($criteria['C4'])) {
            $c4_values = [
                'warung' => $request->input('c4_warung') !== null ? (float)$request->input('c4_warung') : null,
                'laundry' => $request->input('c4_laundry') !== null ? (float)$request->input('c4_laundry') : null,
                'klinik' => $request->input('c4_klinik') !== null ? (float)$request->input('c4_klinik') : null,
                'atm' => $request->input('c4_atm') !== null ? (float)$request->input('c4_atm') : null,
                'minimarket' => $request->input('c4_minimarket') !== null ? (float)$request->input('c4_minimarket') : null,
                'fotocopy' => $request->input('c4_fotocopy') !== null ? (float)$request->input('c4_fotocopy') : null,
                'tempat_ibadah' => $request->input('c4_tempat_ibadah') !== null ? (float)$request->input('c4_tempat_ibadah') : null,
                'pasar' => $request->input('c4_pasar') !== null ? (float)$request->input('c4_pasar') : null,
            ];
            $kos->criteriaValues()->create([
                'criteria_id' => $criteria['C4']->id,
                'values' => $c4_values
            ]);
        }

        // --- C5: Keamanan & Privasi (YA/TIDAK)
        if (isset($criteria['C5'])) {
            $c5_values = [
                'cctv' => (int)$request->input('c5_cctv', 0),
                'pagar' => (int)$request->input('c5_pagar', 0),
                'penjaga' => (int)$request->input('c5_penjaga', 0),
            ];
            $kos->criteriaValues()->create([
                'criteria_id' => $criteria['C5']->id,
                'values' => $c5_values
            ]);
        }

        // --- C6: Peraturan Kos (mapping sesuai spesifikasi)
        if (isset($criteria['C6'])) {
            $jam_malam_raw = $request->input('c6_jam_malam', null); // expect 'ya' or 'tidak'
            $jam_malam = null;
            if ($jam_malam_raw !== null) {
                $jam_malam = strtolower($jam_malam_raw) === 'ya' ? 0 : 1;
            }
            $membawa_teman = (int)$request->input('c6_membawa_teman', 0);
            $ketentuan_bayar_raw = $request->input('c6_ketentuan_bayar', 1); // expects 1|3|6|12 or mapped float
            $mapPayment = 1.0;
            switch (intval($ketentuan_bayar_raw)) {
                case 1: $mapPayment = 1.0; break;
                case 3: $mapPayment = 0.75; break;
                case 6: $mapPayment = 0.5; break;
                case 12: $mapPayment = 0.25; break;
                default:
                    if (is_numeric($ketentuan_bayar_raw)) $mapPayment = (float)$ketentuan_bayar_raw;
            }
            $tamu_menginap = (int)$request->input('c6_tamu_menginap', 0);
            $hewan_peliharaan = (int)$request->input('c6_hewan_peliharaan', 0);

            $c6_values = [
                'jam_malam' => $jam_malam ?? 0,
                'membawa_teman' => $membawa_teman,
                'ketentuan_bayar' => $mapPayment,
                'tamu_menginap' => $tamu_menginap,
                'hewan_peliharaan' => $hewan_peliharaan
            ];
            $kos->criteriaValues()->create([
                'criteria_id' => $criteria['C6']->id,
                'values' => $c6_values
            ]);
        }
    }
}
