<?php

namespace Database\Seeders;

use App\Models\Criteria;
use Illuminate\Database\Seeder;

class CriteriaSeeder extends Seeder
{
    public function run()
    {
        $criteria = [
            [
                'name' => 'Lokasi',
                'code' => 'C1',
                'description' => 'Jarak kos ke kampus',
                'type' => 'cost',
                'order' => 1
            ],
            [
                'name' => 'Harga/Biaya Sewa',
                'code' => 'C2', 
                'description' => 'Biaya sewa kos per bulan',
                'type' => 'cost',
                'order' => 2
            ],
            [
                'name' => 'Fasilitas Kamar & Bangunan',
                'code' => 'C3',
                'description' => 'Fasilitas yang tersedia di kamar dan bangunan',
                'type' => 'benefit',
                'order' => 3
            ],
            [
                'name' => 'Fasilitas Lingkungan',
                'code' => 'C4',
                'description' => 'Fasilitas umum di sekitar kos',
                'type' => 'benefit',
                'order' => 4
            ],
            [
                'name' => 'Keamanan & Privasi',
                'code' => 'C5',
                'description' => 'Tingkat keamanan dan privasi kos',
                'type' => 'benefit',
                'order' => 5
            ],
            [
                'name' => 'Peraturan Kos',
                'code' => 'C6',
                'description' => 'Aturan dan fleksibilitas kos',
                'type' => 'benefit',
                'order' => 6
            ]
        ];

        foreach ($criteria as $criterion) {
            Criteria::create($criterion);
        }
    }
}