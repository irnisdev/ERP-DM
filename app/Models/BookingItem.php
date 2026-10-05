<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    use HasFactory;
    use HasStatus;

    public const STATUS_BOOKED = 'BOOKED';

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_CHECKED_IN = 'CHECKED_IN';

    public const STATUS_IN_TREATMENT = 'IN_TREATMENT';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_NO_SHOW = 'NO_SHOW';

    protected $fillable = [
        'booking_id',
        'service_id',
        'package_id',
        'employee_id',
        'scheduled_start',
        'scheduled_end',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salesItems()
    {
        return $this->hasMany(SalesTransactionItem::class, 'source_booking_item_id');
    }
}