<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §12 Inventory
 * warehouses, inventory_stocks, inventory_movements, stock_transfers,
 * stock_transfer_items, stock_opnames, stock_opname_items
 *
 * inventory_movements = ledger (sumber kebenaran)
 * inventory_stocks = current balance cache yang harus konsisten dengan ledger
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 191);
            $table->string('warehouse_type', 30)->default('STORAGE')->index()
                ->comment('STORAGE | DISPLAY | TREATMENT_ROOM | QUARANTINE');
            $table->text('address')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->unique(['branch_id', 'code'], 'warehouses_branch_code_unique');
        });

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('reserved_quantity', 20, 4)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id'], 'inventory_stocks_warehouse_product_unique');
            $table->index(['product_id'], 'inventory_stocks_product_index');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('movement_type', 30)->index()->comment(
                'PURCHASE | SALE | TRANSFER_IN | TRANSFER_OUT | RETURN_IN | RETURN_OUT | ADJUSTMENT | STOCK_OPNAME'
            );
            $table->decimal('quantity', 20, 4)->comment('Signed: positif IN, negatif OUT');
            $table->decimal('unit_cost', 20, 2)->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('movement_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'movement_date'], 'inv_movements_stock_date_index');
            $table->index(['reference_type', 'reference_id'], 'inv_movements_reference_index');
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('transfer_date');
            $table->string('status', 30)->default('DRAFT')->index()
                ->comment('DRAFT | SUBMITTED | APPROVED | IN_TRANSIT | RECEIVED | CANCELLED');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['from_warehouse_id', 'transfer_date'], 'stock_transfers_from_date_index');
            $table->index(['to_warehouse_id', 'transfer_date'], 'stock_transfers_to_date_index');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->timestamps();
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number', 50)->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('opname_date');
            $table->string('status', 30)->default('DRAFT')->index()
                ->comment('DRAFT | SUBMITTED | APPROVED | POSTED | CANCELLED');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'opname_date'], 'stock_opnames_warehouse_date_index');
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('system_quantity', 20, 4)->default(0);
            $table->decimal('physical_quantity', 20, 4)->default(0);
            $table->decimal('variance_quantity', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_opname_id', 'product_id'], 'stock_opname_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('warehouses');
    }
};