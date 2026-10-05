@php
    /** @var App\Models\Customer $customer */
    $action = $customer->exists ? route('customers.update', $customer) : route('customers.store');
    $method = $customer->exists ? 'PUT' : 'POST';
@endphp

@extends('layouts.app')

@section('title', $customer->exists ? 'Edit Customer' : 'Tambah Customer')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $customer->exists ? 'Edit Customer' : 'Tambah Customer' }}</h1>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <form method="POST" action="{{ $action }}">
        @csrf
        @if ($method === 'PUT')
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Data Customer</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" required
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $customer->name) }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="gender">Gender</label>
                                <select name="gender" id="gender" class="form-select">
                                    <option value="">—</option>
                                    @foreach (['M' => 'Laki-laki', 'F' => 'Perempuan'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('gender', $customer->gender) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="birth_date">Tanggal Lahir</label>
                                <input type="date" name="birth_date" id="birth_date"
                                       class="form-control @error('birth_date') is-invalid @enderror"
                                       value="{{ old('birth_date', $customer->birth_date?->format('Y-m-d')) }}">
                                @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="phone">No. HP</label>
                                <input type="text" name="phone" id="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $customer->phone) }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" name="email" id="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $customer->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="address">Alamat</label>
                                <textarea name="address" id="address" rows="2"
                                          class="form-control @error('address') is-invalid @enderror">{{ old('address', $customer->address) }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="customer_type">Tipe <span class="text-danger">*</span></label>
                                <select name="customer_type" id="customer_type" required
                                        class="form-select @error('customer_type') is-invalid @enderror">
                                    @foreach (['RETAIL', 'MEMBER', 'CORPORATE', 'CLINIC'] as $type)
                                        <option value="{{ $type }}" @selected(old('customer_type', $customer->customer_type) === $type)>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="source">Source</label>
                                <input type="text" name="source" id="source" class="form-control"
                                       value="{{ old('source', $customer->source) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select">
                                    @foreach (['ACTIVE', 'INACTIVE'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $customer->status) === $status)>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="notes">Catatan</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes', $customer->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-3" id="duplicate-panel" style="display:none">
                    <div class="card-header bg-warning text-dark fw-semibold">
                        <i class="bi bi-exclamation-triangle me-1"></i>Potensi Duplicate
                    </div>
                    <div class="card-body">
                        <p class="small">Data dengan nomor HP atau email serupa sudah terdaftar:</p>
                        <div id="duplicate-list"></div>
                    </div>
                </div>

                @unless ($customer->exists)
                    <div class="form-check mb-3">
                        <input type="checkbox" name="force_create" id="force_create" value="1"
                               class="form-check-input" {{ old('force_create') ? 'checked' : '' }}>
                        <label class="form-check-label" for="force_create" style="display:none" id="force-label">
                            Tetap buat customer ini (duplicates sudah dicek)
                        </label>
                    </div>
                @endunless

                <div class="card shadow-sm">
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>{{ $customer->exists ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const phone = document.getElementById('phone');
            const email = document.getElementById('email');
            const panel = document.getElementById('duplicate-panel');
            const list = document.getElementById('duplicate-list');
            const force = document.getElementById('force_create');
            const forceLabel = document.getElementById('force-label');
            let timer = null;

            function reset() {
                panel.style.display = 'none';
                if (forceLabel) forceLabel.style.display = 'none';
                if (force) force.checked = false;
            }

            function check() {
                const payload = {
                    phone: phone.value.trim(),
                    email: email.value.trim(),
                    ignore_id: {{ $customer->id ?? 'null' }}
                };

                if (!payload.phone && !payload.email) { reset(); return; }

                fetch('{{ route('customers.check-duplicates') }}', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    query: new URLSearchParams(payload)
                })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.has_duplicates) { reset(); return; }

                        list.innerHTML = '';
                        data.matches.forEach(m => {
                            const item = document.createElement('div');
                            item.className = 'border rounded p-2 mb-2 small';
                            item.innerHTML =
                                '<span class="font-monospace">' + m.customer_code + '</span><br>' +
                                m.name + '<br>' +
                                '<span class="text-muted">' + (m.phone ?? '-') + ' / ' + (m.email ?? '-') + '</span>';
                            list.appendChild(item);
                        });

                        panel.style.display = 'block';
                        if (forceLabel) forceLabel.style.display = 'inline';
                    });
            }

            function debounce() {
                clearTimeout(timer);
                timer = setTimeout(check, 400);
            }

            phone.addEventListener('input', debounce);
            email.addEventListener('input', debounce);
        })();
    </script>
@endpush