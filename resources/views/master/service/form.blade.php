@extends('layouts.app')

@section('title', $service->exists ? 'Edit Service' : 'Tambah Service')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $service->exists ? 'Edit Service' : 'Tambah Service' }}</h1>
        <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <form method="POST" action="{{ $service->exists ? route('services.update', $service) : route('services.store') }}">
        @csrf
        @if ($service->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Identitas Service</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="service_code">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="service_code" id="service_code" required
                                       class="form-control @error('service_code') is-invalid @enderror"
                                       value="{{ old('service_code', $service->service_code) }}">
                                @error('service_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="name">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" required
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $service->name) }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="category_id">Kategori <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" required
                                        class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="">—</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id', $service->category_id) == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select">
                                    @foreach (['ACTIVE', 'INACTIVE'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $service->status) === $status)>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="description">Deskripsi</label>
                                <textarea name="description" id="description" rows="2" class="form-control">{{ old('description', $service->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Pricing Method</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="pricing_method">Metode <span class="text-danger">*</span></label>
                                <select name="pricing_method" id="pricing_method" required
                                        class="form-select @error('pricing_method') is-invalid @enderror">
                                    @foreach ($pricingMethods as $method)
                                        <option value="{{ $method }}"
                                            @selected(old('pricing_method', $service->pricing_method) === $method)>
                                            {{ $method }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('pricing_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="default_duration_minutes">Durasi Default (menit)</label>
                                <input type="number" min="1" max="1440" name="default_duration_minutes"
                                       id="default_duration_minutes"
                                       class="form-control @error('default_duration_minutes') is-invalid @enderror"
                                       value="{{ old('default_duration_minutes', $service->default_duration_minutes) }}">
                                @error('default_duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <div class="alert alert-info mb-0 small">
                                    <strong>FIXED</strong> — satu harga tetap.<br>
                                    <strong>DURATION</strong> — harga per durasi (60, 90, 120 menit).
                                    <code>duration_minutes</code> wajib diisi.<br>
                                    <strong>WEIGHT</strong> — harga per range berat (40–50 kg).
                                    <code>min_weight</code> dan <code>max_weight</code> wajib diisi dan
                                    range tidak boleh overlap.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                @if ($service->exists)
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white fw-semibold">Harga</div>
                        <div class="card-body">
                            <p class="small text-muted">
                                Aturan harga (duration/weight) dikelola di halaman terpisah.
                            </p>
                            <a href="{{ route('services.prices.index', $service) }}" class="btn btn-outline-secondary w-100">
                                Kelola Harga
                            </a>
                        </div>
                    </div>
                @endif

                <div class="card shadow-sm">
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>{{ $service->exists ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                        <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection