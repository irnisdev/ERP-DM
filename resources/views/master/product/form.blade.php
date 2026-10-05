@extends('layouts.app')

@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $product->exists ? 'Edit Produk' : 'Tambah Produk' }}</h1>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Identitas Produk</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="sku">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" id="sku" required
                                       class="form-control @error('sku') is-invalid @enderror"
                                       value="{{ old('sku', $product->sku) }}">
                                @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="barcode">Barcode</label>
                                <input type="text" name="barcode" id="barcode"
                                       class="form-control @error('barcode') is-invalid @enderror"
                                       value="{{ old('barcode', $product->barcode) }}">
                                @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select">
                                    @foreach (['ACTIVE', 'INACTIVE'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $product->status) === $status)>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="name">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" required
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $product->name) }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="weight">Berat (kg)</label>
                                <input type="number" step="0.0001" min="0" name="weight" id="weight"
                                       class="form-control @error('weight') is-invalid @enderror"
                                       value="{{ old('weight', $product->weight) }}">
                                @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="description">Deskripsi</label>
                                <textarea name="description" id="description" rows="2" class="form-control">{{ old('description', $product->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Klasifikasi</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="category_id">Kategori <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" required
                                        class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="">—</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="brand_id">Brand</label>
                                <select name="brand_id" id="brand_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="unit_id">Satuan <span class="text-danger">*</span></label>
                                <select name="unit_id" id="unit_id" required
                                        class="form-select @error('unit_id') is-invalid @enderror">
                                    <option value="">—</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" @selected(old('unit_id', $product->unit_id) == $unit->id)>
                                            {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Harga & Stok</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="purchase_price">Harga Beli <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="purchase_price" id="purchase_price" required
                                       class="form-control @error('purchase_price') is-invalid @enderror"
                                       value="{{ old('purchase_price', $product->purchase_price ?? 0) }}">
                                @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="default_sell_price">Harga Jual <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="default_sell_price" id="default_sell_price" required
                                       class="form-control @error('default_sell_price') is-invalid @enderror"
                                       value="{{ old('default_sell_price', $product->default_sell_price ?? 0) }}">
                                @error('default_sell_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label" for="minimum_stock">Min. Stok <span class="text-danger">*</span></label>
                                <input type="number" step="0.0001" min="0" name="minimum_stock" id="minimum_stock" required
                                       class="form-control @error('minimum_stock') is-invalid @enderror"
                                       value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}">
                                @error('minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label" for="maximum_stock">Max. Stok</label>
                                <input type="number" step="0.0001" min="0" name="maximum_stock" id="maximum_stock"
                                       class="form-control @error('maximum_stock') is-invalid @enderror"
                                       value="{{ old('maximum_stock', $product->maximum_stock) }}">
                                @error('maximum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <p class="small text-muted mt-2 mb-0">
                            Harga historis per tipe disimpan terpisah di halaman
                            <em>Riwayat Harga</em> agar transaksi lama tidak berubah.
                            @if ($product->exists)
                                <a href="{{ route('products.prices.index', $product) }}">Buka riwayat harga</a>.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>{{ $product->exists ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection