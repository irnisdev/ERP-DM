<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServicePriceRequest;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Services\ServicePricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicePriceController extends Controller
{
    public function __construct(private readonly ServicePricingService $pricing)
    {
    }

    /**
     * Harga historis (PRD 02 §10). Duration dan weight pricing punya aturan
     * berbeda dan keduanya dicek overlap-nya.
     */
    public function index(Service $service): View
    {
        $this->authorize('managePrices', $service);

        return view('master.service.prices', [
            'service' => $service->load('prices'),
            'examples' => $this->pricingExamples($service),
        ]);
    }

    public function store(StoreServicePriceRequest $request, Service $service): RedirectResponse
    {
        $this->authorize('managePrices', $service);

        // ServicePricingService memvalidasi aturan DURATION/WEIGHT dan overlap.
        $this->pricing->addPrice($service, $request->validated());

        return back()->with('success', 'Harga berhasil ditambahkan.');
    }

    public function destroy(Service $service, ServicePrice $price): RedirectResponse
    {
        $this->authorize('deletePrice', $service);

        abort_unless($price->service_id === $service->id, 404);

        $price->delete();

        return back()->with('success', 'Harga berhasil dihapus.');
    }

    /**
     * @return array<int, string>
     */
    private function pricingExamples(Service $service): array
    {
        return match ($service->pricing_method) {
            Service::PRICING_DURATION => ['60', '90', '120'],
            Service::PRICING_WEIGHT => ['40-50', '51-60', '61-70'],
            default => [],
        };
    }
}