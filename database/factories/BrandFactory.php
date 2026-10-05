<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(4)),
            'name' => $this->faker->words(2, true),
            'status' => 'ACTIVE',
        ];
    }
}
