<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use Auditable;
    use HasFactory;
    use HasStatus;
    use SoftDeletes;

    public const PRICING_FIXED = 'FIXED';

    public const PRICING_DURATION = 'DURATION';

    public const PRICING_WEIGHT = 'WEIGHT';

    /**
     * Atribut yang direkam di audit log (PRD 02 §27).
     *
     * @return array<int, string>
     */
    protected function auditAttributes(): array
    {
        return [
        'service_code',
        'name',
        'category_id',
        'pricing_method',
        'default_duration_minutes',
        'status',
        ];
    }

    protected $fillable = [
        'service_code',
        'category_id',
        'name',
        'description',
        'pricing_method',
        'default_duration_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'default_duration_minutes' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class);
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(
            ServicePackage::class,
            'service_package_items',
            'service_id',
            'package_id'
        )->withPivot('quantity')->withTimestamps();
    }

    public function commissionRules(): HasMany
    {
        return $this->hasMany(CommissionRule::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function salesItems(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class);
    }
}