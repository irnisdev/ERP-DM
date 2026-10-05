<?php

namespace App\Policies;

use App\Models\ServicePackage;
use App\Models\User;

class ServicePackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('package.view');
    }

    public function view(User $user, ServicePackage $package): bool
    {
        return $user->can('package.view');
    }

    public function create(User $user): bool
    {
        return $user->can('package.manage');
    }

    public function update(User $user, ServicePackage $package): bool
    {
        return $user->can('package.manage');
    }

    public function delete(User $user, ServicePackage $package): bool
    {
        return $user->can('package.manage') && ! $package->salesItems()->exists();
    }
}
