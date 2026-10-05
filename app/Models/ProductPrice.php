<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use Auditable;
    use HasFactory;

    /**
     * Atribut yang direkam di audit log (PRD 02 §27).
     *
     * @return array<int, string>
     */
    protected function auditAttributes(): array
    {
        return [
        'product_id',
        'price_type',
        'price',
        'effective_date',
        'end_date',
        ];
    }

    protected $fillable = [
        'product_id',
        'price_type',
        'price',
        'effective_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'end_date' => 'date',
            'price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeEffectiveOn(Builder $query, string $date): Builder
    {
        return $query->whereDate('effective_date', '<=', $date)
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date));
    }
}