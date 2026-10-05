@extends('layouts.app')

@section('title', 'Product')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Product</h1>
        <div class="d-flex gap-2">
            @can('create', App\Models\ProductCategory::class)
                <a href="{{ route('product-categories.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-folder me-1"></i>Kategori
                </a>
                <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-tag me-1"></i>Brand
                </a>
                <a href="{{ route('units.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-rulers me-1"></i>Satuan
                </a>
            @endcan
            @can('create', App\Models\Product::class)
                <a href="{{ route('products.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Product
                </a>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="search" name="search" class="form-control"
                           placeholder="SKU, nama, barcode" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Kategori</label>
                    <select name="category_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Brand</label>
                    <select name="brand_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(($filters['brand_id'] ?? '') == $brand->id)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['ACTIVE', 'INACTIVE'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>SKU</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Brand</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-end">Min. Stok</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="font-monospace">{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name ?? '—' }}</td>
                            <td>{{ $product->brand->name ?? '—' }}</td>
                            <td>{{ $product->unit->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format((float) $product->default_sell_price, 0) }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format((float) $product->minimum_stock, 4), '0'), '.') }}</td>
                            <td>
                                <span class="badge text-bg-{{ $product->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                    {{ $product->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('products.show', $product) }}"
                                   class="btn btn-sm btn-outline-secondary">Detail</a>
                                @can('update', $product)
                                    <a href="{{ route('products.edit', $product) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada data produk
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>
@endsection