<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §7 Customer Domain
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 50)->unique();
            $table->string('name', 191);
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('address')->nullable();
            $table->string('customer_type', 30)->default('RETAIL')->index()
                ->comment('RETAIL | MEMBER | CORPORATE | CLINIC');
            $table->string('source', 50)->nullable()->comment('Acquisition channel');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phone'], 'customers_phone_index');
            $table->index(['email'], 'customers_email_index');
            $table->index(['customer_type', 'status'], 'customers_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};