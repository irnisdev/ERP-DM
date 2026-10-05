@extends('layouts.app')

@section('title', 'Package Treatment')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Package Treatment</h1>
        @can('create', App\Models\ServicePackage::class)
            <a href="{{ route('packages.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Tambah Package
            </a>
        @endcan
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="search" name="search" class="form-control"
                           placeholder="Kode, nama" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['ACTIVE', 'INACTIVE'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
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
                        <th class="text-end">Harga Paket</th>
                        <th>Isi</th>
                        <th>Valid</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td class="font-monospace">{{ $package->code }}</td>
                            <td>{{ $package->name }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $package->price, 0) }}</td>
                            <td class="small">{{ $package->services_count }} service</td>
                            <td class="small text-muted">
                                {{ $package->valid_from?->format('M Y') ?? '—' }}
                                @if ($package->valid_until)
                                    — {{ $package->valid_until->format('M Y') }}
                                @endif
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $package->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                    {{ $package->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('packages.show', $package) }}"
                                   class="btn btn-sm btn-outline-secondary">Detail</a>
                                @can('update', $package)
                                    <a href="{{ route('packages.edit', $package) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada data package
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($packages->hasPages())
            <div class="card-footer">{{ $packages->links() }}</div>
        @endif
    </div>
@endsection