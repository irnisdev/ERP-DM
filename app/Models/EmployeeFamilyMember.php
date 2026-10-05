<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFamilyMember extends Model
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
        'employee_id',
        'name',
        'relationship',
        'nik',
        'is_dependent',
        ];
    }

    protected $fillable = [
        'employee_id',
        'name',
        'relationship',
        'nik',
        'birth_date',
        'gender',
        'phone',
        'address',
        'is_dependent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_dependent' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}