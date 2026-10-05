@php
    /** @var App\Models\Employee $employee */
    $action = $employee->exists ? route('employees.update', $employee) : route('employees.store');
@endphp

@extends('layouts.app')

@section('title', $employee->exists ? 'Edit Employee' : 'Tambah Employee')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $employee->exists ? 'Edit Employee' : 'Tambah Employee' }}</h1>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <form method="POST" action="{{ $action }}">
        @csrf
        @if ($employee->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Data Pribadi</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="employee_code">
                                    Kode Employee
                                    @unless ($employee->exists)
                                        <span class="text-muted small">(kosongkan untuk generate)</span>
                                    @endunless
                                </label>
                                <input type="text" name="employee_code" id="employee_code"
                                       class="form-control @error('employee_code') is-invalid @enderror"
                                       value="{{ old('employee_code', $employee->employee_code) }}"
                                       @if ($employee->exists) readonly @endif>
                                @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="full_name">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="full_name" required
                                       class="form-control @error('full_name') is-invalid @enderror"
                                       value="{{ old('full_name', $employee->full_name) }}">
                                @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="nik">NIK</label>
                                <input type="text" name="nik" id="nik"
                                       class="form-control @error('nik') is-invalid @enderror"
                                       value="{{ old('nik', $employee->nik) }}">
                                @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="gender">Gender</label>
                                <select name="gender" id="gender" class="form-select">
                                    <option value="">—</option>
                                    @foreach (['M' => 'Laki-laki', 'F' => 'Perempuan'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('gender', $employee->gender) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="birth_place">Tempat Lahir</label>
                                <input type="text" name="birth_place" id="birth_place" class="form-control"
                                       value="{{ old('birth_place', $employee->birth_place) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="birth_date">Tanggal Lahir</label>
                                <input type="date" name="birth_date" id="birth_date"
                                       class="form-control @error('birth_date') is-invalid @enderror"
                                       value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}">
                                @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="marital_status">Status Kawin</label>
                                <select name="marital_status" id="marital_status" class="form-select">
                                    <option value="">—</option>
                                    @foreach (['SINGLE' => 'Belum Menikah', 'MARRIED' => 'Menikah',
                                               'DIVORCED' => 'Cerai', 'WIDOWED' => 'Janda/Duda'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('marital_status', $employee->marital_status) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="phone">No. HP</label>
                                <input type="text" name="phone" id="phone" class="form-control"
                                       value="{{ old('phone', $employee->phone) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control"
                                       value="{{ old('email', $employee->email) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="address">Alamat</label>
                                <textarea name="address" id="address" rows="2" class="form-control">{{ old('address', $employee->address) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Organisasi & Status</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="branch_id">Cabang <span class="text-danger">*</span></label>
                                <select name="branch_id" id="branch_id" required
                                        class="form-select @error('branch_id') is-invalid @enderror">
                                    <option value="">—</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected(old('branch_id', $employee->branch_id) == $branch->id)>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="department_id">Department</label>
                                <select name="department_id" id="department_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected(old('department_id', $employee->department_id) == $department->id)>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="position_id">Posisi</label>
                                <select name="position_id" id="position_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach ($positions as $position)
                                        <option value="{{ $position->id }}" @selected(old('position_id', $employee->position_id) == $position->id)>
                                            {{ $position->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="manager_id">Manager (Atasan)</label>
                                <select name="manager_id" id="manager_id"
                                        class="form-select @error('manager_id') is-invalid @enderror">
                                    <option value="">—</option>
                                    @foreach ($managers as $manager)
                                        <option value="{{ $manager->id }}" @selected(old('manager_id', $employee->manager_id) == $manager->id)>
                                            {{ $manager->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('manager_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="join_date">Tanggal Join</label>
                                <input type="date" name="join_date" id="join_date" class="form-control"
                                       value="{{ old('join_date', $employee->join_date?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="resign_date">Tanggal Resign</label>
                                <input type="date" name="resign_date" id="resign_date"
                                       class="form-control @error('resign_date') is-invalid @enderror"
                                       value="{{ old('resign_date', $employee->resign_date?->format('Y-m-d')) }}">
                                @error('resign_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="employment_status">Status Pekerjaan <span class="text-danger">*</span></label>
                                <select name="employment_status" id="employment_status" required
                                        class="form-select @error('employment_status') is-invalid @enderror">
                                    @foreach (['ACTIVE', 'INACTIVE', 'RESIGNED', 'TERMINATED'] as $status)
                                        <option value="{{ $status }}" @selected(old('employment_status', $employee->employment_status) === $status)>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="status">Status Record <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select">
                                    @foreach (['ACTIVE', 'INACTIVE'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $employee->status) === $status)>
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
                @if ($employee->exists)
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h6 text-muted">Tindak lanjut</h2>
                            <p class="small text-muted">
                                Setelah employee dibuat, tambahkan keluarga, BPJS, dan dokumen
                                dari halaman detail.
                            </p>
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary w-100">
                                Buka Detail
                            </a>
                        </div>
                    </div>
                @endif

                <div class="card shadow-sm mt-3">
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>{{ $employee->exists ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection