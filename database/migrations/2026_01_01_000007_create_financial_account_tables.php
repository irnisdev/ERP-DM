<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §15 Payment & Financial Account (master)
 * banks, financial_accounts, payment_methods
 *
 * Catatan: tabel ini dibuat sebelum supplier karena supplier_bank_accounts
 * mereferensikan banks.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
        });

        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 50)->unique();
            $table->string('account_type', 30)->default('BANK')->index()
                ->comment('CASH | BANK | E_WALLET | OTHER');
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('account_number', 100)->nullable();
            $table->string('account_name', 191);
            $table->string('currency', 10)->default('IDR');
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->unique(['bank_id', 'account_number'], 'financial_accounts_bank_number_unique');
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('method_type', 30)->default('CASH')->index()
                ->comment('CASH | BANK_TRANSFER | E_WALLET | CARD | QRIS | OTHER');
            $table->foreignId('financial_account_id')->nullable()
                ->constrained('financial_accounts')->nullOnDelete();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('financial_accounts');
        Schema::dropIfExists('banks');
    }
};