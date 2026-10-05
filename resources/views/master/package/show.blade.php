@extends('layouts.app')

@section('title', $package->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $package->name }}</h1>
            <span class="font-monospace text-muted">{{ $package->code }}</span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $package)
                <a href="{{ route('packages.edit', $package) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary">
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
                        <dt class="col-sm-5">Harga Paket</dt>
                        <dd class="col-sm-7 fw-semibold">{{ number_format((float) $package->price, 0) }}</dd>

                        <dt class="col-sm-5">Total Harga Service</dt>
                        <dd class="col-sm-7">{{ number_format((float) $totalItemPrice, 0) }}</dd>

                        @if ($totalItemPrice > 0)
                            <dt class="col-sm-5">Selisih</dt>
                            <dd class="col-sm-7">
                                <span class="badge text-bg-{{ (float) $package->price < $totalItemPrice ? 'success' : 'warning' }}">
                                    {{ number_format(abs((float) $totalItemPrice - (float) $package->price), 0) }}
                                    {{ (float) $package->price < $totalItemPrice ? 'hemat' : 'lebih mahal' }}
                                </span>
                            </dd>
                        @endif

                        <dt class="col-sm-5">Valid Mulai</dt>
                        <dd class="col-sm-7">{{ $package->valid_from?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-5">Valid Sampai</dt>
                        <dd class="col-sm-7">{{ $package->valid_until?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            <span class="badge text-bg-{{ $package->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                {{ $package->status }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Deskripsi</div>
                <div class="card-body">{{ $package->description ?? 'Tidak ada deskripsi.' }}</div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    Isi Package
                    <span class="badge text-bg-light ms-1">{{ $package->services->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Service</th><th class="text-end">Qty</th><th>Pricing</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($package->services as $service)
                                <tr>
                                    <td>
                                        <a href="{{ route('services.show', $service) }}">{{ $service->name }}</a>
                                        <div class="font-monospace small text-muted">{{ $service->service_code }}</div>
                                    </td>
                                    <td class="text-end">{{ rtrim(rtrim((string) $service->pivot->quantity, '0'), '.') }}</td>
                                    <td><span class="badge text-bg-light">{{ $service->pricing_method }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Package belum memuat service</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection