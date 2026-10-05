@extends('layouts.app')

@section('title', 'Harga Service')

@section('content')
    @php use App\Models\Service; @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Harga Service</h1>
            <span class="text-muted">{{ $service->name }} — <span class="font-monospace">{{ $service->service_code }}</span></span>
            <span class="badge text-bg-{{ $service->pricing_method === 'FIXED' ? 'light' : 'info' }} ms-1">
                {{ $service->pricing_method }}
            </span>
        </div>
        <a href="{{ route('services.show', $service) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        Harga historis tidak mengubah transaksi lama. Transaksi menyimpan snapshot
        <code>unit_price</code> saat penjualan terjadi.
        @if ($service->pricing_method === Service::PRICING_WEIGHT)
            <strong>Range berat tidak boleh overlap</strong> untuk service dan periode yang sama.
        @endif
        @if ($service->pricing_method === Service::PRICING_DURATION)
            Setiap durasi hanya boleh punya satu harga pada periode yang sama.
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Daftar Harga</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kriteria</th>
                                <th class="text-end">Harga</th>
                                <th>Efektif</th>
                                <th>Berakhir</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($service->prices->sortByDesc('effective_date') as $price)
                                <tr>
                                    <td>
                                        @if ($service->pricing_method === Service::PRICING_DURATION)
                                            {{ $price->duration_minutes }} menit
                                        @elseif ($service->pricing_method === Service::PRICING_WEIGHT)
                                            {{ rtrim(rtrim((string) $price->min_weight, '0'), '.') }}–{{ rtrim(rtrim((string) $price->max_weight, '0'), '.') }} kg
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format((float) $price->price, 0) }}</td>
                                    <td>{{ $price->effective_date->format('d M Y') }}</td>
                                    <td>{{ $price->end_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="text-end">
                                        <form method="POST"
                                              action="{{ route('services.prices.destroy', [$service, $price]) }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Hapus harga ini?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada aturan harga</td></tr>
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
                    <form method="POST" action="{{ route('services.prices.store', $service) }}">
                        @csrf

                        @if ($service->pricing_method === Service::PRICING_DURATION)
                            <div class="mb-2">
                                <label class="form-label small mb-1" for="duration_minutes">Durasi (menit)</label>
                                <input type="number" min="1" max="1440" name="duration_minutes" id="duration_minutes"
                                       class="form-control @error('duration_minutes') is-invalid @enderror" required>
                                @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if ($examples)
                                    <div class="form-text">Contoh: {{ implode(', ', $examples) }}</div>
                                @endif
                            </div>
                        @elseif ($service->pricing_method === Service::PRICING_WEIGHT)
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1" for="min_weight">Berat Min (kg)</label>
                                    <input type="number" step="0.0001" min="0" name="min_weight" id="min_weight"
                                           class="form-control @error('min_weight') is-invalid @enderror" required>
                                    @error('min_weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1" for="max_weight">Berat Max (kg)</label>
                                    <input type="number" step="0.0001" min="0" name="max_weight" id="max_weight"
                                           class="form-control @error('max_weight') is-invalid @enderror" required>
                                    @error('max_weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            @if ($examples)
                                <div class="form-text mb-2">Contoh range: {{ implode(', ', $examples) }}</div>
                            @endif
                        @endif

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