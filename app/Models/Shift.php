<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasBranch;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use Auditable;
    use HasBranch;
    use HasFactory;
    use HasStatus;

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
        ];
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function totalMinutes(): int
    {
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);

        if ($end <= $start) {
            $end += 86400;
        }

        return intdiv($end - $start, 60) - (int) $this->break_minutes;
    }
}