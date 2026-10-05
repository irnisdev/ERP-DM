<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'DIANMUSTIKA')->firstOrFail();

        $departments = [
            ['code' => 'OPS', 'name' => 'Operasional'],
            ['code' => 'TREATMENT', 'name' => 'Treatment'],
            ['code' => 'SALES', 'name' => 'Sales'],
            ['code' => 'FINANCE', 'name' => 'Finance'],
            ['code' => 'HR', 'name' => 'Human Resource'],
            ['code' => 'GA', 'name' => 'General Administration'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['company_id' => $company->id, 'code' => $department['code']],
                ['name' => $department['name'], 'status' => 'ACTIVE']
            );
        }
    }
}