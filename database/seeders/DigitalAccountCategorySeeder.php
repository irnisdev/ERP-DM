<?php

namespace Database\Seeders;

use App\Models\DigitalAccountCategory;
use Illuminate\Database\Seeder;

class DigitalAccountCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'SOCIAL' => 'Social Media',
            'EMAIL' => 'Email',
            'MARKETPLACE' => 'Marketplace',
            'DOMAIN' => 'Domain & Hosting',
            'PAYMENT' => 'Payment Gateway',
            'SOFTWARE' => 'Software & Tools',
            'SOCIAL_TOOLS' => 'Social Media Tool',
        ];

        foreach ($categories as $code => $name) {
            DigitalAccountCategory::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'status' => 'ACTIVE']
            );
        }
    }
}