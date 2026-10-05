@extends('layouts.app')

@section('title', $booking->booking_number)

@section('content')
    @php
        use App\Models\Booking;

        $labels = [
            'BOOKED' => 'Dipesan',
            'CONFIRMED' => 'Dikonfirmasi',
            'CHECKED_IN' => 'Check-in',
            'IN_TREATMENT' => 'Dalam Treatment',
            'COMPLETED' => 'Selesai',
            'CANCELLED' => 'Dibatalkan',
            'NO_SHOW' => 'Tidak Datang',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 font-monospace">{{ $booking->booking_number }}</h1>
            <span class="text-muted">
                {{ $booking->customer->name }} · {{ $booking->branch->name }} ·
                {{ $booking->booking_date->format('d M Y') }}
            </span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $booking)
                <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">
                    Item Treatment
                    <span class="badge text-bg-light float-end">{{ $booking->items->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Treatment</th><th>Tipe</th><th>Therapist</th>
                                <th>Jadwal</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($booking->items as $item)
                                <tr>
                                    <td>{{ $item->service->name ?? $item->package->name ?? '—' }}</td>
                                    <td><span class="badge text-bg-light">{{ $item->service_id ? 'SERVICE' : 'PACKAGE' }}</span></td>
                                    <td>{{ $item->employee->full_name ?? '—' }}</td>
                                    <td class="small">
                                        {{ $item->scheduled_start?->format('d M Y H:i') ?? '—' }}
                                        @if ($item->scheduled_end)
                                            <br>s/d {{ $item->scheduled_end->format('H:i') }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ $item->status === 'COMPLETED' ? 'success' : 'light' }}">
                                            {{ $labels[$item->status] ?? $item->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada item</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Alur Treatment</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-2 small">
                        @foreach (['BOOKED','CONFIRMED','CHECKED_IN','IN_TREATMENT','COMPLETED'] as $step)
                            @php $done = array_search($booking->status, array_keys($labels), true) >= array_search($step, array_keys($labels), true); @endphp
                            <span class="badge text-bg-{{ $booking->status === $step ? 'primary' : ($done ? 'success' : 'light') }}">
                                {{ $labels[$step] }}
                            </span>
                            @if ($step !== 'COMPLETED')
                                <i class="bi bi-chevron-right"></i>
                            @endif
                        @endforeach
                    </div>

                    @if (in_array($booking->status, [Booking::STATUS_CANCELLED, Booking::STATUS_NO_SHOW], true))
                        <div class="alert alert-danger mt-3 mb-0 small">
                            <i class="bi bi-x-circle me-1"></i>
                            Booking ini tidak menghasilkan pendapatan dan tidak bisa diproses ke POS.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">
                    Status
                    <span class="badge text-bg-{{ $booking->generatesRevenue() ? 'info' : 'danger' }} float-end">
                        {{ $labels[$booking->status] ?? $booking->status }}
                    </span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Tanggal</dt>
                        <dd class="col-7 text-end">{{ $booking->booking_date->format('d M Y') }}</dd>
                        <dt class="col-5">Jam</dt>
                        <dd class="col-7 text-end">
                            {{ substr((string) $booking->start_time, 0, 5) ?: '—' }}
                            @if ($booking->end_time) — {{ substr((string) $booking->end_time, 0, 5) }} @endif
                        </dd>
                        <dt class="col-5">Dibuat oleh</dt>
                        <dd class="col-7 text-end">{{ $booking->creator->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            @can('transition', $booking)
                @if ($transitions)
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white fw-semibold">Ubah Status</div>
                        <div class="card-body d-grid gap-2">
                            @foreach ($transitions as $target)
                                <form method="POST" action="{{ route('bookings.transition', $booking) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $target }}">
                                    @if (in_array($target, ['CANCELLED', 'NO_SHOW'], true))
                                        <input type="text" name="reason" class="form-control form-control-sm mb-1"
                                               placeholder="Alasan (opsional)">
                                    @endif
                                    <button class="btn btn-sm w-100 {{ in_array($target, ['CANCELLED','NO_SHOW']) ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                                        {{ $labels[$target] ?? $target }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endcan

            @can('createSalesFrom', $booking)
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Buat Invoice</div>
                    <div class="card-body">
                        @php $billable = $booking->items->where('status', 'COMPLETED'); @endphp
                        @if ($billable->isEmpty())
                            <p class="small text-muted mb-0">
                                Item harus berstatus <strong>Selesai</strong> sebelum bisa dijadikan invoice.
                            </p>
                        @else
                            <p class="small">
                                {{ $billable->count() }} item selesai siap dijadikan invoice POS.
                            </p>
                            <a href="{{ route('pos.create') }}?booking_id={{ $booking->id }}"
                               class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-receipt me-1"></i>Buka POS
                            </a>
                        @endif
                    </div>
                </div>
            @endcan
        </div>
    </div>
@endsection