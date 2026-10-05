<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServicePackageRequest;
use App\Http\Requests\UpdateServicePackageRequest;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Services\ServicePricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServicePackageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ServicePackage::class);

        $packages = ServicePackage::query()
            ->with('services')
            ->withCount('services')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q->where('code', 'like', $search)->orWhere('name', 'like', $search));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('master.package.index', [
            'packages' => $packages,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ServicePackage::class);

        return view('master.package.form', [
            'package' => new ServicePackage(['status' => 'ACTIVE']),
            'services' => Service::where('status', 'ACTIVE')->orderBy('name')->get(),
            'selectedItems' => [],
        ]);
    }

    public function store(StoreServicePackageRequest $request): RedirectResponse
    {
        $package = $this->persist(new ServicePackage, $request->packageData(), $request->itemData());

        return redirect()
            ->route('packages.show', $package)
            ->with('success', "Package {$package->code} berhasil dibuat.");
    }

    public function show(ServicePackage $servicePackage): View
    {
        $this->authorize('view', $servicePackage);

        $servicePackage->load('services');

        $pricing = app(ServicePricingService::class);

        // Nilai jual paket tidak otomatis sama dengan total harga service;
        // harga paket adalah diskon yang ditetapkan admin.
        $totalItemPrice = $servicePackage->services->sum(function (Service $service) use ($pricing) {
            $price = $pricing->resolvePrice($service);

            return $price ? (float) $price->price * (float) $service->pivot->quantity : 0.0;
        });

        return view('master.package.show', [
            'package' => $servicePackage,
            'totalItemPrice' => $totalItemPrice,
        ]);
    }

    public function edit(ServicePackage $servicePackage): View
    {
        $this->authorize('update', $servicePackage);

        $servicePackage->load('services');

        return view('master.package.form', [
            'package' => $servicePackage,
            'services' => Service::where('status', 'ACTIVE')->orderBy('name')->get(),
            'selectedItems' => $servicePackage->items->mapWithKeys(fn ($i) => [$i->service_id => (float) $i->quantity])->all(),
        ]);
    }

    public function update(UpdateServicePackageRequest $request, ServicePackage $servicePackage): RedirectResponse
    {
        $this->persist($servicePackage, $request->packageData(), $request->itemData());

        return redirect()
            ->route('packages.show', $servicePackage)
            ->with('success', 'Package berhasil diperbarui.');
    }

    public function destroy(ServicePackage $servicePackage): RedirectResponse
    {
        $this->authorize('delete', $servicePackage);

        $servicePackage->delete();

        return redirect()
            ->route('packages.index')
            ->with('success', "Package {$servicePackage->code} dinonaktifkan.");
    }

    /**
     * Header dan item package disimpan atomik.
     *
     * @param  array<string, mixed>  $header
     * @param  array<int, array{service_id: int, quantity: float}>  $items
     */
    private function persist(ServicePackage $package, array $header, array $items): ServicePackage
    {
        return DB::transaction(function () use ($package, $header, $items) {
            $package->fill($header)->save();

            // Sinkronkan item agar tidak ada duplikat atau sisa data lama.
            $package->items()->delete();

            foreach ($items as $item) {
                $package->items()->create($item);
            }

            return $package->fresh(['services']);
        });
    }
}