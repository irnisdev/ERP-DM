<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\SalesTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'transaction_type' => Payment::SOURCE_SALES,
            'transaction_id' => SalesTransaction::factory(),
            'payment_method_id' => PaymentMethod::firstOrCreate(
                ['code' => 'CASH'],
                ['name' => 'Tunai', 'method_type' => 'CASH', 'status' => 'ACTIVE']
            )->id,
            'financial_account_id' => null,
            'amount' => 100000,
            'payment_date' => now(),
            'status' => Payment::STATUS_COMPLETED,
        ];
    }
}
