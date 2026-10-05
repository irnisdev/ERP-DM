@extends('layouts.app')

@section('title', $transaction->invoice_number)

@section('content')
    @php use App\Models\Payment; @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 font-monospace">{{ $transaction->invoice_number }}</h1>
            <span class="text-muted">
                {{ $transaction->transaction_date->format('d M Y H:i') }} ·
                {{ $transaction->customer->name ?? 'Umum' }} ·
                {{ $transaction->branch->name }}
            </span>
        </div>
        <div class="d-flex gap-2">
            @can('cancel', $transaction)
                <form method="POST" action="{{ route('sales.cancel', $transaction) }}"
                      onsubmit="return confirm('Batalkan transaksi ini? Stok akan dikembalikan.')">
                    @csrf
                    <input type="hidden" name="reason" value="Dibatalkan dari halaman detail">
                    <button class="btn btn-outline-danger">
                        <i class="bi bi-x-circle me-1"></i>Batalkan
                    </button>
                </form>
            @endcan
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Item</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th><th>Tipe</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Diskon</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaction->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->item_name }}
                                        @if ($item->employee)
                                            <div class="small text-muted">{{ $item->employee->full_name }}</div>
                                        @endif
                                    </td>
                                    <td><span class="badge text-bg-light">{{ $item->item_type }}</span></td>
                                    <td class="text-end">{{ number_format((float) $item->unit_price, 0) }}</td>
                                    <td class="text-end">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $item->discount, 0) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $item->subtotal, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top">
                    <dl class="row mb-0 small">
                        <dt class="col-6">Subtotal</dt>
                        <dd class="col-6 text-end">{{ number_format((float) $transaction->subtotal, 0) }}</dd>
                        <dt class="col-6">Diskon</dt>
                        <dd class="col-6 text-end text-danger">{{ number_format((float) $transaction->discount, 0) }}</dd>
                        <dt class="col-6">Pajak</dt>
                        <dd class="col-6 text-end">{{ number_format((float) $transaction->tax, 0) }}</dd>
                        <dt class="col-6 fw-bold">Total</dt>
                        <dd class="col-6 text-end fw-bold">{{ number_format((float) $transaction->grand_total, 0) }}</dd>
                    </dl>
                </div>
            </div>

            @if ($commissions->isNotEmpty())
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Komisi</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Karyawan</th><th>Invoice</th>
                                    <th class="text-end">Komisi</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($commissions as $commission)
                                    <tr>
                                        <td>{{ $commission->employee->full_name ?? '—' }}</td>
                                        <td class="font-monospace small">
                                            {{ $commission->salesTransaction->invoice_number ?? '—' }}
                                        </td>
                                        <td class="text-end">{{ number_format((float) $commission->commission_amount, 0) }}</td>
                                        <td><span class="badge text-bg-light">{{ $commission->status }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">
                    Status
                    <span class="badge text-bg-{{ match ($transaction->status) {
                        'PAID' => 'success', 'PARTIALLY_PAID' => 'warning',
                        'CANCELLED' => 'danger', default => 'secondary',
                    } }} float-end">{{ $transaction->status }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Total</dt>
                        <dd class="col-7 text-end">{{ number_format((float) $transaction->grand_total, 0) }}</dd>
                        <dt class="col-5">Terbayar</dt>
                        <dd class="col-7 text-end">{{ number_format((float) $transaction->grand_total - $outstanding, 0) }}</dd>
                        <dt class="col-5">Sisa</dt>
                        <dd class="col-7 text-end {{ $outstanding > 0 ? 'text-danger fw-bold' : '' }}">
                            {{ number_format($outstanding, 0) }}
                        </dd>
                        <dt class="col-5">Kasir</dt>
                        <dd class="col-7 text-end">{{ $transaction->creator->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Pembayaran</div>
                <div class="card-body">
                    @forelse ($payments as $payment)
                        <div class="border rounded p-2 mb-2 small">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">{{ number_format((float) $payment->amount, 0) }}</span>
                                <span class="text-muted">{{ $payment->paymentMethod->name ?? '—' }}</span>
                            </div>
                            <div class="text-muted font-monospace">{{ $payment->payment_number }}</div>
                            <div class="text-muted">{{ $payment->payment_date->format('d M Y H:i') }}</div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Belum ada pembayaran.</p>
                    @endforelse
                </div>
            </div>

            @can('pay', $transaction)
                @if ($outstanding > 0 && $transaction->status !== 'CANCELLED')
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-semibold">Catat Pembayaran</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('sales.pay', $transaction) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small mb-1" for="payment_method_id">Metode</label>
                                    <select name="payment_method_id" id="payment_method_id"
                                            class="form-select form-select-sm" required>
                                        @foreach ($paymentMethods as $method)
                                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1" for="amount">Nominal</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                                           class="form-control form-control-sm"
                                           value="{{ $outstanding }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small mb-1" for="reference_number">No. referensi</label>
                                    <input type="text" name="reference_number" id="reference_number"
                                           class="form-control form-control-sm">
                                </div>
                                <button class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-save me-1"></i>Simpan Pembayaran
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    </div>
@endsection