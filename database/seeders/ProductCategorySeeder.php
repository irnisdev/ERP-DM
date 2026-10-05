<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'OIL' => ['name' => 'Minyak & Serum', 'parent' => null],
            'CREAM' => ['name' => 'Krim & Lotion', 'parent' => null],
            'CLEANSER' => ['name' => 'Cleanser', 'parent' => null],
            'MASK' => ['name' => 'Masker', 'parent' => null],
            'SUPPLEMENT' => ['name' => 'Suplemen', 'parent' => null],
            'EQUIPMENT' => ['name' => 'Alat Treatment', 'parent' => null],
            'CARE' => ['name' => 'Perawatan', 'parent' => null],
        ];

        foreach ($categories as $code => $config) {
            ProductCategory::updateOrCreate(
                ['code' => $code],
                ['name' => $config['name'], 'parent_id' => null, 'status' => 'ACTIVE']
            );
        }
    }
}