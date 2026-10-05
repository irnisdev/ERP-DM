<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['code' => 'DM', 'name' => 'Dian Mustika'],
            ['code' => 'NEUTRUGINA', 'name' => 'Nutr'],
            ['code' => 'BIORE', 'name' => 'Biore'],
            ['code' => 'SENSODYNE', 'name' => 'Sensodyne'],
            ['code' => 'LION', 'name' => 'Lion'],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['code' => $brand['code']],
                ['name' => $brand['name'], 'status' => 'ACTIVE']
            );
        }
    }
}