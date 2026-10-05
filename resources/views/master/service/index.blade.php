@extends('layouts.app')

@section('title', 'Service')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Service</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('service-categories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-folder me-1"></i>Kategori
            </a>
            <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-bundle me-1"></i>Package
            </a>
            @can('create', App\Models\Service::class)
                <a href="{{ route('services.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Service
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
                           placeholder="Kode, nama" value="{{ $filters['search'] ?? '' }}">
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
                    <label class="form-label small mb-1">Pricing Method</label>
                    <select name="pricing_method" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($pricingMethods as $method)
                            <option value="{{ $method }}" @selected(($filters['pricing_method'] ?? '') === $method)>
                                {{ $method }}
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
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Pricing</th>
                        <th class="text-end">Durasi Default</th>
                        <th class="text-end">Harga Efektif</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td class="font-monospace">{{ $service->service_code }}</td>
                            <td>{{ $service->name }}</td>
                            <td>{{ $service->category->name ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $service->pricing_method === 'FIXED' ? 'light' : 'info' }}">
                                    {{ $service->pricing_method }}
                                </span>
                            </td>
                            <td class="text-end">
                                {{ $service->default_duration_minutes ? $service->default_duration_minutes.' mnt' : '—' }}
                            </td>
                            <td class="text-end">
                                @php $price = app(App\Services\ServicePricingService::class)->resolvePrice($service); @endphp
                                {{ $price ? number_format((float) $price->price, 0) : '—' }}
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $service->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                    {{ $service->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('services.show', $service) }}"
                                   class="btn btn-sm btn-outline-secondary">Detail</a>
                                @can('update', $service)
                                    <a href="{{ route('services.edit', $service) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada data service
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($services->hasPages())
            <div class="card-footer">{{ $services->links() }}</div>
        @endif
    </div>
@endsection