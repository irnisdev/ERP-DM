<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service.view');
    }

    public function view(User $user, Service $service): bool
    {
        return $user->can('service.view');
    }

    public function create(User $user): bool
    {
        return $user->can('service.manage');
    }

    public function update(User $user, Service $service): bool
    {
        return $user->can('service.manage');
    }

    public function delete(User $user, Service $service): bool
    {
        // Service yang sudah terjual atau sudah dibooking tidak boleh dihapus.
        return $user->can('service.manage')
            && ! $service->salesItems()->exists()
            && ! $service->bookingItems()->exists();
    }

    public function managePrices(User $user, Service $service): bool
    {
        return $user->can('service.manage');
    }

    public function deletePrice(User $user, Service $service): bool
    {
        return $user->can('service.manage');
    }
}
