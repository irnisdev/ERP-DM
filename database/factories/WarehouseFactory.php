<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => 'WH-'.Str::upper(Str::random(4)),
            'name' => 'Gudang '.$this->faker->city(),
            'warehouse_type' => 'STORAGE',
            'status' => 'ACTIVE',
        ];
    }

    public function display(): static
    {
        return $this->state(fn () => ['warehouse_type' => 'DISPLAY']);
    }
}
