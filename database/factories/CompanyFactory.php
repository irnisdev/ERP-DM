<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'code' => Str::upper(Str::random(4)),
            'name' => $name,
            'legal_name' => 'PT '.$name,
            'tax_number' => $this->faker->numerify('##.###.###.#-###.###'),
            'phone' => $this->faker->numerify('021-#######'),
            'email' => $this->faker->unique()->companyEmail(),
            'address' => $this->faker->address(),
            'status' => 'ACTIVE',
        ];
    }
}