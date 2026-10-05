<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customers)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q
                    ->where('customer_code', 'like', $search)
                    ->orWhere('name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('email', 'like', $search));
            })
            ->when($request->filled('customer_type'), fn ($q) => $q->where('customer_type', $request->string('customer_type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('master.customer.index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'customer_type', 'status']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('master.customer.form', [
            'customer' => new Customer([
                'customer_type' => 'RETAIL',
                'status' => 'ACTIVE',
            ]),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        // PRD 02 §8 — warning duplicate sebelum membuat record baru
        if (! $request->boolean('force_create') && $this->customers->hasDuplicates(
            $request->input('phone'),
            $request->input('email')
        )) {
            return back()
                ->withInput()
                ->with('duplicate_warning', 'Nomor HP atau email sudah terdaftar. '
                    .'Periksa kembali atau gunakan "Tetap buat" bila ini memang customer berbeda.');
        }

        $customer = $this->customers->create($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Customer {$customer->customer_code} berhasil dibuat.");
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        return view('master.customer.show', [
            'customer' => $customer,
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('master.customer.form', ['customer' => $customer]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Data customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        // Customer tidak pernah hard delete (PRD 02 §37.7)
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', "Customer {$customer->customer_code} dinonaktifkan.");
    }

    /**
     * Pengecekan duplicate untuk form (dipanggil via AJAX).
     */
    public function checkDuplicates(Request $request): JsonResponse
    {
        $duplicates = $this->customers->findDuplicates(
            $request->input('phone'),
            $request->input('email'),
            $request->integer('ignore_id') ?: null
        );

        return response()->json([
            'has_duplicates' => $duplicates->isNotEmpty(),
            'matches' => $duplicates->map(fn (Customer $c) => [
                'id' => $c->id,
                'customer_code' => $c->customer_code,
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
            ])->values(),
        ]);
    }
}