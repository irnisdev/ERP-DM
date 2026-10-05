<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseInvoice;
use App\Models\SalesTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * PaymentService — payment bersifat polymorphic/logical reference terhadap
 * sumber transaksi (PRD 01 §15).
 *
 * Karena `transaction_type` + `transaction_id` bukan FK constraint,
 * konsistensi reference diverifikasi di sini (bukan di database).
 *
 * PRD 02 §15: tidak boleh mencatat pendapatan dua kali hanya karena payment
 * dan bank mutation sama-sama masuk. Karena itu payment yang terhadap sales
 * menandai status sales, bukan membuat entri pendapatan kedua.
 */
class PaymentService
{
    /**
     * Tabel yang boleh menjadi sumber payment.
     *
     * @var array<string, class-string<Model>>
     */
    private const SOURCE_MODELS = [
        Payment::SOURCE_SALES => SalesTransaction::class,
        Payment::SOURCE_PURCHASE => PurchaseInvoice::class,
        Payment::SOURCE_EXPENSE => Expense::class,
        Payment::SOURCE_BILL => Bill::class,
    ];

    /**
     * Nomor dokumen berikutnya untuk sumber tertentu.
     */
    public function nextNumber(string $sourceType): string
    {
        $prefix = [
            Payment::SOURCE_SALES => 'PAY',
            Payment::SOURCE_PURCHASE => 'BAP',
            Payment::SOURCE_EXPENSE => 'BEX',
            Payment::SOURCE_BILL => 'BIL',
            Payment::SOURCE_PAYROLL => 'BPG',
            Payment::SOURCE_OTHER => 'BYR',
        ][$sourceType] ?? 'PAY';

        do {
            $number = $prefix.'-'.now()->format('Ym').'-'.str_pad(
                (string) (Payment::query()
                    ->where('transaction_type', $sourceType)
                    ->whereDate('payment_date', now()->toDateString())
                    ->count() + 1),
                4,
                '0',
                STR_PAD_LEFT
            );
        } while (Payment::where('payment_number', $number)->exists());

        return $number;
    }

    /**
     * Membuat payment untuk sebuah dokumen sumber.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFor(
        string $sourceType,
        Model $source,
        PaymentMethod $method,
        float $amount,
        ?FinancialAccount $account = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?int $userId = null
    ): Payment {
        $this->assertValidSource($sourceType, $source);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal payment harus lebih besar dari nol.',
            ]);
        }

        return DB::transaction(function () use (
            $sourceType, $source, $method, $amount, $account, $referenceNumber, $notes, $userId
        ) {
            return Payment::create([
                'payment_number' => $this->nextNumber($sourceType),
                'transaction_type' => $sourceType,
                'transaction_id' => $source->getKey(),
                'payment_method_id' => $method->id,
                'financial_account_id' => $account?->id ?? $method->financial_account_id,
                'amount' => $amount,
                'payment_date' => now(),
                'reference_number' => $referenceNumber,
                'status' => Payment::STATUS_COMPLETED,
                'notes' => $notes,
                'created_by' => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Total payment yang sudah completed untuk sebuah sumber.
     */
    public function paidAmount(string $sourceType, Model $source): float
    {
        return (float) Payment::query()
            ->forSource($sourceType, $source->getKey())
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');
    }

    public function outstanding(string $sourceType, Model $source, float $total): float
    {
        return max(0, $total - $this->paidAmount($sourceType, $source));
    }

    /**
     * Memperbarui status dokumen sumber setelah payment dicatat.
     *
     * Ini satu-satunya tempat status pembayaran dokumen berubah, supaya tidak
     * ada dua jalur penulisan.
     */
    public function syncSourceStatus(string $sourceType, Model $source): void
    {
        $outstanding = $this->outstanding($sourceType, $source, (float) $source->grand_total);

        match ($sourceType) {
            Payment::SOURCE_SALES => $source->update([
                'status' => match (true) {
                    $outstanding <= 0 => SalesTransaction::STATUS_PAID,
                    $outstanding < (float) $source->grand_total => SalesTransaction::STATUS_PARTIALLY_PAID,
                    default => $source->status,
                },
            ]),
            Payment::SOURCE_PURCHASE => $source->update([
                'status' => $outstanding <= 0
                    ? PurchaseInvoice::STATUS_PAID
                    : PurchaseInvoice::STATUS_PARTIAL,
            ]),
            Payment::SOURCE_BILL => $source->update([
                'status' => $outstanding <= 0 ? Bill::STATUS_PAID : Bill::STATUS_PARTIALLY_PAID,
                'paid_at' => $outstanding <= 0 ? now() : null,
            ]),
            Payment::SOURCE_EXPENSE => $source->update([
                'status' => $outstanding <= 0 ? 'PAID' : $source->status,
            ]),
            default => null,
        };
    }

    /**
     * Memastikan transaction_type sesuai dengan class model sumber.
     */
    public function assertValidSource(string $sourceType, Model $source): void
    {
        $expected = self::SOURCE_MODELS[$sourceType] ?? null;

        if ($expected !== null && ! $source instanceof $expected) {
            throw ValidationException::withMessages([
                'transaction_type' => "transaction_type {$sourceType} tidak sesuai dengan sumber "
                    .class_basename($source).'.',
            ]);
        }

        if (! array_key_exists($sourceType, self::SOURCE_MODELS)
            && ! in_array($sourceType, [Payment::SOURCE_PAYROLL, Payment::SOURCE_OTHER], true)) {
            throw ValidationException::withMessages([
                'transaction_type' => "transaction_type {$sourceType} tidak dikenal.",
            ]);
        }
    }
}