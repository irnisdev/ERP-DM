@extends('layouts.app')

@section('title', 'Employee')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Employee</h1>
        @can('create', App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Tambah Employee
            </a>
        @endcan
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="search" name="search" class="form-control"
                           placeholder="Kode, nama, NIK, HP" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Cabang</label>
                    <select name="branch_id" class="form-select">
                        <option value="">Semua ({{ $branches->count() }})</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? '') == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="employment_status" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['ACTIVE', 'INACTIVE', 'RESIGNED', 'TERMINATED'] as $status)
                            <option value="{{ $status }}" @selected(($filters['employment_status'] ?? '') === $status)>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Cabang</th>
                        <th>Posisi</th>
                        <th>HP</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="font-monospace">{{ $employee->employee_code }}</td>
                            <td>
                                {{ $employee->full_name }}
                                @if ($employee->manager)
                                    <div class="small text-muted">Atasan: {{ $employee->manager->full_name }}</div>
                                @endif
                            </td>
                            <td>{{ $employee->branch->name ?? '—' }}</td>
                            <td>{{ $employee->position->name ?? '—' }}</td>
                            <td>{{ $employee->phone ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $employee->employment_status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                    {{ $employee->employment_status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('employees.show', $employee) }}"
                                   class="btn btn-sm btn-outline-secondary">Detail</a>
                                @can('update', $employee)
                                    <a href="{{ route('employees.edit', $employee) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada data employee
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="card-footer">{{ $employees->links() }}</div>
        @endif
    </div>
@endsection