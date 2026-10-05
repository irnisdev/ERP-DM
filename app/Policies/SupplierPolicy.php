<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supplier.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.view');
    }

    public function create(User $user): bool
    {
        return $user->can('supplier.manage');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.manage');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.manage') && ! $supplier->purchaseOrders()->exists();
    }

    /**
     * Nomor rekening adalah data sensitif (PRD 02 §28).
     */
    public function viewBankAccounts(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.view') && $user->hasPermission('sensitive.rekening');
    }

    public function manageBankAccounts(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.manage') && $user->hasPermission('sensitive.rekening');
    }
}
