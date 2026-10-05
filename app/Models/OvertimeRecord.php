<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRecord extends Model
{
    use HasFactory;
    use HasStatus;

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'overtime_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'approved_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'overtime_date' => 'date',
            'duration_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}