<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequest extends Model
{
    use HasFactory;
    use HasStatus;

    public const TYPE_PURCHASE_ORDER = 'PURCHASE_ORDER';

    public const TYPE_EXPENSE = 'EXPENSE';

    public const TYPE_STOCK_TRANSFER = 'STOCK_TRANSFER';

    public const TYPE_STOCK_OPNAME = 'STOCK_OPNAME';

    public const TYPE_PAYROLL = 'PAYROLL';

    public const TYPE_PAYROLL_ADJUSTMENT = 'PAYROLL_ADJUSTMENT';

    public const TYPE_BANK_RECONCILIATION = 'BANK_RECONCILIATION';

    protected $fillable = [
        'request_type',
        'request_id',
        'requested_by',
        'approver_id',
        'status',
        'requested_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Logis reference ke entity yang diminta approval. request_type memetakan
     * ke model; resolution dilakukan di service layer atau lewat resolveTarget().
     */
    public function target(): ?Model
    {
        return self::modelForType($this->request_type)
            ?->find($this->request_id);
    }

    public static function modelForType(string $requestType): ?string
    {
        return match ($requestType) {
            self::TYPE_PURCHASE_ORDER => PurchaseOrder::class,
            self::TYPE_EXPENSE => Expense::class,
            self::TYPE_STOCK_TRANSFER => StockTransfer::class,
            self::TYPE_STOCK_OPNAME => StockOpname::class,
            self::TYPE_PAYROLL => Payroll::class,
            self::TYPE_PAYROLL_ADJUSTMENT => PayrollAdjustment::class,
            self::TYPE_BANK_RECONCILIATION => BankReconciliation::class,
            default => null,
        };
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'SUBMITTED');
    }
}