<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Workflow: DRAFT → REVIEW → APPROVED → PUBLISHED → VOID
 * Setelah APPROVED slip terkunci dan tidak dapat diubah langsung.
 */
class Payslip extends Model
{
    use HasFactory;
    use HasStatus;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_REVIEW = 'REVIEW';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_PUBLISHED = 'PUBLISHED';

    public const STATUS_VOID = 'VOID';

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'template_id',
        'payslip_number',
        'issue_date',
        'gross_salary',
        'total_deduction',
        'net_salary',
        'status',
        'pdf_path',
        'approved_by',
        'approved_at',
        'verification_code',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'approved_at' => 'datetime',
            'gross_salary' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PayslipTemplate::class, 'template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PUBLISHED, self::STATUS_VOID], true);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REVIEW], true);
    }
}