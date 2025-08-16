<?php

namespace Database\Seeders;

use App\Models\Subcriteria;
use Illuminate\Database\Seeder;

class SubcriteriaSeeder extends Seeder
{
    public function run()
    {
        $subcriteria = [
            // Fasilitas Kamar & Bangunan (C2)
            ['criteria_id' => 2, 'name' => 'Kasur', 'code' => 'C2_1', 'type' => 'binary', 'order' => 1],
            ['criteria_id' => 2, 'name' => 'Lemari', 'code' => 'C2_2', 'type' => 'binary', 'order' => 2],
            ['criteria_id' => 2, 'name' => 'Meja', 'code' => 'C2_3', 'type' => 'binary', 'order' => 3],
            ['criteria_id' => 2, 'name' => 'Kursi', 'code' => 'C2_4', 'type' => 'binary', 'order' => 4],
            ['criteria_id' => 2, 'name' => 'Kipas Angin', 'code' => 'C2_5', 'type' => 'binary', 'order' => 5],
            ['criteria_id' => 2, 'name' => 'AC', 'code' => 'C2_6', 'type' => 'binary', 'order' => 6],
            ['criteria_id' => 2, 'name' => 'TV', 'code' => 'C2_7', 'type' => 'binary', 'order' => 7],
            ['criteria_id' => 2, 'name' => 'WIFI', 'code' => 'C2_8', 'type' => 'binary', 'order' => 8],
            ['criteria_id' => 2, 'name' => 'Kamar Mandi Dalam', 'code' => 'C2_9', 'type' => 'binary', 'order' => 9],
            ['criteria_id' => 2, 'name' => 'Dapur', 'code' => 'C2_10', 'type' => 'binary', 'order' => 10],
            ['criteria_id' => 2, 'name' => 'Parkiran', 'code' => 'C2_11', 'type' => 'binary', 'order' => 11],
            ['criteria_id' => 2, 'name' => 'Termasuk Biaya Listrik', 'code' => 'C2_12', 'type' => 'binary', 'order' => 12],

            // Fasilitas Lingkungan (C4)
            ['criteria_id' => 4, 'name' => 'Warung', 'code' => 'C4_1', 'type' => 'distance', 'order' => 1],
            ['criteria_id' => 4, 'name' => 'Laundry', 'code' => 'C4_2', 'type' => 'distance', 'order' => 2],
            ['criteria_id' => 4, 'name' => 'Klinik', 'code' => 'C4_3', 'type' => 'distance', 'order' => 3],
            ['criteria_id' => 4, 'name' => 'ATM', 'code' => 'C4_4', 'type' => 'distance', 'order' => 4],
            ['criteria_id' => 4, 'name' => 'MiniMarket', 'code' => 'C4_5', 'type' => 'distance', 'order' => 5],
            ['criteria_id' => 4, 'name' => 'Fotocopy', 'code' => 'C4_6', 'type' => 'distance', 'order' => 6],
            ['criteria_id' => 4, 'name' => 'Tempat Ibadah', 'code' => 'C4_7', 'type' => 'distance', 'order' => 7],
            ['criteria_id' => 4, 'name' => 'Pasar', 'code' => 'C4_8', 'type' => 'distance', 'order' => 8],

            // Keamanan & Privasi (C5)
            ['criteria_id' => 5, 'name' => 'CCTV', 'code' => 'C5_1', 'type' => 'binary', 'order' => 1],
            ['criteria_id' => 5, 'name' => 'Pagar', 'code' => 'C5_2', 'type' => 'binary', 'order' => 2],
            ['criteria_id' => 5, 'name' => 'Penjaga', 'code' => 'C5_3', 'type' => 'binary', 'order' => 3],

            // Peraturan Kos (C6)
            ['criteria_id' => 6, 'name' => 'Jam Malam', 'code' => 'C6_1', 'type' => 'binary', 'order' => 1],
            ['criteria_id' => 6, 'name' => 'Membawa Teman', 'code' => 'C6_2', 'type' => 'binary', 'order' => 2],
            ['criteria_id' => 6, 'name' => 'Ketentuan Bayar', 'code' => 'C6_3', 'type' => 'numeric', 'order' => 3],
            ['criteria_id' => 6, 'name' => 'Tamu Menginap', 'code' => 'C6_4', 'type' => 'binary', 'order' => 4],
            ['criteria_id' => 6, 'name' => 'Hewan Peliharaan', 'code' => 'C6_5', 'type' => 'binary', 'order' => 5],
        ];

        foreach ($subcriteria as $subcriterion) {
            Subcriteria::create($subcriterion);
        }
    }
}