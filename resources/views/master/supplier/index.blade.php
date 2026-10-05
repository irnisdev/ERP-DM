@extends('layouts.app')

@section('title', 'Supplier')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Supplier</h1>
        @can('create', App\Models\Supplier::class)
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Tambah Supplier
            </a>
        @endcan
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-7">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="search" name="search" class="form-control"
                           placeholder="Kode, nama, PIC, HP" value="{{ $filters['search'] ?? '' }}">
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
                <div class="col-md-2">
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
                        <th>PIC</th>
                        <th>HP</th>
                        <th class="text-end">Termin</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="font-monospace">{{ $supplier->supplier_code }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->contact_person ?? '—' }}</td>
                            <td>{{ $supplier->phone ?? '—' }}</td>
                            <td class="text-end">
                                {{ $supplier->payment_terms_days ? $supplier->payment_terms_days.' hari' : '—' }}
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $supplier->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                    {{ $supplier->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('suppliers.show', $supplier) }}"
                                   class="btn btn-sm btn-outline-secondary">Detail</a>
                                @can('update', $supplier)
                                    <a href="{{ route('suppliers.edit', $supplier) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada data supplier
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($suppliers->hasPages())
            <div class="card-footer">{{ $suppliers->links() }}</div>
        @endif
    </div>
@endsection