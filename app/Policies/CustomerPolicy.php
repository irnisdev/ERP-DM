<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customer.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customer.view');
    }

    public function create(User $user): bool
    {
        return $user->can('customer.manage');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customer.manage');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customer.manage');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->can('customer.manage');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->isSuperAdmin();
    }
}