@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Dashboard</h1>
        <span class="badge text-bg-secondary">
            {{ auth()->user()->employee?->branch?->name ?? 'Semua cabang' }}
        </span>
    </div>

    <div class="row g-3">
        @foreach ([
            ['Employee', 'employees.index', 'bi-people', 'employee.view'],
            ['Customer', 'customers.index', 'bi-person-vcard', 'customer.view'],
            ['Product', 'products.index', 'bi-box-seam', 'product.view'],
            ['Service', 'services.index', 'bi-heart-pulse', 'service.view'],
            ['Package', 'packages.index', 'bi-bundle', 'package.view'],
            ['Supplier', 'suppliers.index', 'bi-truck', 'supplier.view'],
        ] as [$label, $route, $icon, $permission])
            @can($permission)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route($route) }}" class="text-decoration-none">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi {{ $icon }} fs-3 text-primary"></i>
                                <div class="fw-semibold mt-1">{{ $label }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endcan
        @endforeach
    </div>

    <div class="alert alert-info mt-4 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Modul transaksional (booking, POS, inventory, finance, payroll) belum dibangun.
        Phase 2 hanya mencakup master data. Lihat <code>README.md</code> untuk status tiap phase.
    </div>
@endsection