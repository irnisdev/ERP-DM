<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasCreator;
    use HasFactory;

    public const TYPE_PURCHASE = 'PURCHASE';

    public const TYPE_SALE = 'SALE';

    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';

    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';

    public const TYPE_RETURN_IN = 'RETURN_IN';

    public const TYPE_RETURN_OUT = 'RETURN_OUT';

    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';

    public const TYPE_STOCK_OPNAME = 'STOCK_OPNAME';

    protected $table = 'inventory_movements';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'movement_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reference()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function scopeIncoming(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeOutgoing(Builder $query): Builder
    {
        return $query->where('quantity', '<', 0);
    }
}