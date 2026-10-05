<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master rekening kas/bank/e-wallet. Bank mutation untuk semua bank memakai
 * tabel yang sama (bank_transactions) — tidak ada tabel per bank.
 */
class FinancialAccount extends Model
{
    use Auditable;
    use HasFactory;
    use HasStatus;

    public const TYPE_CASH = 'CASH';

    public const TYPE_BANK = 'BANK';

    public const TYPE_E_WALLET = 'E_WALLET';

    public const TYPE_OTHER = 'OTHER';

    protected $fillable = [
        'account_code',
        'account_type',
        'bank_id',
        'branch_id',
        'account_number',
        'account_name',
        'currency',
        'opening_balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }
}