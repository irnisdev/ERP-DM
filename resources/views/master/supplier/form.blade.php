@extends('layouts.app')

@section('title', $supplier->exists ? 'Edit Supplier' : 'Tambah Supplier')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $supplier->exists ? 'Edit Supplier' : 'Tambah Supplier' }}</h1>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <form method="POST"
          action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
        @csrf
        @if ($supplier->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Data Supplier</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="supplier_code">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="supplier_code" id="supplier_code" required
                                       class="form-control @error('supplier_code') is-invalid @enderror"
                                       value="{{ old('supplier_code', $supplier->supplier_code) }}">
                                @error('supplier_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="name">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" required
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $supplier->name) }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="contact_person">PIC</label>
                                <input type="text" name="contact_person" id="contact_person" class="form-control"
                                       value="{{ old('contact_person', $supplier->contact_person) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="phone">No. HP</label>
                                <input type="text" name="phone" id="phone" class="form-control"
                                       value="{{ old('phone', $supplier->phone) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" name="email" id="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $supplier->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="tax_number">NPWP</label>
                                <input type="text" name="tax_number" id="tax_number" class="form-control"
                                       value="{{ old('tax_number', $supplier->tax_number) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="address">Alamat</label>
                                <textarea name="address" id="address" rows="2" class="form-control">{{ old('address', $supplier->address) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Ketentuan</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="payment_terms_days">Termin Pembayaran (hari)</label>
                            <input type="number" min="0" max="365" name="payment_terms_days" id="payment_terms_days"
                                   class="form-control @error('payment_terms_days') is-invalid @enderror"
                                   value="{{ old('payment_terms_days', $supplier->payment_terms_days ?? 30) }}">
                            @error('payment_terms_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select">
                                @foreach (['ACTIVE', 'INACTIVE'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $supplier->status) === $status)>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($supplier->exists)
                            <p class="small text-muted mb-0">
                                Rekening bank dikelola setelah supplier dibuat, dari halaman detail.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>{{ $supplier->exists ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection