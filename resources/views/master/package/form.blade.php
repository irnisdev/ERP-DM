@extends('layouts.app')

@section('title', $package->exists ? 'Edit Package' : 'Tambah Package')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $package->exists ? 'Edit Package' : 'Tambah Package' }}</h1>
        <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <form method="POST" action="{{ $package->exists ? route('packages.update', $package) : route('packages.store') }}">
        @csrf
        @if ($package->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Data Package</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="code">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" required
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code', $package->code) }}">
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="name">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" required
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $package->name) }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="price">Harga Paket <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="price" id="price" required
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price', $package->price ?? 0) }}">
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="valid_from">Valid Mulai</label>
                                <input type="date" name="valid_from" id="valid_from" class="form-control"
                                       value="{{ old('valid_from', $package->valid_from?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="valid_until">Valid Sampai</label>
                                <input type="date" name="valid_until" id="valid_until"
                                       class="form-control @error('valid_until') is-invalid @enderror"
                                       value="{{ old('valid_until', $package->valid_until?->format('Y-m-d')) }}">
                                @error('valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select">
                                    @foreach (['ACTIVE', 'INACTIVE'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $package->status) === $status)>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="description">Deskripsi</label>
                                <textarea name="description" id="description" rows="2" class="form-control">{{ old('description', $package->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">
                        Isi Package <span class="text-danger">*</span>
                    </div>
                    <div class="card-body">
                        @error('services')<div class="alert alert-danger small">{{ $message }}</div>@enderror

                        <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-2">
                                <thead class="table-light">
                                    <tr><th>Service</th><th class="text-end" style="width: 110px;">Qty</th></tr>
                                </thead>
                                <tbody id="package-items">
                                    @foreach ($services as $index => $service)
                                        @php $checked = array_key_exists($service->id, $selectedItems); @endphp
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input item-toggle"
                                                           id="svc_{{ $service->id }}" data-target="qty_{{ $service->id }}"
                                                           @checked($checked)>
                                                    <label class="form-check-label small" for="svc_{{ $service->id }}">
                                                        {{ $service->name }}
                                                    </label>
                                                </div>
                                                <input type="hidden" name="services[{{ $index }}][service_id]"
                                                       id="hidden_svc_{{ $service->id }}" value="{{ $service->id }}"
                                                       @disabled(! $checked)>
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" min="0.0001"
                                                       class="form-control form-control-sm text-end item-qty"
                                                       id="qty_{{ $service->id }}" name="services[{{ $index }}][quantity]"
                                                       value="{{ $selectedItems[$service->id] ?? 1 }}"
                                                       @disabled(! $checked)>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="select-all">
                            Pilih Semua
                        </button>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i>{{ $package->exists ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                        <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('.item-toggle').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    const qty = document.getElementById(toggle.dataset.target);
                    const hidden = document.getElementById('hidden_svc_' + toggle.id.replace('svc_', ''));
                    if (qty) qty.disabled = !toggle.checked;
                    if (hidden) hidden.disabled = !toggle.checked;
                });
            });

            document.getElementById('select-all').addEventListener('click', function () {
                const allChecked = Array.from(document.querySelectorAll('.item-toggle')).every(function (t) {
                    return t.checked;
                });

                document.querySelectorAll('.item-toggle').forEach(function (toggle) {
                    toggle.checked = !allChecked;
                    toggle.dispatchEvent(new Event('change'));
                });
            });
        })();
    </script>
@endpush