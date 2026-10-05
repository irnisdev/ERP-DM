<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCommission extends Model
{
    use HasFactory;
    use HasStatus;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_INCLUDED = 'INCLUDED';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_VOID = 'VOID';

    protected $fillable = [
        'employee_id',
        'sales_transaction_id',
        'sales_transaction_item_id',
        'commission_amount',
        'status',
        'payroll_id',
    ];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function salesTransactionItem(): BelongsTo
    {
        return $this->belongsTo(SalesTransactionItem::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}