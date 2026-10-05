<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment bersifat polymorphic/logical reference terhadap sumber transaksi
 * (transaction_type + transaction_id). Validasi konsistensi dilakukan di
 * service layer, bukan di level database.
 */
class Payment extends Model
{
    use HasCreator;
    use HasFactory;
    use HasStatus;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_VOID = 'VOID';

    public const SOURCE_SALES = 'SALES';

    public const SOURCE_PURCHASE = 'PURCHASE';

    public const SOURCE_EXPENSE = 'EXPENSE';

    public const SOURCE_BILL = 'BILL';

    public const SOURCE_PAYROLL = 'PAYROLL';

    public const SOURCE_OTHER = 'OTHER';

    protected $fillable = [
        'payment_number',
        'transaction_type',
        'transaction_id',
        'payment_method_id',
        'financial_account_id',
        'amount',
        'payment_date',
        'reference_number',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function bankReconciliations()
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function scopeForSource(Builder $query, string $transactionType, int $transactionId): Builder
    {
        return $query->where('transaction_type', $transactionType)
            ->where('transaction_id', $transactionId);
    }

    public static function forSource(string $transactionType, int|Model $transaction): Builder
    {
        $id = $transaction instanceof Model ? $transaction->getKey() : $transaction;

        return static::query()->forSource($transactionType, (int) $id);
    }

    /**
     * Payment untuk sebuah dokumen sumber.
     *
     * Sengaja bukan method relasi pada model sumber: `transaction_type` +
     * `transaction_id` bukan FK constraint, sehingga Eloquent tidak bisa
     * menganggapnya sebagai relationship (dan `withCount('payments')` akan
     * gagal karena model sumber belum punya key).
     */
    public static function for(Model $source): Builder
    {
        return static::forSource(self::sourceTypeOf($source), $source->getKey());
    }

    public static function sourceTypeOf(Model $source): string
    {
        return match (true) {
            $source instanceof SalesTransaction => self::SOURCE_SALES,
            $source instanceof PurchaseInvoice => self::SOURCE_PURCHASE,
            $source instanceof Expense => self::SOURCE_EXPENSE,
            $source instanceof Bill => self::SOURCE_BILL,
            default => self::SOURCE_OTHER,
        };
    }

    /**
     * Subquery total pembayaran completed untuk sebuah dokumen sumber.
     *
     * Dipakai sebagai selectSub agar tabel listing tidak butuh N+1 query.
     *
     * @param  string  $modelKey  kolom id dari tabel dokumen sumber, mis. "sales_transactions.id"
     */
    public static function completedSumSubquery(string $sourceType, string $modelKey): Builder
    {
        return static::query()
            ->selectRaw('coalesce(sum(amount), 0)')
            ->from((new static())->getTable())
            ->whereColumn('transaction_id', $modelKey)
            ->where('transaction_type', $sourceType)
            ->where('status', self::STATUS_COMPLETED);
    }
}