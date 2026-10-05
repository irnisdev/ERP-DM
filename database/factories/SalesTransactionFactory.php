<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\SalesTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SalesTransaction>
 */
class SalesTransactionFactory extends Factory
{
    protected $model = SalesTransaction::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'branch_id' => Branch::factory(),
            'customer_id' => null,
            'cashier_id' => null,
            'transaction_date' => now(),
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'grand_total' => 0,
            'status' => SalesTransaction::STATUS_DRAFT,
        ];
    }
}
