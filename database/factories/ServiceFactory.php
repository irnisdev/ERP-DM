<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'service_code' => 'SRV-'.Str::upper(Str::random(6)),
            'category_id' => ServiceCategory::factory(),
            'name' => $this->faker->words(2, true).' Treatment',
            'description' => $this->faker->sentence(),
            'pricing_method' => Service::PRICING_FIXED,
            'default_duration_minutes' => 60,
            'status' => 'ACTIVE',
        ];
    }

    public function duration(): static
    {
        return $this->state(fn () => [
            'pricing_method' => Service::PRICING_DURATION,
            'default_duration_minutes' => 60,
        ]);
    }

    public function weight(): static
    {
        return $this->state(fn () => ['pricing_method' => Service::PRICING_WEIGHT]);
    }
}