<?php

namespace App\Services;

use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * SalesService — satu engine untuk penjualan produk, jasa, dan paket.
 *
 * PRD 02 §37.5: jangan membuat transaksi produk dan jasa menjadi dua engine
 * berbeda. Semua lewat `sales_transaction_items` yang sama.
 *
 * PRD 02 §12 checkout:
 *   1. Validate cart
 *   2. Lock relevant stock
 *   3. Create sales header
 *   4. Create items
 *   5. Generate inventory movement untuk product
 *   6. Generate commission jika applicable
 *   7. Create payment
 *   8. Update transaction status
 *   9. Commit
 *
 * Seluruh langkah 1–9 berjalan dalam satu DB transaction. Tidak ada kondisi
 * pembayaran sukses tanpa invoice, atau stok berkurang tanpa transaksi
 * (PRD 01 §30).
 */
class SalesService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CommissionService $commission,
        private readonly PaymentService $payments,
        private readonly PriceResolver $prices,
    ) {
    }

    /**
     * Checkout penuh.
     *
     * @param  array<string, mixed>  $cart  item_type, product_id/service_id/package_id, quantity, discount, employee_id, unit_price
     * @param  array<string, mixed>|null  $payment  payment_method_id, amount, financial_account_id, reference_number
     * @return array{0: SalesTransaction, 1: \Illuminate\Support\Collection}
     */
    public function checkout(array $cart, array $context, ?array $payment = null): array
    {
        if ($cart === []) {
            throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
        }

        return DB::transaction(function () use ($cart, $context, $payment) {
            $lines = $this->resolveCart($cart, $context);

            $header = $this->createHeader($lines, $context);
            $items = $this->createItems($header, $lines);

            $this->deductStock($header, $lines, $context);
            $this->accrueCommissions($items);

            $payments = $this->recordPayment($header, $payment, $context);

            return [$header->fresh(), $payments];
        });
    }

    /**
     * Menyusun setiap baris cart menjadi data item lengkap dengan harga dan
     * subtotal. Harga di-resolve dari master saat checkout dan disimpan sebagai
     * snapshot (PRD 01 §2.3).
     *
     * @return array<int, array<string, mixed>>
     */
    private function resolveCart(array $cart, array $context): array
    {
        $customerType = isset($context['customer_id'])
            ? Customer::whereKey($context['customer_id'])->value('customer_type')
            : null;

        $lines = [];

        foreach (array_values($cart) as $index => $line) {
            $type = $line['item_type'] ?? null;
            $quantity = (float) ($line['quantity'] ?? 0);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "cart.{$index}.quantity" => 'Kuantitas harus lebih besar dari nol.',
                ]);
            }

            $line['customer_type'] ??= $customerType;

            [$item, $price] = $this->resolveItem($line, $index, $quantity);

            $discount = (float) ($line['discount'] ?? 0);
            $subtotal = ($price * $quantity) - $discount;

            if ($subtotal < 0) {
                throw ValidationException::withMessages([
                    "cart.{$index}.discount" => 'Diskon tidak boleh melebihi nilai item.',
                ]);
            }

            $lines[] = [
                'item' => $item,
                'item_type' => $type,
                'quantity' => $quantity,
                'unit_price' => $price,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'employee_id' => $line['employee_id'] ?? null,
                'source_booking_item_id' => $line['source_booking_item_id'] ?? null,
                'warehouse_id' => $line['warehouse_id'] ?? null,
            ];
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array{0: SalesTransactionItem, 1: float}
     */
    private function resolveItem(array $line, int $index, float $quantity): array
    {
        $type = $line['item_type'] ?? null;

        $model = match ($type) {
            SalesTransactionItem::TYPE_PRODUCT => isset($line['product_id'])
                ? Product::find($line['product_id'])
                : null,
            SalesTransactionItem::TYPE_SERVICE => isset($line['service_id'])
                ? Service::find($line['service_id'])
                : null,
            SalesTransactionItem::TYPE_PACKAGE => isset($line['package_id'])
                ? ServicePackage::find($line['package_id'])
                : null,
            default => throw ValidationException::withMessages([
                "cart.{$index}.item_type" => 'item_type harus PRODUCT, SERVICE, atau PACKAGE.',
            ]),
        };

        if ($model === null) {
            throw ValidationException::withMessages([
                "cart.{$index}" => 'Item tidak ditemukan atau tidak aktif.',
            ]);
        }

        if ($model->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                "cart.{$index}" => "{$model->name} tidak aktif dan tidak bisa dijual.",
            ]);
        }

        // Harga selalu di-resolve dari master. Nilai dari request diabaikan
        // supaya client tidak bisa memanipulasi harga jual.
        $price = $this->prices->resolve($model, $line);

        if ($price < 0) {
            throw ValidationException::withMessages([
                "cart.{$index}.unit_price" => 'Harga tidak boleh negatif.',
            ]);
        }

        $stub = new SalesTransactionItem([
            'item_type' => $type,
            'item_name' => $model->name,
            'quantity' => $quantity,
            'unit_price' => $price,
            'discount' => (float) ($line['discount'] ?? 0),
            'subtotal' => ($price * $quantity) - (float) ($line['discount'] ?? 0),
            'employee_id' => $line['employee_id'] ?? null,
            'source_booking_item_id' => $line['source_booking_item_id'] ?? null,
        ]);

        $stub->product_id = $model instanceof Product ? $model->id : null;
        $stub->service_id = $model instanceof Service ? $model->id : null;
        $stub->package_id = $model instanceof ServicePackage ? $model->id : null;

        return [$stub, $price];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<string, mixed>  $context
     */
    private function createHeader(array $lines, array $context): SalesTransaction
    {
        $subtotal = array_sum(array_column($lines, 'subtotal'));
        $discount = (float) ($context['discount'] ?? 0);
        $tax = (float) ($context['tax'] ?? 0);

        return SalesTransaction::create([
            'invoice_number' => $this->nextInvoiceNumber(),
            'branch_id' => $context['branch_id'],
            'customer_id' => $context['customer_id'] ?? null,
            'cashier_id' => $context['cashier_id'] ?? null,
            'transaction_date' => now(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'grand_total' => $subtotal - $discount + $tax,
            'status' => SalesTransaction::STATUS_DRAFT,
            'notes' => $context['notes'] ?? null,
            'created_by' => $context['created_by'] ?? null,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return \Illuminate\Support\Collection<int, SalesTransactionItem>
     */
    private function createItems(SalesTransaction $header, array $lines)
    {
        $items = collect();

        foreach ($lines as $line) {
            $items->push($header->items()->create([
                'item_type' => $line['item_type'],
                'product_id' => $line['item']->product_id,
                'service_id' => $line['item']->service_id,
                'package_id' => $line['item']->package_id,
                'item_name' => $line['item']->item_name,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount' => $line['discount'],
                'subtotal' => $line['subtotal'],
                'employee_id' => $line['employee_id'],
                'source_booking_item_id' => $line['source_booking_item_id'],
                'notes' => $line['notes'] ?? null,
            ]));

        }

        return $items;
    }

    /**
     * Mengurangi stok hanya untuk item PRODUCT, memakai warehouse default
     * cabang bila baris cart tidak menentukan warehouse.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<string, mixed>  $context
     */
    private function deductStock(SalesTransaction $header, array $lines, array $context): void
    {
        $allowNegative = (bool) ($context['allow_negative_stock'] ?? false);
        $defaultWarehouse = null;

        foreach ($lines as $line) {
            if ($line['item_type'] !== SalesTransactionItem::TYPE_PRODUCT) {
                continue;
            }

            $product = $line['item']->product;

            if ($product === null) {
                continue;
            }

            $warehouse = $line['warehouse_id']
                ? Warehouse::find($line['warehouse_id'])
                : ($defaultWarehouse ??= $this->defaultWarehouse($header->branch_id));

            if ($warehouse === null) {
                throw ValidationException::withMessages([
                    'items' => "Belum ada warehouse untuk cabang ini, produk {$product->name} tidak bisa dijual.",
                ]);
            }

            $this->inventory->deduct(
                warehouse: $warehouse,
                product: $product,
                quantity: $line['quantity'],
                reference: [
                    'type' => $header,
                    'id' => $header->id,
                    'date' => $header->transaction_date->toDateString(),
                    'user_id' => $header->created_by,
                ],
                allowNegative: $allowNegative,
                unitCost: (float) $product->purchase_price,
            );
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SalesTransactionItem>  $items
     */
    private function accrueCommissions($items): void
    {
        foreach ($items as $item) {
            $employee = $item->employee;

            if ($employee === null) {
                continue;
            }

            $this->commission->accrue($item, $employee);
        }
    }

    /**
     * @param  array<string, mixed>|null  $payment
     * @param  array<string, mixed>  $context
     * @return \Illuminate\Support\Collection<int, \App\Models\Payment>
     */
    private function recordPayment(SalesTransaction $header, ?array $payment, array $context)
    {
        if ($payment === null || (float) ($payment['amount'] ?? 0) <= 0) {
            // Penjualan tanpa pembayaran (mis. invoice) tetap valid sebagai DRAFT.
            return collect();
        }

        $method = \App\Models\PaymentMethod::find($payment['payment_method_id'] ?? null);

        if ($method === null) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Metode pembayaran tidak ditemukan.',
            ]);
        }

        $account = isset($payment['financial_account_id'])
            ? \App\Models\FinancialAccount::find($payment['financial_account_id'])
            : $method->financial_account;

        $amount = (float) $payment['amount'];

        if ($amount > (float) $header->grand_total) {
            throw ValidationException::withMessages([
                'payment.amount' => 'Nominal pembayaran melebihi total transaksi.',
            ]);
        }

        $record = $this->payments->createFor(
            sourceType: \App\Models\Payment::SOURCE_SALES,
            source: $header,
            method: $method,
            amount: $amount,
            account: $account,
            referenceNumber: $payment['reference_number'] ?? null,
            notes: $payment['notes'] ?? null,
            userId: $context['created_by'] ?? null,
        );

        $this->payments->syncSourceStatus(\App\Models\Payment::SOURCE_SALES, $header);

        return collect([$record]);
    }

    public function nextInvoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd').'-'.str_pad(
                (string) (SalesTransaction::query()
                    ->whereDate('transaction_date', now()->toDateString())
                    ->count() + 1),
                3,
                '0',
                STR_PAD_LEFT
            );
        } while (SalesTransaction::where('invoice_number', $number)->exists());

        return $number;
    }

    /**
     * Warehouse pertama yang aktif di sebuah cabang.
     */
    private function defaultWarehouse(int $branchId): ?Warehouse
    {
        return Warehouse::query()
            ->where('branch_id', $branchId)
            ->where('status', 'ACTIVE')
            ->orderBy('id')
            ->first();
    }

    /**
     * Pembatalan penjualan: stok kembali dan komisi dibatalkan.
     *
     * Transaksi tidak dihapus (PRD 02 §37.7), hanya ditandai CANCELLED.
     */
    public function cancel(SalesTransaction $transaction, ?string $reason = null): SalesTransaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            $branchId = $transaction->branch_id;

            foreach ($transaction->items()->where('item_type', SalesTransactionItem::TYPE_PRODUCT)->get() as $item) {
                $product = $item->product;

                if ($product === null) {
                    continue;
                }

                $warehouse = $this->defaultWarehouse($branchId);

                if ($warehouse === null) {
                    continue;
                }

                $this->inventory->increase(
                    warehouse: $warehouse,
                    product: $product,
                    quantity: (float) $item->quantity,
                    movementType: \App\Models\InventoryMovement::TYPE_RETURN_IN,
                    reference: [
                        'type' => $transaction,
                        'id' => $transaction->id,
                        'date' => now()->toDateString(),
                        'notes' => 'Pembatalan penjualan '.$transaction->invoice_number,
                    ],
                    unitCost: (float) $product->purchase_price,
                );
            }

            $transaction->commissions()->update(['status' => \App\Models\EmployeeCommission::STATUS_VOID]);

            $notes = trim(($transaction->notes ?? '')."\n".'Dibatalkan: '.($reason ?? 'tanpa alasan'));

            $transaction->update([
                'status' => SalesTransaction::STATUS_CANCELLED,
                'notes' => $notes,
            ]);

            return $transaction->fresh();
        });
    }
}