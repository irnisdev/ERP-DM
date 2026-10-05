@extends('layouts.app')

@section('title', 'Komisi')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Komisi</h1>
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Penjualan
        </a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Karyawan</label>
                    <select name="employee_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}"
                                @selected(($filters['employee_id'] ?? '') == $employee->id)>
                                {{ $employee->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
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
                        <th>Karyawan</th>
                        <th>Invoice</th>
                        <th>Waktu</th>
                        <th class="text-end">Komisi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($commissions as $commission)
                        <tr>
                            <td>{{ $commission->employee->full_name ?? '—' }}</td>
                            <td class="font-monospace small">
                                {{ $commission->salesTransaction->invoice_number ?? '—' }}
                            </td>
                            <td class="small">{{ $commission->created_at?->format('d M Y H:i') }}</td>
                            <td class="text-end fw-semibold">
                                {{ number_format((float) $commission->commission_amount, 0) }}
                            </td>
                            <td><span class="badge text-bg-light">{{ $commission->status }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada data komisi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($commissions->isNotEmpty())
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end">{{ number_format((float) $commissions->sum('commission_amount'), 0) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if ($commissions->hasPages())
            <div class="card-footer">{{ $commissions->links() }}</div>
        @endif
    </div>
@endsection