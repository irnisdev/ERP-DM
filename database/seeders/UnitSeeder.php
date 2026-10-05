<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'PCS', 'name' => 'Satuan Bijian', 'decimal_scale' => 0],
            ['code' => 'BOX', 'name' => 'Box', 'decimal_scale' => 0],
            ['code' => 'BTL', 'name' => 'Botol', 'decimal_scale' => 0],
            ['code' => 'TUB', 'name' => 'Tube', 'decimal_scale' => 0],
            ['code' => 'KG', 'name' => 'Kilogram', 'decimal_scale' => 3],
            ['code' => 'LTR', 'name' => 'Liter', 'decimal_scale' => 2],
            ['code' => 'ML', 'name' => 'Mililiter', 'decimal_scale' => 1],
            ['code' => 'PACK', 'name' => 'Pak', 'decimal_scale' => 0],
            ['code' => 'SET', 'name' => 'Set', 'decimal_scale' => 0],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'decimal_scale' => $unit['decimal_scale'],
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}