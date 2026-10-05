<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_code' => 'CUST-'.Str::upper(Str::random(6)),
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'birth_date' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'phone' => $this->faker->numerify('08##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'customer_type' => $this->faker->randomElement(['RETAIL', 'MEMBER', 'CLINIC']),
            'source' => $this->faker->randomElement(['WALK_IN', 'INSTAGRAM', 'REFERRAL', 'GOOGLE']),
            'status' => 'ACTIVE',
        ];
    }
}