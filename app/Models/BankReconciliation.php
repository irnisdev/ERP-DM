<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends Model
{
    use HasFactory;

    public const STATUS_MATCHED = 'MATCHED';

    public const STATUS_UNMATCHED = 'UNMATCHED';

    public const STATUS_VOID = 'VOID';

    protected $fillable = [
        'bank_transaction_id',
        'payment_id',
        'matched_amount',
        'status',
        'reconciled_by',
        'reconciled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'matched_amount' => 'decimal:2',
            'reconciled_at' => 'datetime',
        ];
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}