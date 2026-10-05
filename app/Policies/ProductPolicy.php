<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('product.view');
    }

    public function create(User $user): bool
    {
        return $user->can('product.manage');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('product.manage');
    }

    /**
     * Produk yang sudah pernah terjual tidak boleh dihapus, hanya dinonaktifkan.
     * Transaksi lama harus tetap mereferensikan produk yang sama.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->can('product.manage') && ! $product->salesItems()->exists();
    }

    public function managePrices(User $user, Product $product): bool
    {
        return $user->can('product.manage');
    }

    public function deletePrice(User $user, Product $product): bool
    {
        // Harga yang sudah dipakai transaksi tidak boleh dihapus (PRD 02 §9).
        return $user->can('product.manage');
    }
}
