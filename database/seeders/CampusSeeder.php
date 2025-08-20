<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;

class CampusSeeder extends Seeder
{
    public function run()
    {
        $campuses = [
            [
                'name' => 'Kampus 1',
                'code' => 'K1',
                'description' => 'Fakultas Psikologi; Fakultas Ekonomi dan Bisnis'
            ],
            [
                'name' => 'Kampus 2',
                'code' => 'K2',
                'description' => 'Fakultas Ekonomi dan Bisnis'
            ],
            [
                'name' => 'Kampus 3',
                'code' => 'K3',
                'description' => 'Fakultas Farmasi ; Fakultas Kesehatan Masyarakat'
            ],
            [
                'name' => 'Kampus 4',
                'code' => 'K4',
                'description' => 'Fakultas Keguruan dan Ilmu Pendidikan; Fakultas Sains dan Teknologi Terapan; Fakultas Sastra Budaya dan Komunikasi; Fakultas Teknologi Industri; Fakultas Hukum; Fakultas Kedokteran; Fakultas Agama Islam'
            ],
            [
                'name' => 'Kampus 5',
                'code' => 'K5',
                'description' => 'Fakultas Keguruan dan Ilmu Pendidikan'
            ],
            [
                'name' => 'Kampus 6',
                'code' => 'K6',
                'description' => 'Fakultas Agama Islam'
            ],
        ];

        foreach ($campuses as $campus) {
            Campus::create($campus);
        }
    }
}
