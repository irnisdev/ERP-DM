@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $supplier->name }}</h1>
            <span class="font-monospace text-muted">{{ $supplier->supplier_code }}</span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $supplier)
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Kontak</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">PIC</dt>
                        <dd class="col-sm-8">{{ $supplier->contact_person ?? '—' }}</dd>

                        <dt class="col-sm-4">No. HP</dt>
                        <dd class="col-sm-8">{{ $supplier->phone ?? '—' }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $supplier->email ?? '—' }}</dd>

                        <dt class="col-sm-4">NPWP</dt>
                        <dd class="col-sm-8">{{ $supplier->tax_number ?? '—' }}</dd>

                        <dt class="col-sm-4">Termin</dt>
                        <dd class="col-sm-8">
                            {{ $supplier->payment_terms_days ? $supplier->payment_terms_days.' hari' : '—' }}
                        </dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge text-bg-{{ $supplier->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                {{ $supplier->status }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Alamat</dt>
                        <dd class="col-sm-8">{{ $supplier->address ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Rekening Bank</span>
                    @can('viewBankAccounts', $supplier)
                        <a href="{{ route('suppliers.bank-accounts.index', $supplier) }}"
                           class="btn btn-sm btn-outline-secondary">Kelola</a>
                    @endcan
                </div>
                <div class="card-body">
                    @unless ($canViewBank)
                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-lock me-1"></i>
                            Nomor rekening adalah data sensitif dan membutuhkan permission khusus.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Bank</th><th>Nomor Rekening</th><th>Atas Nama</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($supplier->bankAccounts as $account)
                                        <tr>
                                            <td>{{ $account->bank?->name ?? '—' }}</td>
                                            <td class="font-monospace">
                                                {{ $account->account_number }}
                                                @if ($account->is_primary)
                                                    <span class="badge text-bg-success ms-1">Utama</span>
                                                @endif
                                            </td>
                                            <td>{{ $account->account_name }}</td>
                                            <td class="text-end">
                                                @if ($canManageBank)
                                                    <form method="POST" class="d-inline"
                                                          action="{{ route('suppliers.bank-accounts.destroy', [$supplier, $account]) }}">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Hapus rekening ini?')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">
                                            Belum ada rekening bank terdaftar
                                        </td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endunless
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold">Pembelian</div>
                <div class="card-body text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Purchase order, goods receipt, dan purchase invoice dibangun pada Phase 4.
                </div>
            </div>
        </div>
    </div>
@endsection