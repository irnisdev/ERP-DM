<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePrice extends Model
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
        'service_id',
        'duration_minutes',
        'min_weight',
        'max_weight',
        'price',
        'effective_date',
        'end_date',
        ];
    }

    protected $fillable = [
        'service_id',
        'duration_minutes',
        'min_weight',
        'max_weight',
        'price',
        'effective_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'min_weight' => 'decimal:4',
            'max_weight' => 'decimal:4',
            'price' => 'decimal:2',
            'effective_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeEffectiveOn(Builder $query, string $date): Builder
    {
        return $query->whereDate('effective_date', '<=', $date)
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date));
    }

    /**
     * Periode harga masih berlaku pada tanggal tertentu.
     */
    public function scopeEffectivePeriod(Builder $query, string $startDate, ?string $endDate = null): Builder
    {
        if ($endDate === null) {
            return $query->where(fn (Builder $q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $startDate));
        }

        // [start, end] lama dan [newStart, newEnd] baru tidak boleh tumpang tindih
        return $query->whereDate('effective_date', '<=', $endDate)
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $startDate));
    }

    public function scopeForDuration(Builder $query, int $minutes): Builder
    {
        return $query->where('duration_minutes', $minutes);
    }

    public function scopeForWeightRange(Builder $query, float $weight): Builder
    {
        return $query->where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight);
    }

    /**
     * Rentang berat yang diminta tumpang tindih dengan baris yang ada.
     */
    public function scopeOverlapsWeightRange(Builder $query, float $minWeight, float $maxWeight): Builder
    {
        return $query->where('min_weight', '<=', $maxWeight)
            ->where('max_weight', '>=', $minWeight);
    }

    /**
     * Satu nilai berat termasuk dalam range baris ini.
     */
    public function containsWeight(float $weight): bool
    {
        return $weight >= (float) $this->min_weight && $weight <= (float) $this->max_weight;
    }
}