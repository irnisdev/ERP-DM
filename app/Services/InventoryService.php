<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * InventoryService — satu-satunya jalan lawful mengubah stok.
 *
 * PRD 01 §12: `inventory_movements` adalah ledger, `inventory_stocks` adalah
 * current balance cache yang harus konsisten dengan ledger.
 * PRD 02 §37.8: jangan update stock langsung tanpa inventory movement.
 *
 * Setiap perubahan stok menulis ledger DAN memperbarui cache dalam satu
 * transaksi. Metode public di sini selalu dijalankan di dalam transaksi
 * pemanggil (mis. SalesService::checkout).
 */
class InventoryService
{
    public function __construct(private readonly StockMovementWriter $ledger)
    {
    }

    /**
     * Baris stok dengan row lock, dibuat bila belum ada.
     *
     * Lock_for_update mencegah dua transaksi bersamaan membaca stok lama yang
     * sama lalu oversell.
     */
    public function lockStock(Warehouse $warehouse, Product $product): InventoryStock
    {
        $stock = InventoryStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->first();

        if ($stock === null) {
            $stock = InventoryStock::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]);

            // Baris baru dibuat di dalam transaksi; lock sudah otomatis dipegang.
            return $stock;
        }

        return $stock;
    }

    /**
     * Memastikan stok cukup sebelum transaksi dijalankan.
     *
     * @param  array<int, array{product: Product, quantity: float}>  $lines
     */
    public function assertStockAvailable(Warehouse $warehouse, array $lines, bool $allowNegative = false): void
    {
        if ($allowNegative) {
            return;
        }

        foreach ($lines as $line) {
            $stock = InventoryStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $line['product']->id)
                ->lockForUpdate()
                ->first();

            $available = $stock ? $stock->availableQuantity() : 0.0;

            if ($available < $line['quantity']) {
                throw ValidationException::withMessages([
                    'items' => sprintf(
                        'Stok %s tidak cukup. Tersedia %s, diminta %s.',
                        $line['product']->name,
                        rtrim(rtrim(number_format($available, 4), '0'), '.'),
                        rtrim(rtrim(number_format($line['quantity'], 4), '0'), '.')
                    ),
                ]);
            }
        }
    }

    /**
     * Mengurangi stok dan menulis ledger SALE.
     *
     * @param  array<string, mixed>  $reference
     */
    public function deduct(
        Warehouse $warehouse,
        Product $product,
        float $quantity,
        array $reference = [],
        bool $allowNegative = false,
        ?float $unitCost = null
    ): InventoryMovement {
        $stock = $this->lockStock($warehouse, $product);

        if ($stock->availableQuantity() < $quantity && ! $allowNegative) {
            throw ValidationException::withMessages([
                'items' => "Stok {$product->name} tidak cukup.",
            ]);
        }

        $stock->update(['quantity' => (float) $stock->quantity - $quantity]);

        return $this->ledger->write(
            warehouse: $warehouse,
            product: $product,
            movementType: InventoryMovement::TYPE_SALE,
            quantity: -$quantity,
            reference: $reference,
            unitCost: $unitCost,
        );
    }

    /**
     * Menambah stok dan menulis ledger PURCHASE.
     *
     * @param  array<string, mixed>  $reference
     */
    public function increase(
        Warehouse $warehouse,
        Product $product,
        float $quantity,
        string $movementType,
        array $reference = [],
        ?float $unitCost = null
    ): InventoryMovement {
        $stock = $this->lockStock($warehouse, $product);

        $stock->update(['quantity' => (float) $stock->quantity + $quantity]);

        return $this->ledger->write(
            warehouse: $warehouse,
            product: $product,
            movementType: $movementType,
            quantity: abs($quantity),
            reference: $reference,
            unitCost: $unitCost,
        );
    }

    /**
     * Mengecek satu baris stok tanpa mengubah apa pun.
     *
     * Dipakai form POS supaya user tahu shortfall sebelum checkout.
     */
    public function availableQuantity(Warehouse $warehouse, Product $product): float
    {
        $stock = InventoryStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        return $stock ? $stock->availableQuantity() : 0.0;
    }

    /**
     * Rekonsiliasi cache dengan ledger.
     *
     * Berguna sebagai self-check dan untuk diagnose saat selisih ditemukan.
     * Cache = SUM(quantity) dari seluruh movement untuk warehouse+product.
     */
    public function reconcile(Warehouse $warehouse, Product $product): float
    {
        $ledgerQuantity = (float) InventoryMovement::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->sum('quantity');

        InventoryStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->update(['quantity' => $ledgerQuantity]);

        return $ledgerQuantity;
    }

    /**
     * Stok produk yang sudah di-reserve (mis. untuk booking) mengurangi
     * ketersediaan tanpa mengurangi quantity fisik.
     */
    public function reserve(Warehouse $warehouse, Product $product, float $quantity): InventoryStock
    {
        $stock = $this->lockStock($warehouse, $product);

        if ($stock->availableQuantity() < $quantity) {
            throw ValidationException::withMessages([
                'items' => "Stok {$product->name} tidak cukup untuk di-reserve.",
            ]);
        }

        $stock->update(['reserved_quantity' => (float) $stock->reserved_quantity + $quantity]);

        return $stock;
    }

    public function releaseReservation(Warehouse $warehouse, Product $product, float $quantity): InventoryStock
    {
        $stock = $this->lockStock($warehouse, $product);

        $stock->update([
            'reserved_quantity' => max(0, (float) $stock->reserved_quantity - $quantity),
        ]);

        return $stock;
    }

    /**
     * Selling price master saat ini, dipakai sebagai fallback POS.
     */
    public function suggestedUnitPrice(Product $product, string $priceType = 'RETAIL'): float
    {
        $price = $product->priceFor($priceType);

        return $price ? (float) $price->price : (float) $product->default_sell_price;
    }
}