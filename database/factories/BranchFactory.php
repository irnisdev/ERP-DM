<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    protected $model = \App\Models\Branch::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->city().' '.Str::upper(Str::random(3));

        return [
            'company_id' => Company::factory(),
            'code' => Str::upper(Str::random(4)),
            'name' => $name,
            'branch_type' => 'BRANCH',
            'phone' => $this->faker->numerify('021-#######'),
            'email' => Str::slug($name, '.').'@example.com',
            'address' => $this->faker->address(),
            'status' => 'ACTIVE',
        ];
    }

    public function central(): static
    {
        return $this->state(fn () => ['branch_type' => 'CENTRAL']);
    }
}