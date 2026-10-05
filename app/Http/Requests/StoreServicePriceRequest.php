<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServicePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('managePrices', $this->route('service')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'min_weight' => ['nullable', 'numeric', 'min:0'],
            'max_weight' => ['nullable', 'numeric', 'min:0', 'gte:min_weight'],
            'price' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price.required' => 'Harga wajib diisi.',
            'effective_date.required' => 'Tanggal efektif wajib diisi.',
            'min_weight.gte' => 'Berat maksimum harus lebih besar dari berat minimum.',
            'max_weight.gte' => 'Berat maksimum harus lebih besar dari berat minimum.',
        ];
    }
}