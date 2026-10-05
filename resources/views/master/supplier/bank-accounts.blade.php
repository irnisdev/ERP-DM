@extends('layouts.app')

@section('title', 'Rekening Supplier')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Rekening Bank</h1>
            <span class="text-muted">{{ $supplier->name }} — <span class="font-monospace">{{ $supplier->supplier_code }}</span></span>
        </div>
        <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="alert alert-warning">
        <i class="bi bi-shield-lock me-1"></i>
        Nomor rekening bersifat sensitif. Hanya user dengan permission
        <code>sensitive.rekening</code> yang dapat melihat dan mengubah data ini.
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Daftar Rekening</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Bank</th><th>Nomor Rekening</th><th>Atas Nama</th><th>Cabang</th><th>Status</th><th></th></tr>
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
                                    <td>{{ $account->branch_name ?? '—' }}</td>
                                    <td>{{ $account->status }}</td>
                                    <td class="text-end">
                                        <form method="POST"
                                              action="{{ route('suppliers.bank-accounts.destroy', [$supplier, $account]) }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Hapus rekening ini?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">
                                    Belum ada rekening bank terdaftar
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Tambah Rekening</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('suppliers.bank-accounts.store', $supplier) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small mb-1" for="bank_id">Bank</label>
                            <select name="bank_id" id="bank_id" class="form-select" required>
                                <option value="">—</option>
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}" @selected(old('bank_id') == $bank->id)>
                                        {{ $bank->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1" for="account_number">Nomor Rekening</label>
                            <input type="text" name="account_number" id="account_number"
                                   class="form-control @error('account_number') is-invalid @enderror" required>
                            @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1" for="account_name">Atas Nama</label>
                            <input type="text" name="account_name" id="account_name"
                                   class="form-control @error('account_name') is-invalid @enderror" required>
                            @error('account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1" for="branch_name">Cabang Bank</label>
                            <input type="text" name="branch_name" id="branch_name" class="form-control"
                                   value="{{ old('branch_name') }}">
                        </div>

                        <div class="mb-2">
                            <input type="hidden" name="status" value="ACTIVE">
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_primary" value="1" id="is_primary"
                                   class="form-check-input" {{ old('is_primary') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_primary">Jadikan rekening utama</label>
                        </div>

                        <button class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i>Simpan Rekening
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection