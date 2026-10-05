<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

/**
 * Penulis tunggal ledger inventory.
 *
 * Semua baris `inventory_movements` harus dibuat lewat sini agar format
 * quantity (signed) dan reference_type konsisten.
 */
class StockMovementWriter
{
    /**
     * @param  array<string, mixed>  $reference
     */
    public function write(
        Warehouse $warehouse,
        Product $product,
        string $movementType,
        float $quantity,
        array $reference = [],
        ?float $unitCost = null,
        ?string $notes = null
    ): InventoryMovement {
        $signed = $this->sign($movementType) * abs($quantity);

        return InventoryMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'movement_type' => $movementType,
            'quantity' => $signed,
            'unit_cost' => $unitCost,
            'reference_type' => $this->referenceType($reference),
            'reference_id' => $reference['id'] ?? null,
            'movement_date' => $reference['date'] ?? now()->toDateString(),
            'notes' => $notes ?? ($reference['notes'] ?? null),
            'created_by' => $reference['user_id'] ?? auth()->id(),
        ]);
    }

    /**
     * Pengeluaran (barang keluar) bernilai negatif.
     */
    private function sign(string $movementType): int
    {
        return match ($movementType) {
            InventoryMovement::TYPE_SALE,
            InventoryMovement::TYPE_TRANSFER_OUT,
            InventoryMovement::TYPE_RETURN_OUT => -1,
            default => 1,
        };
    }

    /**
     * @param  array<string, mixed>  $reference
     */
    private function referenceType(array $reference): ?string
    {
        $model = $reference['type'] ?? null;

        if ($model instanceof Model) {
            return $model->getMorphClass();
        }

        return is_string($model) ? $model : null;
    }
}