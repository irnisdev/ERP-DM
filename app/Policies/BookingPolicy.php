<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('booking.view');
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->can('booking.view') && $this->sameBranch($user, $booking);
    }

    public function create(User $user): bool
    {
        return $user->can('booking.manage');
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->can('booking.manage')
            && $this->sameBranch($user, $booking)
            && ! $this->isLocked($booking);
    }

    /**
     * Perubahan status (confirm, check-in, treatment, completed, cancel, no-show).
     */
    public function transition(User $user, Booking $booking): bool
    {
        return $user->can('booking.manage') && $this->sameBranch($user, $booking);
    }

    /**
     * Booking yang sudah selesai atau dibatalkan tidak bisa diedit.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return $user->can('booking.manage') && ! $this->isLocked($booking);
    }

    public function createSalesFrom(User $user, Booking $booking): bool
    {
        return $user->can('sales.create')
            && $this->sameBranch($user, $booking)
            && $booking->generatesRevenue();
    }

    private function isLocked(Booking $booking): bool
    {
        return in_array($booking->status, [
            Booking::STATUS_COMPLETED,
            Booking::STATUS_CANCELLED,
            Booking::STATUS_NO_SHOW,
        ], true);
    }

    private function sameBranch(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->employee !== null
            && $user->employee->branch_id === $booking->branch_id;
    }
}
