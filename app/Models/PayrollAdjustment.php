<?php

namespace App\Models;

use App\Models\Concerns\HasApprover;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Manual adjustment tidak mengubah master salary dan tercatat di audit log.
 * Jika payroll sudah approved, tidak dapat diedit langsung.
 */
class PayrollAdjustment extends Model
{
    use HasApprover;
    use HasCreator;
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'type',
        'description',
        'amount',
        'reason',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}