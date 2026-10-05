<?php

namespace App\Http\Requests;

use App\Models\ServicePackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServicePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ServicePackage::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('service_packages', 'code')->ignore($this->route('servicePackage')),
            ],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],

            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'exists:services,id'],
            'services.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode package wajib diisi.',
            'code.unique' => 'Kode package sudah dipakai.',
            'price.required' => 'Harga package wajib diisi.',
            'services.required' => 'Package harus memuat minimal satu service.',
            'services.*.service_id.required' => 'Service wajib dipilih.',
            'services.*.quantity.min' => 'Jumlah service harus lebih dari 0.',
            'valid_until.after_or_equal' => 'Tanggal berakhir harus setelah atau sama dengan tanggal mulai.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ids = collect($this->input('services', []))->pluck('service_id')->filter()->all();

            if (count($ids) !== count(array_unique($ids))) {
                $validator->errors()->add('services', 'Service yang sama tidak boleh masuk dua kali.');
            }
        });
    }

    /**
     * Data header saja; item ditangani terpisah.
     *
     * @return array<string, mixed>
     */
    public function packageData(): array
    {
        $data = $this->validated();
        unset($data['services']);

        return $data;
    }

    /**
     * @return array<int, array{service_id: int, quantity: float}>
     */
    public function itemData(): array
    {
        return collect($this->validated()['services'])
            ->map(fn (array $item) => [
                'service_id' => (int) $item['service_id'],
                'quantity' => (float) $item['quantity'],
            ])
            ->values()
            ->all();
    }
}