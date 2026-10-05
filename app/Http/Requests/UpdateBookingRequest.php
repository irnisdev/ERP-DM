<?php

namespace App\Http\Requests;

use App\Models\Booking;

class UpdateBookingRequest extends StoreBookingRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('booking')) ?? false;
    }
}
