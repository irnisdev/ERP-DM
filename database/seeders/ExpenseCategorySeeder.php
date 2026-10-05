<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'OPERASIONAL' => 'Operasional',
            'GA' => 'General Administration',
            'UTILITAS' => 'Utilitas',
            'GAJI' => 'Gaji & Tunjangan',
            'MARKETING' => 'Marketing',
            'MAINTENANCE' => 'Maintenance',
            'TRANSPORT' => 'Transportasi',
            'ATURAN' => 'Pajak & Aturan',
        ];

        $ids = [];

        foreach ($categories as $code => $name) {
            $ids[$code] = ExpenseCategory::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'status' => 'ACTIVE']
            )->id;
        }

        $children = [
            'GA-SUP' => ['name' => 'Perlengkapan Kantor', 'parent' => 'GA'],
            'GA-COM' => ['name' => 'Komunikasi & Internet', 'parent' => 'GA'],
            'OPS-CLEAN' => ['name' => 'Kebersihan', 'parent' => 'OPERASIONAL'],
            'OPS-CONS' => ['name' => 'Consumable Treatment', 'parent' => 'OPERASIONAL'],
            'ATRN-PBB' => ['name' => 'PBB', 'parent' => 'ATURAN'],
            'ATRN-PAJAK' => ['name' => 'Pajak Penghasilan', 'parent' => 'ATURAN'],
        ];

        foreach ($children as $code => $config) {
            ExpenseCategory::updateOrCreate(
                ['code' => $code],
                ['name' => $config['name'], 'parent_id' => $ids[$config['parent']], 'status' => 'ACTIVE']
            );
        }
    }
}