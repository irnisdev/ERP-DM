<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    use HasFactory;
    use HasStatus;

    public const TYPE_PERCENTAGE = 'PERCENTAGE';

    public const TYPE_FIXED = 'FIXED';

    protected $fillable = [
        'employee_id',
        'service_id',
        'commission_type',
        'commission_value',
        'effective_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:2',
            'effective_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Commission = treatment Rp200.000, rule 20% → Rp40.000
     */
    public function calculate(float $baseAmount): float
    {
        return $this->commission_type === self::TYPE_PERCENTAGE
            ? round($baseAmount * ((float) $this->commission_value / 100), 2)
            : round((float) $this->commission_value, 2);
    }
}