<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesTransactionItem extends Model
{
    use HasFactory;

    public const TYPE_PRODUCT = 'PRODUCT';

    public const TYPE_SERVICE = 'SERVICE';

    public const TYPE_PACKAGE = 'PACKAGE';

    protected $fillable = [
        'sales_transaction_id',
        'item_type',
        'product_id',
        'service_id',
        'package_id',
        'item_name',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
        'employee_id',
        'source_booking_item_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

    public function sourceBookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class, 'source_booking_item_id');
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class, 'sales_transaction_item_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(EmployeeCommission::class, 'sales_transaction_item_id');
    }

    /**
     * Aturan item_type → kolom referensi wajib (PRD 01 §14).
     */
    public function validateReference(): void
    {
        $map = [
            self::TYPE_PRODUCT => 'product_id',
            self::TYPE_SERVICE => 'service_id',
            self::TYPE_PACKAGE => 'package_id',
        ];

        if (! isset($map[$this->item_type])) {
            throw new \InvalidArgumentException("item_type tidak valid: {$this->item_type}");
        }

        if (! $this->{$map[$this->item_type]}) {
            throw new \InvalidArgumentException("{$map[$this->item_type]} wajib untuk item_type {$this->item_type}");
        }
    }
}