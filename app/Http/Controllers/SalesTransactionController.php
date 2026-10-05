<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCommission;
use App\Models\Payment;
use App\Models\SalesTransaction;
use App\Services\PaymentService;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesTransactionController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly SalesService $sales,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesTransaction::class);

        $user = $request->user();

        $transactions = SalesTransaction::query()
            ->with(['customer', 'branch', 'items'])
            ->select([
                'sales_transactions.*',
                // Total pembayaran selesai per invoice, tanpa N+1.
                'paid_amount' => Payment::completedSumSubquery(
                    Payment::SOURCE_SALES,
                    'sales_transactions.id'
                ),
            ])
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('branch_id', $user->employee?->branch_id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q
                    ->where('invoice_number', 'like', $search)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $search)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('transaction_date', $request->date('date')))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'status', 'branch_id', 'date']),
            'branches' => \App\Models\Branch::orderBy('name')->get(),
        ]);
    }

    public function show(SalesTransaction $transaction): View
    {
        $this->authorize('view', $transaction);

        $transaction->load(['customer', 'branch', 'creator', 'items.product', 'items.service', 'items.package', 'items.employee']);

        return view('sales.show', [
            'transaction' => $transaction,
            'payments' => Payment::for($transaction)->with('paymentMethod')->get(),
            'outstanding' => $this->payments->outstanding(Payment::SOURCE_SALES, $transaction, (float) $transaction->grand_total),
            'paymentMethods' => \App\Models\PaymentMethod::where('status', 'ACTIVE')->orderBy('name')->get(),
            'commissions' => $transaction->commissions()->with('employee')->get(),
        ]);
    }

    /**
     * Checkout dari booking yang sudah selesai.
     */
    public function fromBooking(CheckoutRequest $request, \App\Models\Booking $booking)
    {
        $request->assertBranchAccess();

        [$transaction] = $this->sales->checkout(
            $request->validated()['cart'],
            $request->contextData(),
            $request->paymentData(),
        );

        return redirect()
            ->route('sales.show', $transaction)
            ->with('success', "Transaksi dari booking {$booking->booking_number} berhasil dibuat.");
    }

    public function pay(Request $request, SalesTransaction $transaction): RedirectResponse
    {
        $this->authorize('pay', $transaction);

        $data = $request->validate([
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'financial_account_id' => ['nullable', 'exists:financial_accounts,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $method = \App\Models\PaymentMethod::findOrFail($data['payment_method_id']);
        $account = isset($data['financial_account_id'])
            ? \App\Models\FinancialAccount::find($data['financial_account_id'])
            : $method->financialAccount;

        DB::transaction(function () use ($data, $method, $account, $transaction, $request) {
            $this->payments->createFor(
                sourceType: Payment::SOURCE_SALES,
                source: $transaction,
                method: $method,
                amount: (float) $data['amount'],
                account: $account,
                referenceNumber: $data['reference_number'] ?? null,
                userId: $request->user()->id,
            );

            $this->payments->syncSourceStatus(Payment::SOURCE_SALES, $transaction);
        });

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function cancel(Request $request, SalesTransaction $transaction): RedirectResponse
    {
        $this->authorize('cancel', $transaction);

        $reason = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ])['reason'] ?? null;

        $this->sales->cancel($transaction, $reason);

        return back()->with('success', "Transaksi {$transaction->invoice_number} dibatalkan. Stok dikembalikan.");
    }
}
