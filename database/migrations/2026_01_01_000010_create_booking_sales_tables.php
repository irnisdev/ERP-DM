<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §13 Booking / Treatment & §14 Sales
 * bookings, booking_items, sales_transactions, sales_transaction_items,
 * sales_returns, sales_return_items
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->date('booking_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status', 30)->default('BOOKED')->index()->comment(
                'BOOKED | CONFIRMED | CHECKED_IN | IN_TREATMENT | COMPLETED | CANCELLED | NO_SHOW'
            );
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'booking_date'], 'bookings_branch_date_index');
            $table->index(['customer_id', 'status'], 'bookings_customer_status_index');
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->string('status', 30)->default('BOOKED')->index()->comment(
                'BOOKED | CONFIRMED | CHECKED_IN | IN_TREATMENT | COMPLETED | CANCELLED | NO_SHOW'
            );
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status'], 'booking_items_booking_status_index');
            $table->index(['employee_id', 'scheduled_start'], 'booking_items_employee_schedule_index');
        });

        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('transaction_date');
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount', 20, 2)->default(0);
            $table->decimal('tax', 20, 2)->default(0);
            $table->decimal('grand_total', 20, 2)->default(0);
            $table->string('status', 30)->default('DRAFT')->index()->comment(
                'DRAFT | PAID | PARTIALLY_PAID | CANCELLED | REFUNDED'
            );
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'transaction_date'], 'sales_transactions_branch_date_index');
            $table->index(['customer_id', 'status'], 'sales_transactions_customer_status_index');
        });

        Schema::create('sales_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_transaction_id')->constrained('sales_transactions')->cascadeOnDelete();
            $table->string('item_type', 30)->index()->comment('PRODUCT | SERVICE | PACKAGE');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->string('item_name', 191)->comment('Snapshot nama item');
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 2)->comment('Snapshot harga transaksi');
            $table->decimal('discount', 20, 2)->default(0);
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('source_booking_item_id')->nullable()
                ->constrained('booking_items')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sales_transaction_id', 'item_type'], 'sales_items_transaction_type_index');
            $table->index(['service_id'], 'sales_items_service_index');
            $table->index(['product_id'], 'sales_items_product_index');
        });

        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('sales_transaction_id')->constrained('sales_transactions')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->date('return_date');
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('DRAFT')->index()->comment('DRAFT | POSTED | CANCELLED');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'return_date'], 'sales_returns_branch_date_index');
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignId('sales_transaction_item_id')->constrained('sales_transaction_items')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->decimal('amount', 20, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('sales_transaction_items');
        Schema::dropIfExists('sales_transactions');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
    }
};