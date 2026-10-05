<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Nomor rekening adalah data sensitif (PRD 02 §28) sehingga butuh permission
 * sensitive.rekening selain supplier.manage.
 */
class StoreSupplierBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageBankAccounts', $this->route('supplier')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $supplierId = $this->route('supplier')->id;

        return [
            'bank_id' => ['required', 'exists:banks,id'],
            'account_number' => [
                'required', 'string', 'max:100',
                Rule::unique('supplier_bank_accounts', 'account_number')
                    ->where(fn ($q) => $q->where('supplier_id', $supplierId)),
            ],
            'account_name' => ['required', 'string', 'max:191'],
            'branch_name' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['boolean'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'account_number.unique' => 'Nomor rekening ini sudah terdaftar untuk supplier tersebut.',
            'account_name.required' => 'Nama pemilik rekening wajib diisi.',
        ];
    }
}