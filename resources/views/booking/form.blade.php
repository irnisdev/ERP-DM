@extends('layouts.app')

@section('title', $booking->exists ? 'Edit Booking' : 'Buat Booking')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $booking->exists ? 'Edit Booking' : 'Buat Booking' }}</h1>
        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <form method="POST"
          action="{{ $booking->exists ? route('bookings.update', $booking) : route('bookings.store') }}">
        @csrf
        @if ($booking->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Data Booking</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" required
                                        class="form-select @error('customer_id') is-invalid @enderror">
                                    <option value="">—</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            @selected(old('customer_id', $booking->customer_id) == $customer->id)>
                                            {{ $customer->name }} — {{ $customer->customer_code }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="branch_id">Cabang <span class="text-danger">*</span></label>
                                <select name="branch_id" id="branch_id" required
                                        class="form-select @error('branch_id') is-invalid @enderror">
                                    <option value="">—</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected(old('branch_id', $booking->branch_id) == $branch->id)>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="booking_date">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="booking_date" id="booking_date" required
                                       class="form-control @error('booking_date') is-invalid @enderror"
                                       value="{{ old('booking_date', $booking->booking_date?->format('Y-m-d')) }}">
                                @error('booking_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="start_time">Mulai</label>
                                <input type="time" name="start_time" id="start_time"
                                       class="form-control @error('start_time') is-invalid @enderror"
                                       value="{{ old('start_time', $booking->start_time) }}">
                                @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="end_time">Selesai</label>
                                <input type="time" name="end_time" id="end_time"
                                       class="form-control @error('end_time') is-invalid @enderror"
                                       value="{{ old('end_time', $booking->end_time) }}">
                                @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="notes">Catatan</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes', $booking->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">
                        Item Treatment <span class="text-danger">*</span>
                    </div>
                    <div class="card-body">
                        @error('items')<div class="alert alert-danger small">{{ $message }}</div>@enderror
                        @error('items.0.service_id')<div class="alert alert-danger small">{{ $message }}</div>@enderror

                        <div id="booking-items"></div>

                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-3" id="add-item">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Item
                        </button>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i>{{ $booking->exists ? 'Simpan Perubahan' : 'Simpan Booking' }}
                        </button>
                        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const services = @json($services->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'pricing' => $s->pricing_method,
            ]));
            const packages = @json($packages->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name,
            ]));
            const employees = @json($employees->map(fn ($e) => [
                'id' => $e->id, 'name' => $e->full_name,
            ]));
            const existing = @json($selectedItems);

            const container = document.getElementById('booking-items');
            let index = 0;

            function options(list, selected, blank) {
                let html = blank === false ? '' : '<option value="">' + (blank || '—') + '</option>';
                list.forEach((o) => {
                    html += '<option value="' + o.id + '"' +
                        (String(o.id) === String(selected) ? ' selected' : '') + '>' + o.name + '</option>';
                });
                return html;
            }

            function addItem(item) {
                item = item || {};
                const i = index++;
                const row = document.createElement('div');
                row.className = 'border rounded p-2 mb-2';
                row.innerHTML =
                    '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="small text-muted">Item ' + (i + 1) + '</span>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" data-remove>' +
                    '<i class="bi bi-x"></i></button></div>' +
                    '<select name="items[' + i + '][service_id]" class="form-select form-select-sm mb-1">' +
                    options(services, item.service_id, 'Service') + '</select>' +
                    '<select name="items[' + i + '][package_id]" class="form-select form-select-sm mb-1">' +
                    options(packages, item.package_id, 'Package') + '</select>' +
                    '<select name="items[' + i + '][employee_id]" class="form-select form-select-sm mb-1">' +
                    options(employees, item.employee_id, 'Therapist') + '</select>' +
                    '<input type="datetime-local" name="items[' + i + '][scheduled_start]" class="form-control form-control-sm mb-1" ' +
                    'value="' + (item.scheduled_start || '') + '">' +
                    '<input type="datetime-local" name="items[' + i + '][scheduled_end]" class="form-control form-control-sm" ' +
                    'value="' + (item.scheduled_end || '') + '">';

                container.appendChild(row);
            }

            container.addEventListener('click', function (e) {
                if (e.target.closest('[data-remove]')) e.target.closest('.border').remove();
            });

            document.getElementById('add-item').addEventListener('click', () => addItem());

            if (existing.length) existing.forEach(addItem); else addItem();
        })();
    </script>
@endpush