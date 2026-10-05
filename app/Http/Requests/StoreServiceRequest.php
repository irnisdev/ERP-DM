<?php

namespace App\Http\Requests;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Service::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_code' => [
                'required', 'string', 'max:50',
                Rule::unique('services', 'service_code')->ignore($this->route('service')),
            ],
            'category_id' => ['required', 'exists:service_categories,id'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'pricing_method' => ['required', Rule::in([
                Service::PRICING_FIXED,
                Service::PRICING_DURATION,
                Service::PRICING_WEIGHT,
            ])],
            'default_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_code.required' => 'Kode service wajib diisi.',
            'service_code.unique' => 'Kode service sudah dipakai.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'pricing_method.required' => 'Metode pricing wajib dipilih.',
            'default_duration_minutes.integer' => 'Durasi harus berupa angka menit.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('pricing_method') === Service::PRICING_DURATION
                && ! $this->input('default_duration_minutes')) {
                $validator->errors()->add(
                    'default_duration_minutes',
                    'Durasi default wajib diisi untuk pricing method DURATION.'
                );
            }
        });
    }
}