<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'employee_code' => 'EMP-'.Str::upper(Str::random(6)),
            'nik' => $this->faker->numerify('3273##########'),
            'full_name' => $this->faker->name(),
            'birth_place' => $this->faker->city(),
            'birth_date' => $this->faker->dateTimeBetween('-40 years', '-22 years'),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'phone' => $this->faker->numerify('08##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'marital_status' => $this->faker->randomElement(['SINGLE', 'MARRIED']),
            'employment_status' => 'ACTIVE',
            'join_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'branch_id' => \App\Models\Branch::factory(),
            'department_id' => \App\Models\Department::factory(),
            'position_id' => \App\Models\Position::factory(),
            'status' => 'ACTIVE',
        ];
    }

    public function resigned(): static
    {
        return $this->state(fn () => [
            'employment_status' => 'RESIGNED',
            'resign_date' => now()->subMonths(3)->toDateString(),
        ]);
    }
}