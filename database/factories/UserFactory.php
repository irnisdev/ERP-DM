<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'employee_id' => null,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'status' => 'ACTIVE',
            'last_login_at' => null,
        ];
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = \App\Models\Role::firstOrCreate(
                ['name' => 'SUPER_ADMIN', 'guard_name' => 'web'],
                ['description' => 'Administrator sistem']
            );

            $user->roles()->syncWithoutDetaching([$role->id]);
        });
    }

    public function forBranch(\App\Models\Branch $branch): static
    {
        return $this->state(fn () => [
            'employee_id' => \App\Models\Employee::factory()->create(['branch_id' => $branch->id])->id,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}