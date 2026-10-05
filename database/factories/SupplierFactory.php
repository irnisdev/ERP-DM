<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'supplier_code' => 'SUP-'.Str::upper(Str::random(6)),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'phone' => $this->faker->numerify('021-#######'),
            'email' => $this->faker->unique()->companyEmail(),
            'address' => $this->faker->address(),
            'tax_number' => $this->faker->numerify('##.###.###.#-###.###'),
            'payment_terms_days' => 30,
            'status' => 'ACTIVE',
        ];
    }
}
