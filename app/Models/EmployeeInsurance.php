<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeInsurance extends Model
{
    use Auditable;
    use HasFactory;
    use HasStatus;

    /**
     * Atribut yang direkam di audit log (PRD 02 §27).
     *
     * @return array<int, string>
     */
    protected function auditAttributes(): array
    {
        return [
        'employee_id',
        'insurance_type',
        'provider',
        'membership_number',
        'employee_contribution',
        'company_contribution',
        'status',
        ];
    }

    protected $fillable = [
        'employee_id',
        'insurance_type',
        'provider',
        'membership_number',
        'insurance_class',
        'start_date',
        'end_date',
        'employee_contribution',
        'company_contribution',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'employee_contribution' => 'decimal:2',
            'company_contribution' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}