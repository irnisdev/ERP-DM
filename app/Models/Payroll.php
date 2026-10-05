<?php

namespace App\Models;

use App\Models\Concerns\HasApprover;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payroll approved menjadi historical snapshot dan tidak dapat diedit langsung.
 * Perubahan harus melalui reversal/correction workflow.
 */
class Payroll extends Model
{
    use HasApprover;
    use HasFactory;
    use HasStatus;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_REVIEW = 'REVIEW';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_PAID = 'PAID';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'basic_salary',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'status',
        'generated_at',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'basic_salary' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(EmployeeCommission::class);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PAID], true);
    }

    public function recalculateTotals(): void
    {
        $earnings = (float) $this->items()->where('type', 'EARNING')->sum('amount')
            + (float) $this->adjustments()->where('type', 'EARNING')->sum('amount');

        $deductions = (float) $this->items()->where('type', 'DEDUCTION')->sum('amount')
            + (float) $this->adjustments()->where('type', 'DEDUCTION')->sum('amount');

        $this->forceFill([
            'total_earnings' => $earnings,
            'total_deductions' => $deductions,
            'net_salary' => $earnings - $deductions,
        ])->save();
    }
}