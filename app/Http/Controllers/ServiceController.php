<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\ServicePricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::query()
            ->with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q
                    ->where('service_code', 'like', $search)
                    ->orWhere('name', 'like', $search));
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('pricing_method'), fn ($q) => $q->where('pricing_method', $request->string('pricing_method')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('master.service.index', [
            'services' => $services,
            'categories' => ServiceCategory::orderBy('name')->get(),
            'pricingMethods' => [Service::PRICING_FIXED, Service::PRICING_DURATION, Service::PRICING_WEIGHT],
            'filters' => $request->only(['search', 'category_id', 'pricing_method', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Service::class);

        return view('master.service.form', [
            'service' => new Service([
                'status' => 'ACTIVE',
                'pricing_method' => Service::PRICING_FIXED,
            ]),
            'categories' => ServiceCategory::orderBy('name')->get(),
            'pricingMethods' => [Service::PRICING_FIXED, Service::PRICING_DURATION, Service::PRICING_WEIGHT],
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $service = Service::create($request->validated());

        return redirect()
            ->route('services.show', $service)
            ->with('success', "Service {$service->service_code} berhasil dibuat.");
    }

    public function show(Service $service): View
    {
        $this->authorize('view', $service);

        $service->load(['category', 'prices']);

        return view('master.service.show', [
            'service' => $service,
            'currentPrice' => app(ServicePricingService::class)->resolvePrice($service),
        ]);
    }

    public function edit(Service $service): View
    {
        $this->authorize('update', $service);

        return view('master.service.form', [
            'service' => $service,
            'categories' => ServiceCategory::orderBy('name')->get(),
            'pricingMethods' => [Service::PRICING_FIXED, Service::PRICING_DURATION, Service::PRICING_WEIGHT],
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Data service berhasil diperbarui.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        $service->delete();

        return redirect()
            ->route('services.index')
            ->with('success', "Service {$service->service_code} dinonaktifkan.");
    }
}