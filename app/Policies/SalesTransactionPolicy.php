<?php

namespace App\Policies;

use App\Models\SalesTransaction;
use App\Models\User;

class SalesTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.view');
    }

    public function view(User $user, SalesTransaction $transaction): bool
    {
        return $user->can('sales.view') && $this->sameBranch($user, $transaction);
    }

    public function create(User $user): bool
    {
        return $user->can('sales.create');
    }

    public function update(User $user, SalesTransaction $transaction): bool
    {
        return $user->can('sales.create') && $this->sameBranch($user, $transaction);
    }

    public function delete(User $user, SalesTransaction $transaction): bool
    {
        // Transaksi tidak pernah dihapus (PRD 02 §37.7).
        return false;
    }

    public function pay(User $user, SalesTransaction $transaction): bool
    {
        return $user->can('payment.manage') && $this->sameBranch($user, $transaction);
    }

    public function return(User $user, SalesTransaction $transaction): bool
    {
        return $user->can('sales.return') && $this->sameBranch($user, $transaction);
    }

    /**
     * Pembatalan hanya untuk transaksi yang belum dibayar penuh.
     */
    public function cancel(User $user, SalesTransaction $transaction): bool
    {
        return $user->can('sales.return')
            && $this->sameBranch($user, $transaction)
            && $transaction->status !== SalesTransaction::STATUS_CANCELLED;
    }

    private function sameBranch(User $user, SalesTransaction $transaction): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->employee !== null
            && $user->employee->branch_id === $transaction->branch_id;
    }
}
