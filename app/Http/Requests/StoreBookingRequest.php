<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Booking::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'booking_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.package_id' => ['nullable', 'exists:service_packages,id'],
            'items.*.employee_id' => ['nullable', 'exists:employees,id'],
            'items.*.scheduled_start' => ['nullable', 'date'],
            'items.*.scheduled_end' => ['nullable', 'date', 'after:items.*.scheduled_start'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer wajib dipilih.',
            'booking_date.required' => 'Tanggal booking wajib diisi.',
            'items.required' => 'Booking harus memuat minimal satu treatment.',
            'items.min' => 'Booking harus memuat minimal satu treatment.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $index => $item) {
                $hasService = ! empty($item['service_id']);
                $hasPackage = ! empty($item['package_id']);

                if (! $hasService && ! $hasPackage) {
                    $validator->errors()->add(
                        "items.{$index}.service_id",
                        'Setiap item harus punya service atau package.'
                    );
                }

                if ($hasService && $hasPackage) {
                    $validator->errors()->add(
                        "items.{$index}.service_id",
                        'Item tidak boleh punya service dan package sekaligus.'
                    );
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function itemData(): array
    {
        return collect($this->validated()['items'])
            ->map(fn (array $item) => [
                'service_id' => $item['service_id'] ?? null,
                'package_id' => $item['package_id'] ?? null,
                'employee_id' => $item['employee_id'] ?? null,
                'scheduled_start' => $item['scheduled_start'] ?? null,
                'scheduled_end' => $item['scheduled_end'] ?? null,
                'status' => \App\Models\BookingItem::STATUS_BOOKED,
            ])
            ->values()
            ->all();
    }

    public function headerData(): array
    {
        return [
            'customer_id' => $this->validated()['customer_id'],
            'branch_id' => $this->validated()['branch_id'],
            'booking_date' => $this->validated()['booking_date'],
            'start_time' => $this->validated()['start_time'] ?? null,
            'end_time' => $this->validated()['end_time'] ?? null,
            'notes' => $this->validated()['notes'] ?? null,
        ];
    }
}
