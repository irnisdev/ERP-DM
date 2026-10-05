<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'DIANMUSTIKA')->firstOrFail();

        $branches = [
            [
                'code' => 'PUSAT',
                'name' => 'Klinik Pusat',
                'branch_type' => 'CENTRAL',
                'address' => 'Jakarta Pusat',
            ],
            [
                'code' => 'JKT-SEL',
                'name' => 'Klinik Jakarta Selatan',
                'branch_type' => 'BRANCH',
                'address' => 'Jakarta Selatan',
            ],
            [
                'code' => 'BDG',
                'name' => 'Klinik Bandung',
                'branch_type' => 'BRANCH',
                'address' => 'Bandung, Jawa Barat',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['company_id' => $company->id, 'code' => $branch['code']],
                [
                    'name' => $branch['name'],
                    'branch_type' => $branch['branch_type'],
                    'phone' => '021-1234567',
                    'email' => strtolower($branch['code']).'@dianmustika.co.id',
                    'address' => $branch['address'],
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}