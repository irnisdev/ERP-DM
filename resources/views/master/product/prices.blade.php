@extends('layouts.app')

@section('title', 'Riwayat Harga')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Riwayat Harga</h1>
            <span class="text-muted">{{ $product->name }} — <span class="font-monospace">{{ $product->sku }}</span></span>
        </div>
        <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        Harga historis tidak mengubah transaksi lama. Transaksi menyimpan snapshot
        <code>unit_price</code> saat penjualan terjadi.
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Daftar Harga</div>
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
                                        <form method="POST" action="{{ route('products.prices.destroy', [$product, $price]) }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Hapus harga ini?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada harga historis</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Tambah Harga</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('products.prices.store', $product) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small mb-1" for="price_type">Tipe Harga</label>
                            <select name="price_type" id="price_type" class="form-select" required>
                                @foreach (['RETAIL', 'MEMBER', 'CORPORATE', 'WHOLESALE'] as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1" for="price">Harga</label>
                            <input type="number" step="0.01" min="0" name="price" id="price"
                                   class="form-control @error('price') is-invalid @enderror" required>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1" for="effective_date">Efektif Mulai</label>
                            <input type="date" name="effective_date" id="effective_date"
                                   class="form-control @error('effective_date') is-invalid @enderror"
                                   value="{{ now()->toDateString() }}" required>
                            @error('effective_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label small mb-1" for="end_date">Berakhir (opsional)</label>
                            <input type="date" name="end_date" id="end_date"
                                   class="form-control @error('end_date') is-invalid @enderror">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i>Simpan Harga
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection