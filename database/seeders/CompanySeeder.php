<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(
            ['code' => 'DIANMUSTIKA'],
            [
                'name' => 'Dian Mustika',
                'legal_name' => 'PT Dian Mustika',
                'tax_number' => null,
                'phone' => '021-1234567',
                'email' => 'info@dianmustika.co.id',
                'address' => 'Jakarta, Indonesia',
                'status' => 'ACTIVE',
            ]
        );
    }
}