<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §15 payments, §16 bank mutation & reconciliation
 * payments, bank_transactions, bank_reconciliations
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->string('transaction_type', 50)->index()
                ->comment('SALES | PURCHASE | EXPENSE | BILL | PAYROLL | OTHER');
            $table->unsignedBigInteger('transaction_id')->nullable()
                ->comment('Logical reference ke tabel sumber, divalidasi service layer');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('financial_account_id')->nullable()
                ->constrained('financial_accounts')->nullOnDelete();
            $table->decimal('amount', 20, 2);
            $table->dateTime('payment_date');
            $table->string('reference_number', 100)->nullable();
            $table->string('status', 30)->default('COMPLETED')->index()
                ->comment('PENDING | COMPLETED | FAILED | VOID');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transaction_type', 'transaction_id'], 'payments_source_index');
            $table->index(['financial_account_id', 'payment_date'], 'payments_account_date_index');
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->date('transaction_date');
            $table->string('transaction_type', 30)->index()
                ->comment('CREDIT | DEBIT | TRANSFER_IN | TRANSFER_OUT | FEE | INTEREST | ADJUSTMENT');
            $table->decimal('amount', 20, 2);
            $table->text('description')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->decimal('balance', 20, 2)->nullable();
            $table->string('external_reference', 191)->nullable()->comment('Untuk deteksi duplicate import');
            $table->string('reconciliation_status', 30)->default('UNMATCHED')->index()
                ->comment('UNMATCHED | PARTIALLY_MATCHED | MATCHED | IGNORED');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['financial_account_id', 'transaction_date'], 'bank_tx_account_date_index');
            $table->index(['external_reference'], 'bank_tx_external_ref_index');
        });

        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_transaction_id')->constrained('bank_transactions')->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('matched_amount', 20, 2);
            $table->string('status', 30)->default('MATCHED')->index()->comment('MATCHED | UNMATCHED | VOID');
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['bank_transaction_id'], 'bank_recon_tx_index');
            $table->index(['payment_id'], 'bank_recon_payment_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('payments');
    }
};