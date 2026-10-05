<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Customer dapat dibuat dari Master Customer, POS, atau Booking (PRD 02 §8).
 * Customer yang sudah memiliki nomor HP atau email yang sama harus mendapat
 * warning duplicate sebelum record baru dibuat.
 */
class CustomerService
{
    /**
     * Cari customer yang cocok berdasarkan phone atau email.
     *
     * @return \Illuminate\Support\Collection<int, Customer>
     */
    public function findDuplicates(?string $phone, ?string $email, ?int $ignoreId = null)
    {
        if (($phone === null || $phone === '') && ($email === null || $email === '')) {
            return collect();
        }

        return Customer::query()
            ->findDuplicates($phone, $email)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->orderBy('customer_code')
            ->get();
    }

    public function hasDuplicates(?string $phone, ?string $email, ?int $ignoreId = null): bool
    {
        return $this->findDuplicates($phone, $email, $ignoreId)->isNotEmpty();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $data['customer_code'] = $this->generateCode($data['name'] ?? 'CUST');

            return Customer::create($data);
        });
    }

    private function generateCode(string $name): string
    {
        $prefix = 'CUST-'.Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $name) ?: 'X', 0, 4));

        $code = $prefix;
        $suffix = 1;

        while (Customer::withTrashed()->where('customer_code', $code)->exists()) {
            $code = $prefix.'-'.$suffix++;
        }

        return $code;
    }
}