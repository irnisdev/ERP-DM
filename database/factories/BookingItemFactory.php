<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BookingItem>
 */
class BookingItemFactory extends Factory
{
    protected $model = BookingItem::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'service_id' => Service::factory(),
            'employee_id' => Employee::factory(),
            'scheduled_start' => now()->addHour(),
            'scheduled_end' => now()->addHours(2),
            'status' => BookingItem::STATUS_BOOKED,
        ];
    }
}
