<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pricing service untuk duration dan weight (PRD 02 §10).
 *
 * Aturan bisnis PRD 01 §9:
 * - DURATION → duration_minutes wajib
 * - WEIGHT → min_weight dan max_weight wajib
 * - Range berat tidak boleh overlap untuk service dan periode yang sama
 * - Harga historis tidak mengubah transaksi lama
 */
class ServicePricingService
{
    public function addPrice(Service $service, array $data): ServicePrice
    {
        $data = $this->validateAgainstPricingMethod($service, $data);

        if ($service->pricing_method === Service::PRICING_WEIGHT) {
            $this->ensureNoWeightOverlap($service, $data);
        }

        if ($service->pricing_method === Service::PRICING_DURATION) {
            $this->ensureNoDurationOverlap($service, $data);
        }

        return $service->prices()->create($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function validateAgainstPricingMethod(Service $service, array $data): array
    {
        $method = $service->pricing_method;

        if ($method === Service::PRICING_DURATION) {
            if (empty($data['duration_minutes'])) {
                throw ValidationException::withMessages([
                    'duration_minutes' => 'Durasi wajib diisi untuk pricing method DURATION.',
                ]);
            }

            // Bersihkan field weight yang tidak berlaku
            unset($data['min_weight'], $data['max_weight']);

            return $data;
        }

        if ($method === Service::PRICING_WEIGHT) {
            if (($data['min_weight'] ?? null) === null || ($data['max_weight'] ?? null) === null) {
                throw ValidationException::withMessages([
                    'min_weight' => 'Range berat wajib diisi untuk pricing method WEIGHT.',
                    'max_weight' => 'Range berat wajib diisi untuk pricing method WEIGHT.',
                ]);
            }

            if ((float) $data['min_weight'] > (float) $data['max_weight']) {
                throw ValidationException::withMessages([
                    'min_weight' => 'Berat minimum tidak boleh lebih besar dari berat maksimum.',
                ]);
            }

            // Bersihkan field duration yang tidak berlaku
            unset($data['duration_minutes']);

            return $data;
        }

        // FIXED
        unset($data['duration_minutes'], $data['min_weight'], $data['max_weight']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureNoWeightOverlap(Service $service, array $data): void
    {
        $conflict = ServicePrice::query()
            ->where('service_id', $service->id)
            ->overlapsWeightRange((float) $data['min_weight'], (float) $data['max_weight'])
            ->when(
                isset($data['ignore_price_id']),
                fn ($q) => $q->whereKeyNot($data['ignore_price_id'])
            )
            ->effectivePeriod(
                $data['effective_date'] ?? now()->toDateString(),
                $data['end_date'] ?? null
            )
            ->first();

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'max_weight' => "Range berat {$data['min_weight']}–{$data['max_weight']} bertumpang tindih "
                    ."dengan harga lama ({$conflict->min_weight}–{$conflict->max_weight}).",
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureNoDurationOverlap(Service $service, array $data): void
    {
        $conflict = ServicePrice::query()
            ->where('service_id', $service->id)
            ->where('duration_minutes', (int) $data['duration_minutes'])
            ->when(
                isset($data['ignore_price_id']),
                fn ($q) => $q->whereKeyNot($data['ignore_price_id'])
            )
            ->effectivePeriod(
                $data['effective_date'] ?? now()->toDateString(),
                $data['end_date'] ?? null
            )
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'duration_minutes' => "Harga untuk durasi {$data['duration_minutes']} menit pada periode ini sudah ada.",
            ]);
        }
    }

    /**
     * Harga efektif untuk service pada tanggal dan parameter tertentu.
     */
    public function resolvePrice(Service $service, ?int $durationMinutes = null, ?float $weight = null, ?string $date = null): ?ServicePrice
    {
        $date ??= now()->toDateString();

        $query = ServicePrice::query()
            ->where('service_id', $service->id)
            ->effectiveOn($date);

        if ($service->pricing_method === Service::PRICING_DURATION) {
            $query->forDuration((int) ($durationMinutes ?? $service->default_duration_minutes));
        }

        if ($service->pricing_method === Service::PRICING_WEIGHT && $weight !== null) {
            $query->forWeightRange((float) $weight);
        }

        return $query->orderByDesc('effective_date')->first();
    }

    /**
     * Memperbarui harga dalam satu transaksi agar tidak ada celah periode.
     */
    public function replacePrices(Service $service, array $prices): void
    {
        DB::transaction(function () use ($service, $prices) {
            $service->prices()->delete();

            foreach ($prices as $price) {
                $this->addPrice($service, $price);
            }
        });
    }
}