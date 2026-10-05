@extends('layouts.app')

@section('title', 'Detail Customer')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $customer->name }}</h1>
            <span class="font-monospace text-muted">{{ $customer->customer_code }}</span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $customer)
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Informasi</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Tipe</dt>
                        <dd class="col-sm-8">{{ $customer->customer_type }}</dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge text-bg-{{ $customer->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                {{ $customer->status }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Gender</dt>
                        <dd class="col-sm-8">{{ $customer->gender === 'M' ? 'Laki-laki' : ($customer->gender === 'F' ? 'Perempuan' : '—') }}</dd>

                        <dt class="col-sm-4">Tanggal Lahir</dt>
                        <dd class="col-sm-8">{{ $customer->birth_date?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-4">No. HP</dt>
                        <dd class="col-sm-8">{{ $customer->phone ?? '—' }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $customer->email ?? '—' }}</dd>

                        <dt class="col-sm-4">Source</dt>
                        <dd class="col-sm-8">{{ $customer->source ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Alamat & Catatan</div>
                <div class="card-body">
                    <p class="mb-2">{{ $customer->address ?? '—' }}</p>
                    <hr>
                    <p class="mb-0 text-muted">{{ $customer->notes ?? 'Tidak ada catatan.' }}</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Riwayat Aktivitas</div>
                <div class="card-body text-muted small">
                    Riwayat berasal dari transaksi (sales, booking, treatment, payment).
                    Modul transaksional dibangun pada Phase 3–5.
                </div>
            </div>
        </div>
    </div>
@endsection