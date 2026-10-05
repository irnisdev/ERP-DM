<?php

namespace App\Models;

use App\Models\Concerns\HasBranch;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasCreator;
    use HasBranch;
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
        'booking_number',
        'customer_id',
        'branch_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * Booking cancelled/no-show tidak boleh menghasilkan pendapatan.
     */
    public function generatesRevenue(): bool
    {
        return ! in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_NO_SHOW], true);
    }
}