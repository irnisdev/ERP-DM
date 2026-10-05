<?php

namespace App\Models;

use App\Models\Concerns\HasBranch;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    use HasBranch;
    use HasFactory;
    use HasStatus;

    public const STATUS_PRESENT = 'PRESENT';

    public const STATUS_LATE = 'LATE';

    public const STATUS_ABSENT = 'ABSENT';

    public const STATUS_PERMIT = 'PERMIT';

    public const STATUS_SICK = 'SICK';

    public const STATUS_HOLIDAY = 'HOLIDAY';

    public const STATUS_HALF_DAY = 'HALF_DAY';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'attendance_date',
        'check_in',
        'check_out',
        'status',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtimeRecords(): HasOne
    {
        return $this->hasOne(OvertimeRecord::class);
    }

    public function workedMinutes(): int
    {
        if (! $this->check_in || ! $this->check_out) {
            return 0;
        }

        return intdiv($this->check_out->diffInMinutes($this->check_in), 1);
    }
}