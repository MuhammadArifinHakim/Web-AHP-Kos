<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class KosFromExcelSeeder extends Seeder
{
    public function run()
    {
        $excelPath = storage_path('app/data_kos.xlsx');

        if (!file_exists($excelPath)) {
            $this->command->error("File Excel tidak ditemukan: {$excelPath}");
            return;
        }

        try {
            $sheets = Excel::toArray(null, $excelPath);
        } catch (\Throwable $e) {
            $this->command->error("Gagal membaca Excel: " . $e->getMessage());
            return;
        }

        if (empty($sheets) || empty($sheets[0])) {
            $this->command->error("Sheet kosong di file Excel.");
            return;
        }

        $rows = $sheets[0];
        $headerRow = $rows[0] ?? [];
        // normalisasi header: trim + lowercase
        $header = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, $headerRow);

        $this->command->info("Header ditemukan: " . implode(', ', $header));

        // criteria mapping dari DB
        $criteriaMap = [];
        foreach (['C2', 'C4', 'C5', 'C6'] as $code) {
            $id = DB::table('criteria')->where('code', $code)->value('id');
            if ($id) $criteriaMap[$code] = $id;
            else $this->command->warn("Warning: criteria code {$code} tidak ditemukan.");
        }

        $inserted = 0;
        $skipped = 0;

        // helper: baca kolom berdasarkan nama header (case-insensitive)
        $readFromRow = function (array $row, string $name, $default = null) use ($header) {
            $key = strtolower(trim($name));
            $idx = array_search($key, $header);
            if ($idx === false) return $default;
            return array_key_exists($idx, $row) ? $row[$idx] : $default;
        };

        $toBoolInt = function ($v) {
            if (is_null($v) || $v === '') return 0;
            $s = strtolower(trim((string) $v));
            // keep letters, numbers, slash, space, dot, hyphen
            $sClean = preg_replace('/[^\p{L}\p{N}\s\/\.-]/u', '', $s);

            // explicit false tokens (prioritas)
            $falsePattern = '/\b(tidak|no|n|none|nah|nope|dilarang|forbid|forbidden|disallow)\b/u';
            if (preg_match($falsePattern, $sClean)) return 0;

            // explicit true tokens (includes wanita/pria and also 'bayar'/'paid')
            $truePattern = '/\b(1|ya|yes|y|true|t|boleh|allowed|allow|bayar|paid|wanita|perempuan|pria|laki|male|female)\b/u';
            if (preg_match($truePattern, $sClean)) return 1;

            // if numeric > 0 => true
            if (is_numeric($sClean)) return intval($sClean) ? 1 : 0;

            // if contains slash or 'dan' check parts for wanita/pria or 'ya'
            if (strpos($sClean, '/') !== false || strpos($sClean, ' dan ') !== false || strpos($sClean, '&') !== false) {
                $parts = preg_split('/[\/&]| dan /ui', $sClean);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if (preg_match('/\b(wanita|pria|perempuan|laki|female|male)\b/u', $p)) return 1;
                    if (preg_match('/\b(ya|yes|1|bayar|paid)\b/u', $p)) return 1;
                }
            }

            return 0;
        };


        // distances normalizer
        $toNullableInt = function ($v) {
            if ($v === null || $v === '') return null;
            if (is_numeric($v)) return intval($v);
            if (preg_match('/(\d+)/', (string) $v, $m)) return intval($m[1]);
            return null;
        };

        $mapKetentuanBayar = function ($v) {
            if (is_null($v) || $v === '') return null;
            $s = strtolower(trim((string) $v));
            $s = str_replace([','], '.', $s); // terima 0,75 juga
            $s = preg_replace('/\s+/', ' ', $s);

            // jika ada kata eksplisit "ya" / "bayar" maka anggap 1 bulan (nilai 1),
            // tapi jangan termasuk angka '1' di sini — angka akan ditangani di bawah.
            if (preg_match('/\b(ya|yes|y|bayar|paid)\b/', $s)) {
                return 1;
            }
            if (preg_match('/\b(tidak|no|n)\b/', $s)) {
                return 0;
            }

            // ambil angka pertama (integer atau decimal) — jika tidak ada, return null
            if (!preg_match('/(\d+(\.\d+)?)/', $s, $m)) {
                return null;
            }
            $num = floatval($m[1]);

            // normalisasi unit kata (tahun/bulan)
            if (preg_match('/(tahun|thn|th|yrs?|year|yr)/', $s)) {
                $months = intval(round($num * 12)); // ex: 1 tahun => 12 bulan
            } elseif (preg_match('/(bulan|bln|mo|mos?)/', $s)) {
                $months = intval(round($num));
            } else {
                // tidak ada unit --> asumsikan angka adalah bulan (policy ini bisa diubah)
                $months = intval(round($num));
            }

            // mapping months -> nilai sistem Anda
            switch ($months) {
                case 1:  return 1;    // 1 bulan
                case 3:  return 0.75; // 3 bulan
                case 6:  return 0.5;  // 6 bulan
                case 12: return 0.25; // 12 bulan (1 tahun)
                default:
                    // jika tidak cocok (mis. 2 bulan), kembalikan null agar bisa direview
                    return null;
            }
        };


        // mulai loop baris (index 1 karena 0 header)
        for ($r = 1; $r < count($rows); $r++) {
            $row = array_values($rows[$r]);

            // skip rows that are basically empty
            $maybeName = $readFromRow($row, 'Nama Kos', '');
            $maybeCampus = $readFromRow($row, 'Kampus', '');
            if (($maybeName === null || trim((string)$maybeName) === '') && ($maybeCampus === null || trim((string)$maybeCampus) === '')) {
                $skipped++;
                continue;
            }

            $kampusRaw = $readFromRow($row, 'Kampus', null);
            $name = trim((string) ($readFromRow($row, 'Nama Kos', '') ?: 'Kos tanpa nama'));
            $distance = $readFromRow($row, 'Jarak ke Kampus', null);
            $price = $readFromRow($row, 'Harga/Bulan (Rp)', null);

            // resolve campus id
            $campusId = null;
            if (!empty($kampusRaw)) {
                if (is_numeric($kampusRaw)) {
                    $camp = DB::table('campuses')->where('id', intval($kampusRaw))->first();
                    if ($camp) $campusId = $camp->id;
                }
                if (is_null($campusId)) {
                    $camp = DB::table('campuses')->where('name', 'like', "%{$kampusRaw}%")->first();
                    if ($camp) $campusId = $camp->id;
                }
            }

            $now = Carbon::now();

            // create/update boarding_houses
            $existingBh = DB::table('boarding_houses')->where('name', $name)->first();
            if (!$existingBh) {
                $bhId = DB::table('boarding_houses')->insertGetId([
                    'name' => $name,
                    'description' => null,
                    'address' => null,
                    'price' => is_numeric($price) ? $price : null,
                    'image' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $bhId = $existingBh->id;
                if (is_numeric($price)) {
                    DB::table('boarding_houses')->where('id', $bhId)->update(['price' => $price, 'updated_at' => $now]);
                }
            }

            // insert/update pivot boarding_house_campus
            if (!is_null($campusId)) {
                $exists = DB::table('boarding_house_campus')
                    ->where('boarding_house_id', $bhId)
                    ->where('campus_id', $campusId)
                    ->first();

                if ($exists) {
                    DB::table('boarding_house_campus')->where('id', $exists->id)
                        ->update([
                            'distance' => is_numeric($distance) ? intval($distance) : $distance,
                            'updated_at' => $now
                        ]);
                } else {
                    DB::table('boarding_house_campus')->insert([
                        'boarding_house_id' => $bhId,
                        'campus_id' => $campusId,
                        'distance' => is_numeric($distance) ? intval($distance) : $distance,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
            } else {
                $this->command->warn("Baris ".($r+1).": campus '{$kampusRaw}' tidak dapat di-resolve. Pivot tidak dibuat.");
            }

            // build criteria arrays
            $c2_keys = ['Kasur','Lemari','Meja','Kursi','Kipas Angin','AC','TV','WIFI','Kamar Mandi Dalam','Dapur','Parkiran','Termasuk biaya listrik'];
            $c2_values = [];
            foreach ($c2_keys as $col) {
                $val = $readFromRow($row, $col, 0);
                $key = Str::slug(str_ireplace('biaya ', '', $col), '_');
                if ($key === 'termasuk_biaya_listrik') $key = 'termasuk_listrik';
                $c2_values[$key] = $toBoolInt($val);
            }

            $c4_keys = ['Warung','Laundry','Klinik','ATM','MiniMarket','Fotocopy','Tempat Ibadah','Pasar'];
            $c4_values = [];
            foreach ($c4_keys as $col) {
                $val = $readFromRow($row, $col, null);
                $c4_values[ Str::slug(strtolower($col), '_') ] = $toNullableInt($val);
            }

            $c5_keys = ['CCTV','Pagar','Penjaga'];
            $c5_values = [];
            foreach ($c5_keys as $col) {
                $val = $readFromRow($row, $col, 0);
                $c5_values[ Str::slug($col, '_') ] = $toBoolInt($val);
            }

            // C6 policies
            $c6_values = [
                'jam_malam' => $toBoolInt($readFromRow($row, 'Jam Malam', 0)),
                'membawa_teman' => $toBoolInt($readFromRow($row, 'Membawa Teman', 0)), // WANITA/PRIA/YA -> 1
                'ketentuan_bayar' => $mapKetentuanBayar($readFromRow($row, 'Ketentuan Bayar', null)),
                'tamu_menginap' => $toBoolInt($readFromRow($row, 'Tamu Menginap', 0)),
                'hewan_peliharaan' => $toBoolInt($readFromRow($row, 'Hewan Peliharaan', 0)),
            ];

            $pairs = [
                'C2' => $c2_values,
                'C4' => $c4_values,
                'C5' => $c5_values,
                'C6' => $c6_values,
            ];

            foreach ($pairs as $code => $valuesArr) {
                if (!isset($criteriaMap[$code])) continue;
                $criteriaId = $criteriaMap[$code];

                $existing = DB::table('boarding_house_criteria')
                    ->where('boarding_house_id', $bhId)
                    ->where('criteria_id', $criteriaId)
                    ->first();

                $jsonValues = json_encode($valuesArr, JSON_UNESCAPED_UNICODE);

                if ($existing) {
                    DB::table('boarding_house_criteria')->where('id', $existing->id)
                        ->update([
                            'values' => $jsonValues,
                            'updated_at' => $now
                        ]);
                } else {
                    DB::table('boarding_house_criteria')->insert([
                        'boarding_house_id' => $bhId,
                        'criteria_id' => $criteriaId,
                        'values' => $jsonValues,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
            }

            $inserted++;
            $this->command->info("Row ".($r+1)." imported: {$name} (bh_id={$bhId})");
        } // end for

        $this->command->info("Import selesai. Inserted/updated rows: {$inserted}. Skipped: {$skipped}.");
    }
}
