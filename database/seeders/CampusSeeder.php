<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;

class CampusSeeder extends Seeder
{
    public function run()
    {
        // $campuses = [
        //     ['name' => 'Kampus 1', 'code' => 'K1', 'description' => 'Kampus utama universitas'],
        //     ['name' => 'Kampus 2', 'code' => 'K2', 'description' => 'Kampus fakultas teknik'],
        //     ['name' => 'Kampus 3', 'code' => 'K3', 'description' => 'Kampus fakultas ekonomi'],
        // ];
        $campuses = [
            ['name' => 'Kampus 1', 'code' => 'K1', 'description' => 'Kampus utama universitas'],
            ['name' => 'Kampus 2', 'code' => 'K2', 'description' => 'Kampus fakultas teknik'],
            ['name' => 'Kampus 3', 'code' => 'K3', 'description' => 'Kampus fakultas ekonomi'],
            ['name' => 'Kampus 4', 'code' => 'K4', 'description' => 'Kampus fakultas hukum'],
            ['name' => 'Kampus 5', 'code' => 'K5', 'description' => 'Kampus fakultas kedokteran'],
            ['name' => 'Kampus 6', 'code' => 'K6', 'description' => 'Kampus fakultas ilmu sosial'],
        ];

        foreach ($campuses as $campus) {
            Campus::create($campus);
        }
    }
}