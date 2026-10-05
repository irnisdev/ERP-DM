<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100',
                Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'barcode' => ['nullable', 'string', 'max:100',
                Rule::unique('products', 'barcode')->ignore($this->route('product'))],
            'category_id' => ['required', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'default_sell_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'SKU wajib diisi.',
            'sku.unique' => 'SKU sudah dipakai produk lain.',
            'barcode.unique' => 'Barcode sudah dipakai produk lain.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'unit_id.required' => 'Satuan wajib dipilih.',
            'purchase_price.min' => 'Harga beli tidak boleh negatif.',
            'default_sell_price.min' => 'Harga jual tidak boleh negatif.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $min = $this->input('minimum_stock');
            $max = $this->input('maximum_stock');

            if ($min !== null && $max !== null && (float) $max < (float) $min) {
                $validator->errors()->add(
                    'maximum_stock',
                    'Stok maksimum tidak boleh lebih kecil dari stok minimum.'
                );
            }
        });
    }
}