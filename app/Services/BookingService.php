<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * BookingService — lifecycle booking treatment.
 *
 * PRD 02 §11:
 *   Booking → Confirmation → Check-in → Treatment → Completed
 *           → Sales Invoice → Payment
 *
 * PRD 02 §11: booking cancelled/no-show tidak boleh menghasilkan pendapatan
 * kecuali ada aturan bisnis khusus.
 */
class BookingService
{
    /**
     * Transisi status yang sah.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        Booking::STATUS_BOOKED => [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_CANCELLED,
            Booking::STATUS_NO_SHOW,
        ],
        Booking::STATUS_CONFIRMED => [
            Booking::STATUS_CHECKED_IN,
            Booking::STATUS_CANCELLED,
            Booking::STATUS_NO_SHOW,
        ],
        Booking::STATUS_CHECKED_IN => [
            Booking::STATUS_IN_TREATMENT,
            Booking::STATUS_CANCELLED,
        ],
        Booking::STATUS_IN_TREATMENT => [
            Booking::STATUS_COMPLETED,
            Booking::STATUS_CANCELLED,
        ],
        Booking::STATUS_COMPLETED => [],
        Booking::STATUS_CANCELLED => [],
        Booking::STATUS_NO_SHOW => [],
    ];

    /**
     * @return array<int, string>
     */
    public function allowedTransitions(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public function canTransition(Booking $booking, string $to): bool
    {
        return in_array($to, $this->allowedTransitions($booking->status), true);
    }

    /**
     * Pindah status booking beserta seluruh itemnya.
     */
    public function transition(Booking $booking, string $to, ?string $reason = null): Booking
    {
        if ($booking->status === $to) {
            return $booking;
        }

        if (! $this->canTransition($booking, $to)) {
            throw ValidationException::withMessages([
                'status' => "Status {$booking->status} tidak bisa diubah menjadi {$to}.",
            ]);
        }

        return DB::transaction(function () use ($booking, $to, $reason) {
            $booking->items()->update(['status' => $to]);

            $attributes = ['status' => $to];

            if (in_array($to, [Booking::STATUS_CANCELLED, Booking::STATUS_NO_SHOW], true) && $reason !== null) {
                $attributes['notes'] = trim(($booking->notes ?? '')."\n{$to}: {$reason}");
            }

            $booking->update($attributes);

            return $booking->fresh();
        });
    }

    public function confirm(Booking $booking): Booking
    {
        return $this->transition($booking, Booking::STATUS_CONFIRMED);
    }

    public function checkIn(Booking $booking): Booking
    {
        return $this->transition($booking, Booking::STATUS_CHECKED_IN);
    }

    public function startTreatment(Booking $booking): Booking
    {
        return $this->transition($booking, Booking::STATUS_IN_TREATMENT);
    }

    public function complete(Booking $booking): Booking
    {
        return $this->transition($booking, Booking::STATUS_COMPLETED);
    }

    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        return $this->transition($booking, Booking::STATUS_CANCELLED, $reason);
    }

    public function noShow(Booking $booking, ?string $reason = null): Booking
    {
        return $this->transition($booking, Booking::STATUS_NO_SHOW, $reason);
    }

    /**
     * Booking item siap dijadikan baris cart POS.
     *
     * Hanya untuk booking yang boleh menghasilkan pendapatan dan item yang
     * sudah Completed.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toCartLines(Booking $booking): array
    {
        if (! $booking->generatesRevenue()) {
            throw ValidationException::withMessages([
                'booking' => "Booking {$booking->status} tidak boleh menghasilkan pendapatan.",
            ]);
        }

        $lines = [];

        foreach ($booking->items()->where('status', BookingItem::STATUS_COMPLETED)->get() as $item) {
            if ($item->service_id) {
                $lines[] = [
                    'item_type' => \App\Models\SalesTransactionItem::TYPE_SERVICE,
                    'service_id' => $item->service_id,
                    'quantity' => 1,
                    'employee_id' => $item->employee_id,
                    'source_booking_item_id' => $item->id,
                    'duration_minutes' => $item->service?->default_duration_minutes,
                ];
            }

            if ($item->package_id) {
                $lines[] = [
                    'item_type' => \App\Models\SalesTransactionItem::TYPE_PACKAGE,
                    'package_id' => $item->package_id,
                    'quantity' => 1,
                    'employee_id' => $item->employee_id,
                    'source_booking_item_id' => $item->id,
                ];
            }
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'booking' => 'Belum ada item booking yang selesai dan bisa dijual.',
            ]);
        }

        return $lines;
    }

    public function nextBookingNumber(): string
    {
        do {
            $number = 'BKG-'.now()->format('Ymd').'-'.str_pad(
                (string) (Booking::query()
                    ->whereDate('booking_date', now()->toDateString())
                    ->count() + 1),
                3,
                '0',
                STR_PAD_LEFT
            );
        } while (Booking::where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * Memeriksa bentrok jadwal therapist.
     *
     * Satu therapist tidak bisa punya dua booking yang overlap pada waktu
     * yang sama. Booking CANCELLED dan NO_SHOW diabaikan karena tidak
     * memakai jadwal.
     *
     * @param  string  $start  format H:i:s
     * @param  string  $end    format H:i:s
     */
    public function assertTherapistAvailable(
        int $employeeId,
        string $date,
        string $start,
        string $end,
        ?int $ignoreBookingId = null
    ): void {
        if ($start === '' || $end === '') {
            return;
        }

        $startsAt = $date.' '.$start;
        $endsAt = $date.' '.$end;

        if ($endsAt <= $startsAt) {
            $endsAt = date('Y-m-d H:i:s', strtotime($endsAt.' +1 day'));
        }

        $conflict = BookingItem::query()
            ->where('employee_id', $employeeId)
            ->whereHas('booking', fn ($q) => $q
                ->whereDate('booking_date', $date)
                ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_NO_SHOW])
                ->when($ignoreBookingId, fn ($q) => $q->whereKeyNot($ignoreBookingId)))
            ->where('scheduled_start', '<', $endsAt)
            ->where('scheduled_end', '>', $startsAt)
            ->exists();

        if ($conflict) {
            $employee = \App\Models\Employee::find($employeeId);

            throw ValidationException::withMessages([
                'scheduled_start' => 'Jadwal therapist '.($employee?->full_name ?? $employeeId).' sudah terisi.',
            ]);
        }
    }
}
