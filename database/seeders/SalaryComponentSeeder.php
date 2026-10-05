<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

/**
 * PRD 01 §28: BASIC_SALARY, ALLOWANCE, BONUS, COMMISSION, BPJS, DEDUCTION.
 * Jenis komponen dapat ditambah tanpa mengubah kode (PRD 02 §19).
 */
class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['code' => 'BASIC_SALARY', 'name' => 'Gaji Pokok', 'type' => 'EARNING', 'method' => 'FIXED', 'taxable' => true, 'bpjs' => false],
            ['code' => 'ALLOWANCE', 'name' => 'Tunjangan', 'type' => 'EARNING', 'method' => 'FIXED', 'taxable' => true, 'bpjs' => true],
            ['code' => 'TRANSPORT_ALLOWANCE', 'name' => 'Tunjangan Transport', 'type' => 'EARNING', 'method' => 'FIXED', 'taxable' => true, 'bpjs' => false],
            ['code' => 'MEAL_ALLOWANCE', 'name' => 'Tunjangan Makan', 'type' => 'EARNING', 'method' => 'PER_DAY', 'taxable' => false, 'bpjs' => false],
            ['code' => 'BONUS', 'name' => 'Bonus', 'type' => 'EARNING', 'method' => 'FIXED', 'taxable' => true, 'bpjs' => false],
            ['code' => 'COMMISSION', 'name' => 'Komisi', 'type' => 'EARNING', 'method' => 'FORMULA', 'taxable' => true, 'bpjs' => false],
            ['code' => 'OVERTIME', 'name' => 'Lembur', 'type' => 'EARNING', 'method' => 'PER_HOUR', 'taxable' => true, 'bpjs' => false],
            ['code' => 'THR', 'name' => 'THR', 'type' => 'EARNING', 'method' => 'FORMULA', 'taxable' => true, 'bpjs' => false],
            ['code' => 'BPJS_KES_EMPLOYEE', 'name' => 'BPJS Kesehatan (Karyawan)', 'type' => 'DEDUCTION', 'method' => 'PERCENTAGE', 'taxable' => false, 'bpjs' => true],
            ['code' => 'BPJS_KETENAGAKERJAAN', 'name' => 'BPJS Ketenagakerjaan', 'type' => 'DEDUCTION', 'method' => 'PERCENTAGE', 'taxable' => false, 'bpjs' => true],
            ['code' => 'PPH21', 'name' => 'PPh 21', 'type' => 'DEDUCTION', 'method' => 'FORMULA', 'taxable' => false, 'bpjs' => false],
            ['code' => 'DEDUCTION', 'name' => 'Potongan Lain', 'type' => 'DEDUCTION', 'method' => 'FIXED', 'taxable' => false, 'bpjs' => false],
        ];

        foreach ($components as $component) {
            SalaryComponent::updateOrCreate(
                ['code' => $component['code']],
                [
                    'name' => $component['name'],
                    'type' => $component['type'],
                    'calculation_method' => $component['method'],
                    'is_taxable' => $component['taxable'],
                    'is_bpjs' => $component['bpjs'],
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}