<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['category', 'brand', 'unit'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q
                    ->where('sku', 'like', $search)
                    ->orWhere('name', 'like', $search)
                    ->orWhere('barcode', 'like', $search));
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('master.product.index', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'filters' => $request->only(['search', 'category_id', 'brand_id', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('master.product.form', [
            'product' => new Product(['status' => 'ACTIVE', 'minimum_stock' => 0]),
            'categories' => ProductCategory::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('success', "Produk {$product->sku} berhasil dibuat.");
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load(['category', 'brand', 'unit', 'prices']);

        return view('master.product.show', [
            'product' => $product,
            'priceTypes' => ['RETAIL', 'MEMBER', 'CORPORATE', 'WHOLESALE'],
        ]);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('master.product.form', [
            'product' => $product,
            'categories' => ProductCategory::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Data produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', "Produk {$product->sku} dinonaktifkan.");
    }
}