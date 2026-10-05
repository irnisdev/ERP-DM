@extends('layouts.app')

@section('title', $service->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $service->name }}</h1>
            <span class="font-monospace text-muted">{{ $service->service_code }}</span>
            <span class="badge text-bg-{{ $service->pricing_method === 'FIXED' ? 'light' : 'info' }} ms-2">
                {{ $service->pricing_method }}
            </span>
        </div>
        <div class="d-flex gap-2">
            @can('managePrices', $service)
                <a href="{{ route('services.prices.index', $service) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-cash-coin me-1"></i>Kelola Harga
                </a>
            @endcan
            @can('update', $service)
                <a href="{{ route('services.edit', $service) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
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
                        <dd class="col-sm-7">{{ $service->category->name ?? '—' }}</dd>

                        <dt class="col-sm-5">Durasi Default</dt>
                        <dd class="col-sm-7">{{ $service->default_duration_minutes ? $service->default_duration_minutes.' menit' : '—' }}</dd>

                        <dt class="col-sm-5">Harga Efektif</dt>
                        <dd class="col-sm-7 fw-semibold">
                            {{ $currentPrice ? number_format((float) $currentPrice->price, 0) : 'Belum ada harga' }}
                        </dd>

                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            <span class="badge text-bg-{{ $service->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                {{ $service->status }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Deskripsi</div>
                <div class="card-body">{{ $service->description ?? 'Tidak ada deskripsi.' }}</div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    Aturan Harga
                    <span class="badge text-bg-light ms-1">{{ $service->prices->count() }}</span>
                </div>
                <div class="card-body">
                    @if ($service->pricing_method === App\Models\Service::PRICING_FIXED)
                        <p class="small text-muted">
                            Pricing FIXED memakai satu harga untuk seluruh service.
                        </p>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    @if ($service->pricing_method === App\Models\Service::PRICING_DURATION)
                                        <th>Durasi</th>
                                    @elseif ($service->pricing_method === App\Models\Service::PRICING_WEIGHT)
                                        <th>Range Berat</th>
                                    @else
                                        <th>Periode</th>
                                    @endif
                                    <th class="text-end">Harga</th>
                                    <th>Efektif</th><th>Berakhir</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($service->prices->sortByDesc('effective_date') as $price)
                                    <tr>
                                        <td>
                                            @if ($service->pricing_method === App\Models\Service::PRICING_DURATION)
                                                {{ $price->duration_minutes }} menit
                                            @elseif ($service->pricing_method === App\Models\Service::PRICING_WEIGHT)
                                                {{ rtrim(rtrim((string) $price->min_weight, '0'), '.') }}–{{ rtrim(rtrim((string) $price->max_weight, '0'), '.') }} kg
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format((float) $price->price, 0) }}</td>
                                        <td>{{ $price->effective_date->format('d M Y') }}</td>
                                        <td>{{ $price->end_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="text-end">
                                            @can('deletePrice', $service)
                                                <form method="POST"
                                                      action="{{ route('services.prices.destroy', [$service, $price]) }}">
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
                                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada aturan harga</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold">Package</div>
                <div class="card-body">
                    @php $packages = $service->packages; @endphp
                    @forelse ($packages as $package)
                        <a href="{{ route('packages.show', $package) }}"
                           class="badge text-bg-light text-decoration me-1 mb-1">
                            {{ $package->name }}
                        </a>
                    @empty
                        <span class="text-muted small">Belum ada package yang memuat service ini.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection