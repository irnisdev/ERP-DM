<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'OPS' => [
                ['code' => 'OPS-MGR', 'name' => 'Operational Manager'],
                ['code' => 'ADM', 'name' => 'Admin Operasional'],
            ],
            'TREATMENT' => [
                ['code' => 'THERAPIST', 'name' => 'Therapist'],
                ['code' => 'THERAPIST-SR', 'name' => 'Senior Therapist'],
            ],
            'SALES' => [
                ['code' => 'CASHIER', 'name' => 'Kasir'],
                ['code' => 'SPV-SALES', 'name' => 'Supervisor Sales'],
            ],
            'FINANCE' => [
                ['code' => 'ACCOUNTING', 'name' => 'Accounting'],
                ['code' => 'FIN-MGR', 'name' => 'Finance Manager'],
            ],
            'HR' => [
                ['code' => 'HR-STAFF', 'name' => 'HR Staff'],
                ['code' => 'PAYROLL', 'name' => 'Payroll Officer'],
            ],
            'GA' => [
                ['code' => 'GA-STAFF', 'name' => 'Administered'],
                ['code' => 'OWNER', 'name' => 'Owner'],
            ],
        ];

        foreach ($map as $departmentCode => $positions) {
            $department = Department::where('code', $departmentCode)->firstOrFail();

            foreach ($positions as $position) {
                Position::updateOrCreate(
                    ['department_id' => $department->id, 'code' => $position['code']],
                    ['name' => $position['name'], 'status' => 'ACTIVE']
                );
            }
        }
    }
}