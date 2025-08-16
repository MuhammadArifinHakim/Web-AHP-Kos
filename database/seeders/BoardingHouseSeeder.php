<?php

namespace Database\Seeders;

use App\Models\BoardingHouse;
use App\Models\Campus;
use App\Models\Criteria;
use Illuminate\Database\Seeder;

class BoardingHouseSeeder extends Seeder
{
    public function run()
    {
        $boardingHouses = [
            [
                'name' => 'Kos Melati',
                'description' => 'Kos nyaman dengan fasilitas lengkap',
                'address' => 'Jl. Melati No. 123',
                'price' => 1200000,
            ],
            [
                'name' => 'Kos Mawar',
                'description' => 'Kos bersih dan strategis',
                'address' => 'Jl. Mawar No. 456',
                'price' => 1500000,
            ],
            [
                'name' => 'Kos Anggrek',
                'description' => 'Kos dengan keamanan 24 jam',
                'address' => 'Jl. Anggrek No. 789',
                'price' => 1800000,
            ],
            [
                'name' => 'Kos Melur',
                'description' => 'Kos dengan lingkungan tenang dan aman',
                'address' => 'Jl. Melur No. 101',
                'price' => 1300000,
            ],
            [
                'name' => 'Kos Kenanga',
                'description' => 'Kos strategis dekat kampus',
                'address' => 'Jl. Kenanga No. 202',
                'price' => 1400000,
            ],
        ];

        $campuses = Campus::all();
        $criteria = Criteria::all();

        foreach ($boardingHouses as $kosData) {
            $kos = BoardingHouse::create($kosData);

            // Attach to campuses with distances
            foreach ($campuses as $campus) {
                $kos->campuses()->attach($campus->id, [
                    'distance' => rand(500, 3000)
                ]);
            }

            // Add criteria values
            foreach ($criteria as $criterion) {
                $values = $this->generateCriteriaValues($criterion);
                $kos->criteriaValues()->create([
                    'criteria_id' => $criterion->id,
                    'values' => $values
                ]);
            }
        }
    }

    private function generateCriteriaValues($criterion)
    {
        switch ($criterion->code) {
            case 'C2': // Fasilitas Kamar & Bangunan
                return [
                    'kasur' => rand(0, 1),
                    'lemari' => rand(0, 1),
                    'meja' => rand(0, 1),
                    'kursi' => rand(0, 1),
                    'kipas_angin' => rand(0, 1),
                    'ac' => rand(0, 1),
                    'tv' => rand(0, 1),
                    'wifi' => rand(0, 1),
                    'kamar_mandi_dalam' => rand(0, 1),
                    'dapur' => rand(0, 1),
                    'parkiran' => rand(0, 1),
                    'termasuk_listrik' => rand(0, 1),
                ];

            case 'C4': // Fasilitas Lingkungan
                return [
                    'warung' => rand(100, 1000),
                    'laundry' => rand(100, 1000),
                    'klinik' => rand(500, 2000),
                    'atm' => rand(200, 1500),
                    'minimarket' => rand(100, 800),
                    'fotocopy' => rand(150, 600),
                    'tempat_ibadah' => rand(200, 1200),
                    'pasar' => rand(800, 3000),
                ];

            case 'C5': // Keamanan & Privasi
                return [
                    'cctv' => rand(0, 1),
                    'pagar' => rand(0, 1),
                    'penjaga' => rand(0, 1),
                ];

            case 'C6': // Peraturan Kos
                return [
                    'jam_malam' => rand(0, 1) == 1 ? 0 : 1, // Ya = 0, Tidak = 1
                    'membawa_teman' => rand(0, 1),
                    'ketentuan_bayar' => [1, 0.75, 0.5, 0.25][rand(0, 3)],
                    'tamu_menginap' => rand(0, 1),
                    'hewan_peliharaan' => rand(0, 1),
                ];

            default:
                return [];
        }
    }
}