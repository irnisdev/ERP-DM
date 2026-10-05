<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierBankAccountRequest;
use App\Models\Bank;
use App\Models\Supplier;
use App\Models\SupplierBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierBankAccountController extends Controller
{
    public function index(Supplier $supplier): View
    {
        $this->authorize('viewBankAccounts', $supplier);

        return view('master.supplier.bank-accounts', [
            'supplier' => $supplier->load('bankAccounts.bank'),
            'banks' => Bank::where('status', 'ACTIVE')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreSupplierBankAccountRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('manageBankAccounts', $supplier);

        $data = $request->validated();
        $data['is_primary'] = $request->boolean('is_primary');

        DB::transaction(function () use ($supplier, $data) {
            if ($data['is_primary']) {
                $supplier->bankAccounts()->update(['is_primary' => false]);
            }

            $supplier->bankAccounts()->create($data);
        });

        return back()->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    public function destroy(Supplier $supplier, SupplierBankAccount $account): RedirectResponse
    {
        $this->authorize('manageBankAccounts', $supplier);

        abort_unless($account->supplier_id === $supplier->id, 404);

        $account->delete();

        return back()->with('success', 'Rekening bank berhasil dihapus.');
    }
}