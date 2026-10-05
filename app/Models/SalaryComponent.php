<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    use Auditable;
    use HasFactory;
    use HasStatus;

    public const TYPE_EARNING = 'EARNING';

    public const TYPE_DEDUCTION = 'DEDUCTION';

    public const METHOD_FIXED = 'FIXED';

    public const METHOD_PERCENTAGE = 'PERCENTAGE';

    public const METHOD_PER_DAY = 'PER_DAY';

    public const METHOD_PER_HOUR = 'PER_HOUR';

    public const METHOD_FORMULA = 'FORMULA';

    protected $fillable = [
        'code',
        'name',
        'type',
        'calculation_method',
        'is_taxable',
        'is_bpjs',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_bpjs' => 'boolean',
        ];
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function isEarning(): bool
    {
        return $this->type === self::TYPE_EARNING;
    }
}