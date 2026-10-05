<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Services\BookingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * PRD 02 §12 — validasi keranjang POS.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->filled('booking_id')) {
            return $this->user()?->can('createSalesFrom', $this->route('booking')) ?? false;
        }

        return $this->user()?->can('create', SalesTransaction::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'notes' => ['nullable', 'string'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'allow_negative_stock' => ['boolean'],

            'cart' => ['required', 'array', 'min:1'],
            'cart.*.item_type' => ['required', Rule::in([
                SalesTransactionItem::TYPE_PRODUCT,
                SalesTransactionItem::TYPE_SERVICE,
                SalesTransactionItem::TYPE_PACKAGE,
            ])],
            'cart.*.product_id' => ['nullable', 'exists:products,id'],
            'cart.*.service_id' => ['nullable', 'exists:services,id'],
            'cart.*.package_id' => ['nullable', 'exists:service_packages,id'],
            'cart.*.quantity' => ['required', 'numeric', 'gt:0'],
            'cart.*.discount' => ['nullable', 'numeric', 'min:0'],
            'cart.*.employee_id' => ['nullable', 'exists:employees,id'],
            'cart.*.warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'cart.*.duration_minutes' => ['nullable', 'integer', 'min:1'],

            'payment.payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'payment.amount' => ['nullable', 'numeric', 'min:0'],
            'payment.financial_account_id' => ['nullable', 'exists:financial_accounts,id'],
            'payment.reference_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cart.required' => 'Keranjang masih kosong.',
            'cart.min' => 'Keranjang masih kosong.',
            'cart.*.quantity.gt' => 'Kuantitas harus lebih besar dari nol.',
            'branch_id.required' => 'Cabang wajib dipilih.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $required = [
                SalesTransactionItem::TYPE_PRODUCT => 'product_id',
                SalesTransactionItem::TYPE_SERVICE => 'service_id',
                SalesTransactionItem::TYPE_PACKAGE => 'package_id',
            ];

            foreach ($this->input('cart', []) as $index => $line) {
                $type = $line['item_type'] ?? null;
                $field = $required[$type] ?? null;

                if ($field !== null && empty($line[$field])) {
                    $validator->errors()->add(
                        "cart.{$index}.{$field}",
                        "{$field} wajib diisi untuk item bertipe {$type}."
                    );
                }
            }

            // Catatan: nominal payment tidak diverifikasi di sini karena total
            // resmi hanya diketahui setelah harga di-resolve dari master.
            // Validasi dilakukan di SalesService.
        });
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * User cabang hanya boleh transaksi di cabangnya sendiri.
     * Super admin boleh di semua cabang.
     */
    public function assertBranchAccess(): void
    {
        $branchId = (int) $this->input('branch_id');

        if ($this->user()->isSuperAdmin()) {
            return;
        }

        $ownBranchId = $this->user()->employee?->branch_id;

        abort_unless(
            $ownBranchId !== null && $ownBranchId === $branchId,
            403,
            'Anda hanya dapat bertransaksi di cabang Anda sendiri.'
        );
    }

    /**
     * Stok negatif hanya untuk role dengan permission khusus (PRD 02 §13).
     */
    public function allowsNegativeStock(): bool
    {
        if (! $this->boolean('allow_negative_stock')) {
            return false;
        }

        if (! $this->user()?->can('inventory.negative_stock')) {
            throw ValidationException::withMessages([
                'allow_negative_stock' => 'Anda tidak memiliki izin untuk penjualan dengan stok negatif.',
            ]);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function contextData(): array
    {
        return [
            'branch_id' => (int) $this->validated()['branch_id'],
            'customer_id' => $this->validated()['customer_id'] ?? null,
            'notes' => $this->validated()['notes'] ?? null,
            'discount' => (float) ($this->validated()['discount'] ?? 0),
            'tax' => (float) ($this->validated()['tax'] ?? 0),
            'allow_negative_stock' => $this->allowsNegativeStock(),
            'created_by' => $this->user()?->id,
            'cashier_id' => $this->user()?->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function paymentData(): ?array
    {
        $payment = $this->validated()['payment'] ?? [];

        if (empty($payment['payment_method_id']) || (float) ($payment['amount'] ?? 0) <= 0) {
            return null;
        }

        return [
            'payment_method_id' => (int) $payment['payment_method_id'],
            'amount' => (float) $payment['amount'],
            'financial_account_id' => isset($payment['financial_account_id'])
                ? (int) $payment['financial_account_id']
                : null,
            'reference_number' => $payment['reference_number'] ?? null,
        ];
    }
}
