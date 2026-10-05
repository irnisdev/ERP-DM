@extends('layouts.app')

@section('title', 'Customer')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Customer</h1>
        @can('create', App\Models\Customer::class)
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Tambah Customer
            </a>
        @endcan
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="search" name="search" class="form-control" placeholder="Kode, nama, HP, email"
                           value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Tipe</label>
                    <select name="customer_type" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['RETAIL', 'MEMBER', 'CORPORATE', 'CLINIC'] as $type)
                            <option value="{{ $type }}" @selected(($filters['customer_type'] ?? '') === $type)>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['ACTIVE', 'INACTIVE'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                {{ $status }}
                            </option>
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
                        <th>HP</th>
                        <th>Email</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="font-monospace">{{ $customer->customer_code }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone ?? '—' }}</td>
                            <td>{{ $customer->email ?? '—' }}</td>
                            <td><span class="badge text-bg-light">{{ $customer->customer_type }}</span></td>
                            <td>
                                <span class="badge text-bg-{{ $customer->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                    {{ $customer->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('customers.show', $customer) }}"
                                   class="btn btn-sm btn-outline-secondary">Detail</a>
                                @can('update', $customer)
                                    <a href="{{ route('customers.edit', $customer) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada data customer
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="card-footer">{{ $customers->links() }}</div>
        @endif
    </div>
@endsection