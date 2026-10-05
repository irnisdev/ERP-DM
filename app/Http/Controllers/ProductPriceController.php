<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductPriceRequest;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductPriceController extends Controller
{
    /**
     * Harga historis (PRD 02 §9). Produk dapat memiliki beberapa historical
     * prices. Transaksi lama tidak berubah ketika harga master berubah.
     */
    public function index(Product $product): View
    {
        $this->authorize('managePrices', $product);

        return view('master.product.prices', [
            'product' => $product->load('prices'),
        ]);
    }

    public function store(StoreProductPriceRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('managePrices', $product);

        $product->prices()->create($request->validated());

        return back()->with('success', 'Harga baru berhasil ditambahkan.');
    }

    public function destroy(Request $request, Product $product, ProductPrice $price): RedirectResponse
    {
        $this->authorize('deletePrice', $product);

        abort_unless($price->product_id === $product->id, 404);

        $price->delete();

        return back()->with('success', 'Harga berhasil dihapus.');
    }
}