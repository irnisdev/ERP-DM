<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
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
        'sku',
        'barcode',
        'name',
        'category_id',
        'brand_id',
        'unit_id',
        'purchase_price',
        'default_sell_price',
        'minimum_stock',
        'status',
        ];
    }

    protected $fillable = [
        'sku',
        'barcode',
        'category_id',
        'brand_id',
        'unit_id',
        'name',
        'description',
        'purchase_price',
        'default_sell_price',
        'minimum_stock',
        'maximum_stock',
        'weight',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'default_sell_price' => 'decimal:2',
            'minimum_stock' => 'decimal:4',
            'maximum_stock' => 'decimal:4',
            'weight' => 'decimal:4',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function salesItems(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class);
    }

    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable');
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereHas('stocks', function (Builder $q) {
            $q->whereColumn('inventory_stocks.quantity', '<', 'products.minimum_stock');
        });
    }

    /**
     * Harga efektif pada tanggal tertentu. Harga master berubah, transaksi lama
     * tidak berubah karena transaksi menyimpan snapshot unit_price.
     */
    public function priceFor(string $priceType = 'RETAIL', ?string $date = null): ?ProductPrice
    {
        $date ??= now()->toDateString();

        return $this->prices()
            ->where('price_type', $priceType)
            ->whereDate('effective_date', '<=', $date)
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->orderByDesc('effective_date')
            ->first();
    }
}