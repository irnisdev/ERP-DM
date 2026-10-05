<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §17 Bills & §18 Expense
 * bill_templates, bills, expense_categories, expenses
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->string('category', 50)->index()
                ->comment('BPJS | PAM | INTERNET | INSTALLMENT | PBB | ELECTRICITY | RENT | INSURANCE | OTHER');
            $table->string('frequency', 30)->default('MONTHLY')->index()
                ->comment('MONTHLY | QUARTERLY | SEMESTERLY | YEARLY | ONE_TIME');
            $table->decimal('default_amount', 20, 2)->nullable();
            $table->unsignedTinyInteger('default_due_day')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('financial_account_id')->nullable()
                ->constrained('financial_accounts')->nullOnDelete();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_template_id')->nullable()->constrained('bill_templates')->nullOnDelete();
            $table->string('bill_number', 50)->unique();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('issue_date')->nullable();
            $table->date('due_date');
            $table->decimal('amount', 20, 2);
            $table->decimal('penalty_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->string('status', 30)->default('UNPAID')->index()
                ->comment('UNPAID | PARTIALLY_PAID | PAID | OVERDUE | VOID');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'due_date'], 'bills_branch_due_date_index');
            $table->index(['status', 'due_date'], 'bills_status_due_date_index');
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('expense_categories')->nullOnDelete();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 50)->unique();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('amount', 20, 2);
            $table->date('expense_date');
            $table->foreignId('financial_account_id')->nullable()
                ->constrained('financial_accounts')->nullOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('DRAFT')->index()
                ->comment('DRAFT | SUBMITTED | APPROVED | REJECTED | PAID | CANCELLED');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'expense_date'], 'expenses_branch_date_index');
            $table->index(['status', 'expense_date'], 'expenses_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('bill_templates');
    }
};