@extends('layouts.app')

@section('title', 'Booking')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Booking</h1>
        @can('create', App\Models\Booking::class)
            <a href="{{ route('bookings.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Buat Booking
            </a>
        @endcan
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="search" name="search" class="form-control"
                           placeholder="No. booking, nama customer" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['BOOKED','CONFIRMED','CHECKED_IN','IN_TREATMENT','COMPLETED','CANCELLED','NO_SHOW'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                {{ $status }}
                            </option>
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
                        <th>No. Booking</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Customer</th>
                        <th>Cabang</th>
                        <th class="text-center">Item</th>
                        <th>Status</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="font-monospace">{{ $booking->booking_number }}</td>
                            <td>{{ $booking->booking_date->format('d M Y') }}</td>
                            <td class="small">
                                {{ substr((string) $booking->start_time, 0, 5) }}
                                @if ($booking->end_time)
                                    — {{ substr((string) $booking->end_time, 0, 5) }}
                                @endif
                            </td>
                            <td>{{ $booking->customer->name ?? '—' }}</td>
                            <td>{{ $booking->branch->name ?? '—' }}</td>
                            <td class="text-center">{{ $booking->items->count() }}</td>
                            <td>
                                <span class="badge text-bg-{{ match ($booking->status) {
                                    'COMPLETED' => 'success',
                                    'CANCELLED', 'NO_SHOW' => 'danger',
                                    'BOOKED' => 'secondary',
                                    default => 'info',
                                } }}">{{ $booking->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('bookings.show', $booking) }}"
                                   class="btn btn-sm btn-outline-secondary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada booking
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($bookings->hasPages())
            <div class="card-footer">{{ $bookings->links() }}</div>
        @endif
    </div>
@endsection