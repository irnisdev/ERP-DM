<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'booking_number' => 'BKG-'.now()->format('Ymd').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 99999), 3, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'branch_id' => Branch::factory(),
            'booking_date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Booking::STATUS_BOOKED,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => Booking::STATUS_CONFIRMED]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => Booking::STATUS_COMPLETED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => Booking::STATUS_CANCELLED]);
    }
}
