<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            // UserSeeder::class,
            CampusSeeder::class,
            CriteriaSeeder::class,
            SubcriteriaSeeder::class,
            BoardingHouseSeeder::class,
        ]);
    }
}