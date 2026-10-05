@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $title }}</h1>
        <a href="{{ route($routeBase.'.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <form method="POST"
          action="{{ $model->exists ? route($routeBase.'.update', $model) : route($routeBase.'.store') }}">
        @csrf
        @if ($model->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Data</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="code">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" required
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code', $model->code) }}">
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="name">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" required
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $model->name) }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if ($allowsParent)
                                <div class="col-md-6">
                                    <label class="form-label" for="parent_id">Induk</label>
                                    <select name="parent_id" id="parent_id"
                                            class="form-select @error('parent_id') is-invalid @enderror">
                                        <option value="">—</option>
                                        @foreach ($parents as $parent)
                                            <option value="{{ $parent->id }}"
                                                @selected(old('parent_id', $model->parent_id) == $parent->id)>
                                                {{ $parent->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif

                            @foreach ($fields as $field)
                                @if ($field === 'description')
                                    <div class="col-12">
                                        <label class="form-label" for="description">Deskripsi</label>
                                        <textarea name="description" id="description" rows="2"
                                                  class="form-control">{{ old('description', $model->description) }}</textarea>
                                    </div>
                                @elseif ($field === 'decimal_scale')
                                    <div class="col-md-4">
                                        <label class="form-label" for="decimal_scale">Jumlah Desimal</label>
                                        <input type="number" min="0" max="6" name="decimal_scale" id="decimal_scale"
                                               class="form-control @error('decimal_scale') is-invalid @enderror"
                                               value="{{ old('decimal_scale', $model->decimal_scale ?? 0) }}">
                                        @error('decimal_scale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @endif
                            @endforeach

                            <div class="col-md-4">
                                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select">
                                    @foreach (['ACTIVE', 'INACTIVE'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $model->status) === $status)>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>{{ $model->exists ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                        <a href="{{ route($routeBase.'.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection