<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Services\BookingService;
use App\Support\BranchScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    public function index(Request $request, BranchScope $branchScope): View
    {
        $this->authorize('viewAny', Booking::class);

        $user = $request->user();

        $bookings = Booking::query()
            ->with(['customer', 'branch', 'items'])
            ->tap(fn ($q) => $branchScope->apply($user, $q))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q
                    ->where('booking_number', 'like', $search)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $search)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('booking_date', $request->date('date')))
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('booking.index', [
            'bookings' => $bookings,
            'filters' => $request->only(['search', 'status', 'branch_id', 'date']),
            'branches' => $this->branchOptions($request, $branchScope),
        ]);
    }

    public function create(Request $request, BranchScope $branchScope): View
    {
        $this->authorize('create', Booking::class);

        $booking = new Booking([
            'status' => Booking::STATUS_BOOKED,
            'booking_date' => now()->toDateString(),
        ]);

        if ($branchId = $branchScope->currentBranchId($request->user())) {
            $booking->branch_id = $branchId;
        }

        return view('booking.form', [
            'booking' => $booking,
            'branches' => $this->branchOptions($request, $branchScope),
            'customers' => Customer::where('status', 'ACTIVE')->orderBy('name')->limit(500)->get(),
            'services' => Service::where('status', 'ACTIVE')->orderBy('name')->get(),
            'packages' => ServicePackage::where('status', 'ACTIVE')->orderBy('name')->get(),
            'employees' => $this->therapistOptions($request, $branchScope),
            'selectedItems' => [],
        ]);
    }

    public function store(StoreBookingRequest $request, BranchScope $branchScope): RedirectResponse
    {
        $this->assertBranchAccess($request, $branchScope);

        $booking = DB::transaction(function () use ($request) {
            $booking = Booking::create(array_merge($request->headerData(), [
                'booking_number' => $this->bookings->nextBookingNumber(),
                'status' => Booking::STATUS_BOOKED,
                'created_by' => $request->user()->id,
            ]));

            foreach ($request->itemData() as $item) {
                $booking->items()->create($item);
            }

            return $booking->fresh(['items']);
        });

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', "Booking {$booking->booking_number} berhasil dibuat.");
    }

    public function show(Request $request, Booking $booking): View
    {
        $this->authorize('view', $booking);

        $booking->load(['customer', 'branch', 'items.service', 'items.package', 'items.employee']);

        return view('booking.show', [
            'booking' => $booking,
            'transitions' => $this->bookings->allowedTransitions($booking->status),
            'paymentMethods' => \App\Models\PaymentMethod::where('status', 'ACTIVE')->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Booking $booking, BranchScope $branchScope): View
    {
        $this->authorize('update', $booking);

        $booking->load('items');

        return view('booking.form', [
            'booking' => $booking,
            'branches' => $this->branchOptions($request, $branchScope),
            'customers' => Customer::where('status', 'ACTIVE')->orderBy('name')->limit(500)->get(),
            'services' => Service::where('status', 'ACTIVE')->orderBy('name')->get(),
            'packages' => ServicePackage::where('status', 'ACTIVE')->orderBy('name')->get(),
            'employees' => $this->therapistOptions($request, $branchScope),
            'selectedItems' => $booking->items->map(fn (BookingItem $i) => [
                'service_id' => $i->service_id,
                'package_id' => $i->package_id,
                'employee_id' => $i->employee_id,
                'scheduled_start' => $i->scheduled_start?->format('Y-m-d\TH:i'),
                'scheduled_end' => $i->scheduled_end?->format('Y-m-d\TH:i'),
            ])->all(),
        ]);
    }

    public function update(UpdateBookingRequest $request, Booking $booking): RedirectResponse
    {
        DB::transaction(function () use ($request, $booking) {
            $booking->update($request->headerData());
            $booking->items()->delete();

            foreach ($request->itemData() as $item) {
                $booking->items()->create($item);
            }
        });

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Booking berhasil diperbarui.');
    }

    /**
     * Satu endpoint untuk semua transisi status (PRD 02 §11).
     */
    public function transition(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('transition', $booking);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys([
                Booking::STATUS_CONFIRMED => 1,
                Booking::STATUS_CHECKED_IN => 1,
                Booking::STATUS_IN_TREATMENT => 1,
                Booking::STATUS_COMPLETED => 1,
                Booking::STATUS_CANCELLED => 1,
                Booking::STATUS_NO_SHOW => 1,
            ]))],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->bookings->transition($booking, $data['status'], $data['reason'] ?? null);

        return back()->with('success', "Status booking diubah menjadi {$data['status']}.");
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $this->authorize('delete', $booking);

        $booking->items()->delete();
        $booking->delete();

        return redirect()
            ->route('bookings.index')
            ->with('success', "Booking {$booking->booking_number} dihapus.");
    }

    private function assertBranchAccess(StoreBookingRequest $request, BranchScope $branchScope): void
    {
        if ($branchScope->allowed($request->user()) === null) {
            return;
        }

        abort_unless(
            $branchScope->canAccess($request->user(), (int) $request->input('branch_id')),
            403,
            'Anda tidak dapat membuat booking di luar cabang Anda.'
        );
    }

    private function branchOptions(Request $request, BranchScope $branchScope)
    {
        $query = Branch::where('status', 'ACTIVE')->orderBy('name');

        $allowed = $branchScope->allowed($request->user());
        if ($allowed !== null) {
            $query->whereIn('id', $allowed);
        }

        return $query->get();
    }

    /**
     * Therapist aktif di cabang yang diizinkan.
     */
    private function therapistOptions(Request $request, BranchScope $branchScope)
    {
        $query = Employee::where('status', 'ACTIVE')->orderBy('full_name');

        $allowed = $branchScope->allowed($request->user());
        if ($allowed !== null) {
            $query->whereIn('branch_id', $allowed);
        }

        return $query->get();
    }
}
