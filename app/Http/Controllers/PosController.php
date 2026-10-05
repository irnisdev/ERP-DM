<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Warehouse;
use App\Models\Employee;
use App\Services\InventoryService;
use App\Services\PriceResolver;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly SalesService $sales,
        private readonly InventoryService $inventory,
        private readonly PriceResolver $prices,
    ) {
    }

    public function create(Request $request): View
    {
        $this->authorize('create', \App\Models\SalesTransaction::class);

        $branchId = $request->integer('branch_id') ?: $request->user()->employee?->branch_id;

        return view('sales.pos.index', [
            'branchId' => $branchId,
            'branches' => $this->branchOptions($request),
            'customers' => Customer::where('status', 'ACTIVE')->orderBy('name')->limit(500)->get(),
            'paymentMethods' => PaymentMethod::where('status', 'ACTIVE')->orderBy('name')->get(),
            'warehouses' => Warehouse::where('status', 'ACTIVE')->orderBy('name')->get(),
        ]);
    }

    /**
     * Satu engine untuk produk, jasa, dan paket — semua-item endpoint dipakai
     * oleh JS keranjang POS agar tidak ada endpoint per tipe.
     */
    public function catalog(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', \App\Models\SalesTransaction::class);

        $search = $request->string('search')->trim()->value();
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $customerType = $request->string('customer_type')->value() ?: null;

        $build = function (string $type) use ($search, $warehouseId, $customerType) {
            return match ($type) {
                'product' => Product::query()
                    ->with('category', 'brand')
                    ->where('status', 'ACTIVE')
                    ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")))
                    ->orderBy('name')->limit(20)->get()
                    ->map(fn (Product $p) => [
                        'item_type' => 'PRODUCT',
                        'id' => $p->id,
                        'name' => $p->name,
                        'meta' => trim($p->category->name.' '.$p->brand->name),
                        'price' => $this->prices->resolve($p, ['customer_type' => $customerType]),
                        'stock' => $warehouseId ? $this->inventory->availableQuantity(
                            Warehouse::find($warehouseId), $p
                        ) : null,
                    ]),
                'service' => Service::query()
                    ->with('category')
                    ->where('status', 'ACTIVE')
                    ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('service_code', 'like', "%{$search}%")))
                    ->orderBy('name')->limit(20)->get()
                    ->map(fn (Service $s) => [
                        'item_type' => 'SERVICE',
                        'id' => $s->id,
                        'name' => $s->name,
                        'meta' => $s->pricing_method,
                        'price' => $this->prices->resolve($s, ['duration_minutes' => $s->default_duration_minutes]),
                        'duration_minutes' => $s->default_duration_minutes,
                    ]),
                default => ServicePackage::query()
                    ->with('services')
                    ->where('status', 'ACTIVE')
                    ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orderBy('name')->limit(20)->get()
                    ->map(fn (ServicePackage $p) => [
                        'item_type' => 'PACKAGE',
                        'id' => $p->id,
                        'name' => $p->name,
                        'meta' => $p->services->count().' treatment',
                        'price' => $this->prices->resolve($p),
                    ]),
            };
        };

        return response()->json([
            'products' => $build('product'),
            'services' => $build('service'),
            'packages' => $build('package'),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $request->assertBranchAccess();

        [$transaction] = $this->sales->checkout(
            $request->validated()['cart'],
            $request->contextData(),
            $request->paymentData(),
        );

        return redirect()
            ->route('sales.show', $transaction)
            ->with('success', "Transaksi {$transaction->invoice_number} berhasil disimpan.");
    }

    private function branchOptions(Request $request)
    {
        $query = \App\Models\Branch::where('status', 'ACTIVE')->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->whereKey($request->user()->employee?->branch_id);
        }

        return $query->get();
    }
}
