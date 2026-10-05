<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePackage extends Model
{
    use Auditable;
    use HasFactory;
    use HasStatus;
    use SoftDeletes;

    /**
     * Atribut yang direkam di audit log (PRD 02 §27).
     *
     * @return array<int, string>
     */
    protected function auditAttributes(): array
    {
        return [
        'code',
        'name',
        'price',
        'valid_from',
        'valid_until',
        'status',
        ];
    }

    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'valid_from',
        'valid_until',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'service_package_items',
            'package_id',
            'service_id'
        )->withPivot('quantity')->withTimestamps();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServicePackageItem::class, 'package_id');
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class, 'package_id');
    }

    public function salesItems(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class, 'package_id');
    }
}