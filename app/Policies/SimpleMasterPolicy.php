<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\ServiceCategory;
use App\Models\Unit;
use App\Models\User;

/**
 * Policy untuk master sederhana tanpa aturan bisnis khusus:
 * kategori produk/jasa, brand, unit.
 *
 * Modul permission ditentukan dari tipe model, sehingga satu policy cukup
 * untuk keempat tabel tersebut.
 */
class SimpleMasterPolicy
{
    public function viewAny(User $user, ?string $model = null): bool
    {
        return $user->can($this->module($model).'.view');
    }

    public function view(User $user, object $model): bool
    {
        return $user->can($this->module($model::class).'.view');
    }

    public function create(User $user, ?string $model = null): bool
    {
        return $user->can($this->module($model).'.manage');
    }

    public function update(User $user, object $model): bool
    {
        return $user->can($this->module($model::class).'.manage');
    }

    public function delete(User $user, object $model): bool
    {
        // Kategori yang sudah dipakai produk/jasa tidak boleh dihapus karena
        // FK ter-RESTRICT dan transaksi lama merujuk kategori tersebut.
        if ($model instanceof ProductCategory && $model->products()->exists()) {
            return false;
        }

        if ($model instanceof ServiceCategory && $model->services()->exists()) {
            return false;
        }

        return $user->can($this->module($model::class).'.manage');
    }

    private function module(?string $model): string
    {
        return match ($model) {
            ProductCategory::class, Brand::class, Unit::class => 'product',
            ServiceCategory::class => 'service',
            default => 'product',
        };
    }
}