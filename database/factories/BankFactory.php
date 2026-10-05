<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Bank>
 */
class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(4)),
            'name' => 'Bank '.$this->faker->company(),
            'status' => 'ACTIVE',
        ];
    }
}
