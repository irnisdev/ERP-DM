@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $product->name }}</h1>
            <span class="font-monospace text-muted">{{ $product->sku }}</span>
            @if ($product->barcode)
                <span class="text-muted ms-2">Barcode: {{ $product->barcode }}</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            @can('managePrices', $product)
                <a href="{{ route('products.prices.index', $product) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-clock-history me-1"></i>Riwayat Harga
                </a>
            @endcan
            @can('update', $product)
                <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Detail</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-5">Kategori</dt>
                        <dd class="col-sm-7">{{ $product->category->name ?? '—' }}</dd>

                        <dt class="col-sm-5">Brand</dt>
                        <dd class="col-sm-7">{{ $product->brand->name ?? '—' }}</dd>

                        <dt class="col-sm-5">Satuan</dt>
                        <dd class="col-sm-7">{{ $product->unit->name ?? '—' }}</dd>

                        <dt class="col-sm-5">Berat</dt>
                        <dd class="col-sm-7">{{ $product->weight ? rtrim(rtrim((string) $product->weight, '0'), '.') . ' kg' : '—' }}</dd>

                        <dt class="col-sm-5">Harga Beli</dt>
                        <dd class="col-sm-7">{{ number_format((float) $product->purchase_price, 0) }}</dd>

                        <dt class="col-sm-5">Harga Jual</dt>
                        <dd class="col-sm-7 fw-semibold">{{ number_format((float) $product->default_sell_price, 0) }}</dd>

                        <dt class="col-sm-5">Min. Stok</dt>
                        <dd class="col-sm-7">{{ rtrim(rtrim(number_format((float) $product->minimum_stock, 4), '0'), '.') }}</dd>

                        <dt class="col-sm-5">Max. Stok</dt>
                        <dd class="col-sm-7">{{ $product->maximum_stock ? rtrim(rtrim(number_format((float) $product->maximum_stock, 4), '0'), '.') : '—' }}</dd>

                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            <span class="badge text-bg-{{ $product->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                {{ $product->status }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Deskripsi</div>
                <div class="card-body">{{ $product->description ?? 'Tidak ada deskripsi.' }}</div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    Harga Efektif
                    <span class="badge text-bg-light ms-1">{{ $product->prices->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Tipe</th><th class="text-end">Harga</th><th>Efektif</th><th>Berakhir</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse ($product->prices->sortByDesc('effective_date') as $price)
                                    <tr>
                                        <td>{{ $price->price_type }}</td>
                                        <td class="text-end">{{ number_format((float) $price->price, 0) }}</td>
                                        <td>{{ $price->effective_date->format('d M Y') }}</td>
                                        <td>{{ $price->end_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="text-end">
                                            @can('deletePrice', $product)
                                                <form method="POST" class="d-inline"
                                                      action="{{ route('products.prices.destroy', [$product, $price]) }}">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Hapus harga ini?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            Belum ada harga historis
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @can('managePrices', $product)
                        <hr>
                        <form method="POST" action="{{ route('products.prices.store', $product) }}" class="row g-2">
                            @csrf
                            <div class="col-md-3">
                                <select name="price_type" class="form-select" required>
                                    @foreach ($priceTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" step="0.01" min="0" name="price" class="form-control"
                                       placeholder="Harga" required>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="effective_date" class="form-control" required
                                       value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold">Stok</div>
                <div class="card-body text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Data stok per warehouse dibangun pada Phase 4. Stok hanya berubah
                    melalui inventory movement, tidak boleh diedit manual.
                </div>
            </div>
        </div>
    </div>
@endsection