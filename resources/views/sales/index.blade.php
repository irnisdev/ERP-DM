@extends('layouts.app')

@section('title', 'Penjualan')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Penjualan</h1>
        @can('create', App\Models\SalesTransaction::class)
            <a href="{{ route('pos.create') }}" class="btn btn-primary">
                <i class="bi bi-cart-plus me-1"></i>Buka POS
            </a>
        @endcan
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="search" name="search" class="form-control"
                           placeholder="No. invoice, nama customer" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['DRAFT', 'PARTIALLY_PAID', 'PAID', 'CANCELLED', 'REFUNDED'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Cabang</label>
                    <select name="branch_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? '') == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ $filters['date'] ?? '' }}">
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
                        <th>Invoice</th>
                        <th>Waktu</th>
                        <th>Customer</th>
                        <th>Cabang</th>
                        <th class="text-center">Item</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Terbayar</th>
                        <th>Status</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="font-monospace">{{ $transaction->invoice_number }}</td>
                            <td class="small">{{ $transaction->transaction_date->format('d M Y H:i') }}</td>
                            <td>{{ $transaction->customer->name ?? 'Umum' }}</td>
                            <td>{{ $transaction->branch->name ?? '—' }}</td>
                            <td class="text-center">{{ $transaction->items_count ?? $transaction->items->count() }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $transaction->grand_total, 0) }}</td>
                            <td class="text-end">{{ number_format((float) $transaction->paid_amount, 0) }}</td>
                            <td>
                                <span class="badge text-bg-{{ match ($transaction->status) {
                                    'PAID' => 'success',
                                    'PARTIALLY_PAID' => 'warning',
                                    'CANCELLED' => 'danger',
                                    default => 'secondary',
                                } }}">{{ $transaction->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('sales.show', $transaction) }}"
                                   class="btn btn-sm btn-outline-secondary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada transaksi penjualan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="card-footer">{{ $transactions->links() }}</div>
        @endif
    </div>
@endsection