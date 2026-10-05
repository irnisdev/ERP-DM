<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\ServicePrice;

/**
 * PriceResolver — harga efektif saat transaksi terjadi.
 *
 * Transaksi menyimpan hasilnya sebagai snapshot di
 * `sales_transaction_items.unit_price`, sehingga perubahan harga master
 * setelahwards tidak mengubah transaksi lama (PRD 01 §2.3, PRD 02 §9).
 */
class PriceResolver
{
    /**
     * @param  array<string, mixed>  $line
     */
    public function resolve(object $model, array $line = []): float
    {
        $priceType = $line['price_type']
            ?? $this->guessPriceType($line['customer_type'] ?? null);

        return match (true) {
            $model instanceof Product => $this->productPrice($model, $priceType),
            $model instanceof Service => $this->servicePrice($model, $line),
            $model instanceof ServicePackage => (float) $model->price,
            default => 0.0,
        };
    }

    private function productPrice(Product $product, string $priceType): float
    {
        $date = now()->toDateString();

        $price = $product->prices()
            ->where('price_type', $priceType)
            ->whereDate('effective_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date))
            ->orderByDesc('effective_date')
            ->first();

        if ($price instanceof ProductPrice) {
            return (float) $price->price;
        }

        return (float) $product->default_sell_price;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function servicePrice(Service $service, array $line): float
    {
        $date = now()->toDateString();

        $query = ServicePrice::query()
            ->where('service_id', $service->id)
            ->whereDate('effective_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date));

        if ($service->pricing_method === Service::PRICING_DURATION) {
            $minutes = (int) ($line['duration_minutes'] ?? $service->default_duration_minutes);
            $query->where('duration_minutes', $minutes);
        }

        if ($service->pricing_method === Service::PRICING_WEIGHT) {
            $weight = (float) ($line['weight'] ?? 0);

            if ($weight <= 0) {
                return 0.0;
            }

            $query->where('min_weight', '<=', $weight)->where('max_weight', '>=', $weight);
        }

        $price = $query->orderByDesc('effective_date')->first();

        return $price ? (float) $price->price : 0.0;
    }

    /**
     * Tipe harga default mengikuti tipe customer: MEMBER dan CORPORATE memakai
     * harga MEMBER, selain itu RETAIL.
     */
    public function guessPriceType(?string $customerType): string
    {
        return in_array($customerType, ['MEMBER', 'CORPORATE'], true) ? 'MEMBER' : 'RETAIL';
    }
}