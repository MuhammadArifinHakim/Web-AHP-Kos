<?php

namespace Database\Seeders;

use App\Models\Criteria;
use Illuminate\Database\Seeder;

class CriteriaSeeder extends Seeder
{
    public function run()
    {
        // Perbaikan urutan: C2 -> Fasilitas (kamar & bangunan), C3 -> Harga
        // Seeder ini menggunakan updateOrCreate sehingga aman dijalankan berulang kali.
        $criteria = [
            [
                'name' => 'Lokasi',
                'code' => 'C1',
                'description' => 'Jarak kos ke kampus',
                'type' => 'cost',
                'order' => 1
            ],
            [
                // sekarang C2 adalah Fasilitas Kamar & Bangunan (sesuai urutan form)
                'name' => 'Fasilitas Kamar & Bangunan',
                'code' => 'C2',
                'description' => 'Fasilitas yang tersedia di kamar dan bangunan',
                'type' => 'benefit',
                'order' => 2
            ],
            [
                // sekarang C3 adalah Harga/Biaya Sewa
                'name' => 'Harga/Biaya Sewa',
                'code' => 'C3',
                'description' => 'Biaya sewa kos per bulan',
                'type' => 'cost',
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
            // key 'code' dipakai sebagai unique identifier saat update/insert
            Criteria::updateOrCreate(
                ['code' => $criterion['code']],
                [
                    'name' => $criterion['name'],
                    'description' => $criterion['description'],
                    'type' => $criterion['type'],
                    'order' => $criterion['order'],
                ]
            );
        }
    }
}
