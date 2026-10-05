<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\SalesTransactionItem;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 02 §11 — Booking workflow dan aturan pendapatan.
 */
class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    private function booking(string $status = Booking::STATUS_BOOKED, ?Customer $customer = null): Booking
    {
        return Booking::factory()->create([
            'status' => $status,
            'customer_id' => $customer?->id ?? Customer::factory()->create()->id,
        ]);
    }

    // ---- Status transitions

    #[Test]
    public function happy_path_follows_workflow(): void
    {
        $booking = $this->booking();

        $booking = $this->service()->confirm($booking);
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->status);

        $booking = $this->service()->checkIn($booking);
        $this->assertSame(Booking::STATUS_CHECKED_IN, $booking->status);

        $booking = $this->service()->startTreatment($booking);
        $this->assertSame(Booking::STATUS_IN_TREATMENT, $booking->status);

        $booking = $this->service()->complete($booking);
        $this->assertSame(Booking::STATUS_COMPLETED, $booking->status);
    }

    #[Test]
    public function cannot_skip_from_booked_to_completed(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service()->complete($this->booking());
    }

    #[Test]
    public function cannot_reopen_completed_booking(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service()->confirm($this->booking(Booking::STATUS_COMPLETED));
    }

    #[Test]
    public function cancelled_booking_is_terminal(): void
    {
        $cancelled = $this->service()->cancel($this->booking());

        $this->assertSame(Booking::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame([], $this->service()->allowedTransitions(Booking::STATUS_CANCELLED));

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->confirm($cancelled);
    }

    #[Test]
    public function transitioning_to_same_status_is_noop(): void
    {
        $booking = $this->booking(Booking::STATUS_CONFIRMED);

        $this->assertSame($booking->id, $this->service()->transition($booking, Booking::STATUS_CONFIRMED)->id);
    }

    #[Test]
    public function transition_updates_all_booking_items(): void
    {
        $booking = $this->booking();
        $itemA = BookingItem::factory()->create([
            'booking_id' => $booking->id,
            'status' => BookingItem::STATUS_BOOKED,
        ]);
        $itemB = BookingItem::factory()->create([
            'booking_id' => $booking->id,
            'status' => BookingItem::STATUS_BOOKED,
        ]);

        $this->service()->confirm($booking);

        $this->assertSame(BookingItem::STATUS_CONFIRMED, $itemA->fresh()->status);
        $this->assertSame(BookingItem::STATUS_CONFIRMED, $itemB->fresh()->status);
    }

    #[Test]
    public function cancel_records_reason(): void
    {
        $booking = $this->booking();
        $this->service()->cancel($booking, 'Customer membatalkan');

        $this->assertStringContainsString('Customer membatalkan', (string) $booking->fresh()->notes);
    }

    // ---- Revenue gate (PRD 02 §11)

    #[Test]
    public function cancelled_booking_generates_no_revenue(): void
    {
        $booking = $this->booking(Booking::STATUS_CANCELLED);
        $this->assertFalse($booking->generatesRevenue());
    }

    #[Test]
    public function no_show_booking_generates_no_revenue(): void
    {
        $booking = $this->booking(Booking::STATUS_NO_SHOW);
        $this->assertFalse($booking->generatesRevenue());
    }

    #[Test]
    public function completed_booking_generates_revenue(): void
    {
        $this->assertTrue($this->booking(Booking::STATUS_COMPLETED)->generatesRevenue());
    }

    #[Test]
    public function to_cart_lines_rejects_cancelled_booking(): void
    {
        $booking = $this->booking(Booking::STATUS_CANCELLED);
        $booking->items()->create([
            'service_id' => Service::factory()->create()->id,
            'status' => BookingItem::STATUS_COMPLETED,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service()->toCartLines($booking);
    }

    #[Test]
    public function to_cart_lines_only_includes_completed_items(): void
    {
        $service = Service::factory()->create();
        $booking = $this->booking(Booking::STATUS_COMPLETED);

        $booking->items()->create([
            'service_id' => $service->id,
            'status' => BookingItem::STATUS_COMPLETED,
        ]);
        $booking->items()->create([
            'service_id' => Service::factory()->create()->id,
            'status' => BookingItem::STATUS_BOOKED,
        ]);

        $lines = $this->service()->toCartLines($booking);

        $this->assertCount(1, $lines);
        $this->assertSame($service->id, $lines[0]['service_id']);
    }

    #[Test]
    public function to_cart_lines_supports_package(): void
    {
        $package = ServicePackage::factory()->create();
        $booking = $this->booking(Booking::STATUS_COMPLETED);

        $booking->items()->create([
            'package_id' => $package->id,
            'status' => BookingItem::STATUS_COMPLETED,
        ]);

        $lines = $this->service()->toCartLines($booking);

        $this->assertSame(SalesTransactionItem::TYPE_PACKAGE, $lines[0]['item_type']);
        $this->assertSame($package->id, $lines[0]['package_id']);
    }

    #[Test]
    public function to_cart_lines_carries_therapist_and_booking_item_link(): void
    {
        $therapist = Employee::factory()->create();
        $booking = $this->booking(Booking::STATUS_COMPLETED);
        $item = $booking->items()->create([
            'service_id' => Service::factory()->create()->id,
            'employee_id' => $therapist->id,
            'status' => BookingItem::STATUS_COMPLETED,
        ]);

        $lines = $this->service()->toCartLines($booking);

        $this->assertSame($therapist->id, $lines[0]['employee_id']);
        $this->assertSame($item->id, $lines[0]['source_booking_item_id']);
    }

    #[Test]
    public function to_cart_lines_rejects_when_no_item_completed(): void
    {
        $booking = $this->booking(Booking::STATUS_COMPLETED);
        $booking->items()->create([
            'service_id' => Service::factory()->create()->id,
            'status' => BookingItem::STATUS_BOOKED,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service()->toCartLines($booking);
    }

    // ---- Therapist schedule conflict

    #[Test]
    public function overlapping_therapist_schedule_is_rejected(): void
    {
        $therapist = Employee::factory()->create();
        $service = Service::factory()->create();
        $date = now()->addDay()->toDateString();

        $booking = Booking::factory()->create(['booking_date' => $date]);
        $booking->items()->create([
            'service_id' => $service->id,
            'employee_id' => $therapist->id,
            'scheduled_start' => $date.' 10:00:00',
            'scheduled_end' => $date.' 11:00:00',
            'status' => BookingItem::STATUS_CONFIRMED,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service()->assertTherapistAvailable($therapist->id, $date, '10:30:00', '11:30:00');
    }

    #[Test]
    public function adjacent_therapist_schedule_is_allowed(): void
    {
        $therapist = Employee::factory()->create();
        $date = now()->addDay()->toDateString();

        $booking = Booking::factory()->create(['booking_date' => $date]);
        $booking->items()->create([
            'service_id' => Service::factory()->create()->id,
            'employee_id' => $therapist->id,
            'scheduled_start' => $date.' 10:00:00',
            'scheduled_end' => $date.' 11:00:00',
            'status' => BookingItem::STATUS_CONFIRMED,
        ]);

        $this->service()->assertTherapistAvailable($therapist->id, $date, '11:00:00', '12:00:00');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function cancelled_booking_does_not_block_schedule(): void
    {
        $therapist = Employee::factory()->create();
        $date = now()->addDay()->toDateString();

        $booking = Booking::factory()->create([
            'booking_date' => $date,
            'status' => Booking::STATUS_CANCELLED,
        ]);
        $booking->items()->create([
            'service_id' => Service::factory()->create()->id,
            'employee_id' => $therapist->id,
            'scheduled_start' => $date.' 10:00:00',
            'scheduled_end' => $date.' 11:00:00',
            'status' => BookingItem::STATUS_CANCELLED,
        ]);

        $this->service()->assertTherapistAvailable($therapist->id, $date, '10:00:00', '11:00:00');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function overnight_schedule_is_handled(): void
    {
        $therapist = Employee::factory()->create();
        $date = now()->addDay()->toDateString();

        $booking = Booking::factory()->create(['booking_date' => $date]);
        $booking->items()->create([
            'service_id' => Service::factory()->create()->id,
            'employee_id' => $therapist->id,
            'scheduled_start' => $date.' 22:00:00',
            'scheduled_end' => $date.' 23:30:00',
            'status' => BookingItem::STATUS_CONFIRMED,
        ]);

        // 23:00–23:45 masih di dalam 22:00–23:30 → bentrok
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->assertTherapistAvailable($therapist->id, $date, '23:00:00', '23:45:00');
    }

    #[Test]
    public function booking_number_is_unique_per_day(): void
    {
        $numbers = [];

        for ($i = 0; $i < 3; $i++) {
            $numbers[] = $this->service()->nextBookingNumber();
            Booking::factory()->create(['booking_number' => end($numbers)]);
        }

        $this->assertCount(3, array_unique($numbers));
    }
}