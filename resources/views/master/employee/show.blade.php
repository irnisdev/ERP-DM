@extends('layouts.app')

@section('title', $employee->full_name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $employee->full_name }}</h1>
            <span class="font-monospace text-muted">{{ $employee->employee_code }}</span>
                <span class="badge text-bg-{{ $employee->employment_status === 'ACTIVE' ? 'success' : 'secondary' }} ms-2">
                    {{ $employee->employment_status }}
                </span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $employee)
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Data Pribadi</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">NIK</dt>
                        <dd class="col-sm-8">{{ $employee->nik ?? '—' }}</dd>

                        <dt class="col-sm-4">Gender</dt>
                        <dd class="col-sm-8">{{ $employee->gender === 'M' ? 'Laki-laki' : ($employee->gender === 'F' ? 'Perempuan' : '—') }}</dd>

                        <dt class="col-sm-4">TTL</dt>
                        <dd class="col-sm-8">
                            {{ $employee->birth_place ?? '—' }},
                            {{ $employee->birth_date?->format('d M Y') ?? '—' }}
                        </dd>

                        <dt class="col-sm-4">Status Kawin</dt>
                        <dd class="col-sm-8">{{ $employee->marital_status ?? '—' }}</dd>

                        <dt class="col-sm-4">HP</dt>
                        <dd class="col-sm-8">{{ $employee->phone ?? '—' }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $employee->email ?? '—' }}</dd>

                        <dt class="col-sm-4">Alamat</dt>
                        <dd class="col-sm-8">{{ $employee->address ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Organisasi</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Cabang</dt>
                        <dd class="col-sm-8">{{ $employee->branch->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Department</dt>
                        <dd class="col-sm-8">{{ $employee->department->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Posisi</dt>
                        <dd class="col-sm-8">{{ $employee->position->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Manager</dt>
                        <dd class="col-sm-8">{{ $employee->manager->full_name ?? '—' }}</dd>

                        <dt class="col-sm-4">Join</dt>
                        <dd class="col-sm-8">{{ $employee->join_date?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-4">Resign</dt>
                        <dd class="col-sm-8">{{ $employee->resign_date?->format('d M Y') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-family"
                            type="button" role="tab">Keluarga ({{ $employee->familyMembers->count() }})</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bpjs"
                            type="button" role="tab">BPJS ({{ $employee->insurances->count() }})</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shift"
                            type="button" role="tab">Shift ({{ $employee->shifts->count() }})</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-family" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-semibold">Keluarga</div>
                        <div class="card-body">
                            @if ($canManageFamily)
                                <form method="POST" action="{{ route('employees.family.store', $employee) }}"
                                      class="row g-2 mb-3">
                                    @csrf
                                    <div class="col-md-4">
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                               placeholder="Nama keluarga" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-3">
                                        <select name="relationship" class="form-select" required>
                                            <option value="">Hubungan…</option>
                                            @foreach (['Spouse' => 'Suami/Istri', 'Child' => 'Anak', 'Father' => 'Ayah',
                                                       'Mother' => 'Ibu', 'Sibling' => 'Saudara'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="nik" class="form-control" placeholder="NIK (opsional)">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-center">
                                        <div class="form-check">
                                            <input type="checkbox" name="is_dependent" value="1" id="is_dependent"
                                                   class="form-check-input">
                                            <label class="form-check-label small" for="is_dependent">Tanggungan</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-lg me-1"></i>Tambah Keluarga
                                        </button>
                                    </div>
                                </form>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr><th>Nama</th><th>Hubungan</th><th>NIK</th><th>Tanggungan</th>
                                            @if ($canManageFamily)<th></th>@endif</tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($employee->familyMembers as $member)
                                            <tr>
                                                <td>{{ $member->name }}</td>
                                                <td>{{ $member->relationship }}</td>
                                                <td>{{ $member->nik ?? '—' }}</td>
                                                <td>{{ $member->is_dependent ? 'Ya' : 'Tidak' }}</td>
                                                @if ($canManageFamily)
                                                    <td class="text-end">
                                                        <form method="POST" class="d-inline"
                                                              action="{{ route('employees.family.destroy', [$employee, $member]) }}">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger"
                                                                    onclick="return confirm('Hapus keluarga ini?')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data keluarga</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-bpjs" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-semibold">BPJS & Asuransi</div>
                        <div class="card-body">
                            @unless ($canManageInsurance)
                                <div class="alert alert-warning small">
                                    <i class="bi bi-lock me-1"></i>
                                    Data BPJS bersifat sensitif dan membutuhkan permission khusus.
                                </div>
                            @else
                                <form method="POST" action="{{ route('employees.insurances.store', $employee) }}"
                                      class="row g-2 mb-3">
                                    @csrf
                                    <div class="col-md-3">
                                        <select name="insurance_type" class="form-select" required>
                                            <option value="">Jenis…</option>
                                            @foreach (['BPJS_KES' => 'BPJS Kesehatan',
                                                       'BPJS_KETENAGAKERJAAN' => 'BPJS Ketenagakerjaan',
                                                       'ASURANSI' => 'Asuransi', 'OTHER' => 'Lainnya'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="provider" class="form-control" placeholder="Provider">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="membership_number" class="form-control"
                                               placeholder="No. Kartu" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" name="start_date" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-lg me-1"></i>Tambah BPJS
                                        </button>
                                    </div>
                                </form>
                            @endunless

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr><th>Jenis</th><th>Provider</th><th>No. Kartu</th><th>Periode</th><th>Status</th>
                                            @if ($canManageInsurance)<th></th>@endif</tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($employee->insurances as $insurance)
                                            <tr>
                                                <td>{{ str_replace('_', ' ', $insurance->insurance_type) }}</td>
                                                <td>{{ $insurance->provider ?? '—' }}</td>
                                                <td class="font-monospace">{{ $insurance->membership_number }}</td>
                                                <td>
                                                    {{ $insurance->start_date->format('M Y') }}
                                                    @if ($insurance->end_date)
                                                        — {{ $insurance->end_date->format('M Y') }}
                                                    @endif
                                                </td>
                                                <td>{{ $insurance->status }}</td>
                                                @if ($canManageInsurance)
                                                    <td class="text-end">
                                                        <form method="POST" class="d-inline"
                                                              action="{{ route('employees.insurances.destroy', [$employee, $insurance]) }}">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger"
                                                                    onclick="return confirm('Hapus BPJS ini?')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data BPJS</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-shift" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-semibold">Shift</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr><th>Shift</th><th>Jam</th><th>Istirahat</th><th>Efektif</th><th>Berakhir</th></tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($employee->shifts as $assignment)
                                            <tr>
                                                <td>{{ $assignment->shift->name ?? '—' }}</td>
                                                <td>{{ $assignment->shift->start_time }} — {{ $assignment->shift->end_time }}</td>
                                                <td>{{ $assignment->shift->break_minutes }} menit</td>
                                                <td>{{ $assignment->effective_date->format('d M Y') }}</td>
                                                <td>{{ $assignment->end_date?->format('d M Y') ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">
                                                Belum ada assignment shift
                                            </td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <p class="small text-muted mt-3 mb-0">
                                Manajemen shift dibangun pada Phase 6 (HR).
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold">Gaji & Dokumen</div>
                <div class="card-body text-muted small">
                    @if ($canViewSalary)
                        Salary structure, payroll, dan payslip dibangun pada Phase 6.
                    @else
                        <i class="bi bi-lock me-1"></i>
                        Data gaji bersifat sensitif dan membutuhkan permission khusus.
                    @endif
                    <br>
                    Upload dokumen (KTP, kontrak, sertifikat) dibangun pada Phase 7.
                </div>
            </div>
        </div>
    </div>
@endsection