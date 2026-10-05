<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerPermissionGates();
    }

    private function registerPolicies(): void
    {
        foreach ([
            \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
            \App\Models\Employee::class => \App\Policies\EmployeePolicy::class,
            \App\Models\Product::class => \App\Policies\ProductPolicy::class,
            \App\Models\Service::class => \App\Policies\ServicePolicy::class,
            \App\Models\ServicePackage::class => \App\Policies\ServicePackagePolicy::class,
            \App\Models\Supplier::class => \App\Policies\SupplierPolicy::class,
            \App\Models\ProductCategory::class => \App\Policies\SimpleMasterPolicy::class,
            \App\Models\Brand::class => \App\Policies\SimpleMasterPolicy::class,
            \App\Models\Unit::class => \App\Policies\SimpleMasterPolicy::class,
            \App\Models\ServiceCategory::class => \App\Policies\SimpleMasterPolicy::class,
        ] as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Setiap permission dari tabel permissions dipetakan menjadi Gate ability
     * bernama sama (mis. "customer.manage"), sehingga Policy cukup menulis
     * $user->can('customer.manage').
     *
     * Super admin (OWNER / SUPER_ADMIN) melewati seluruh pemeriksaan.
     */
    private function registerPermissionGates(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            // Nama permission selalu mengandung titik (module.action).
            if (str_contains($ability, '.') && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}