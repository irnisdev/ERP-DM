<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankTransaction extends Model
{
    use HasFactory;

    public const TYPE_CREDIT = 'CREDIT';

    public const TYPE_DEBIT = 'DEBIT';

    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';

    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';

    public const TYPE_FEE = 'FEE';

    public const TYPE_INTEREST = 'INTEREST';

    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';

    public const RECON_UNMATCHED = 'UNMATCHED';

    public const RECON_PARTIALLY_MATCHED = 'PARTIALLY_MATCHED';

    public const RECON_MATCHED = 'MATCHED';

    public const RECON_IGNORED = 'IGNORED';

    protected $fillable = [
        'financial_account_id',
        'transaction_date',
        'transaction_type',
        'amount',
        'description',
        'reference_number',
        'balance',
        'external_reference',
        'reconciliation_status',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'imported_at' => 'datetime',
        ];
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->where('reconciliation_status', self::RECON_UNMATCHED);
    }
}