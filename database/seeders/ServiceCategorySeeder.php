<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'MASSAGE' => 'Massage',
            'FACIAL' => 'Facial Treatment',
            'BODY' => 'Body Treatment',
            'LASER' => 'Laser & Light',
            'SLIMMING' => 'Slimming',
            'PEDICURE' => 'Pedicure & Manicure',
            'SPA' => 'Spa & Relaxation',
        ];

        foreach ($categories as $code => $name) {
            ServiceCategory::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'parent_id' => null, 'status' => 'ACTIVE']
            );
        }
    }
}