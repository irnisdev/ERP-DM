<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §19 Payroll, §20 Commission, §21 Payslip
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payment_date')->nullable();
            $table->string('status', 30)->default('OPEN')->index()
                ->comment('OPEN | PROCESSING | APPROVED | PAID | CLOSED');
            $table->timestamps();

            $table->index(['period_start', 'period_end'], 'payroll_periods_range_index');
        });

        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->string('type', 30)->index()->comment('EARNING | DEDUCTION');
            $table->string('calculation_method', 50)->default('FIXED')->comment(
                'FIXED | PERCENTAGE | PER_DAY | PER_HOUR | FORMULA'
            );
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_bpjs')->default(false);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
        });

        Schema::create('employee_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('basic_salary', 20, 2);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date'], 'salary_structures_employee_date_index');
        });

        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained('salary_components')->restrictOnDelete();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('calculation_type', 50)->default('FIXED')->comment('FIXED | PERCENTAGE');
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->index(
                ['employee_id', 'salary_component_id', 'effective_date'],
                'salary_components_employee_comp_date_index'
            );
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->decimal('basic_salary', 20, 2)->default(0);
            $table->decimal('total_earnings', 20, 2)->default(0);
            $table->decimal('total_deductions', 20, 2)->default(0);
            $table->decimal('net_salary', 20, 2)->default(0);
            $table->string('status', 30)->default('DRAFT')->index()
                ->comment('DRAFT | REVIEW | APPROVED | PAID | CANCELLED');
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id'], 'payrolls_period_employee_unique');
            $table->index(['employee_id', 'payroll_period_id'], 'payrolls_employee_period_index');
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained('salary_components')->nullOnDelete();
            $table->string('component_name', 191)->comment('Snapshot nama komponen');
            $table->string('type', 30)->comment('EARNING | DEDUCTION');
            $table->decimal('amount', 20, 2);
            $table->string('source_type', 100)->nullable()
                ->comment('BASIC | ALLOWANCE | ATTENDANCE | OVERTIME | COMMISSION | BPJS | ADJUSTMENT');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['payroll_id', 'sort_order'], 'payroll_items_payroll_sort_index');
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->string('type', 30)->comment('EARNING | DEDUCTION');
            $table->string('description', 191);
            $table->decimal('amount', 20, 2);
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['payroll_id'], 'payroll_adjustments_payroll_index');
        });

        Schema::create('employee_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('sales_transaction_id')->constrained('sales_transactions')->restrictOnDelete();
            $table->foreignId('sales_transaction_item_id')->nullable()
                ->constrained('sales_transaction_items')->nullOnDelete();
            $table->decimal('commission_amount', 20, 2);
            $table->string('status', 30)->default('PENDING')->index()->comment('PENDING | INCLUDED | APPROVED | VOID');
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status'], 'commissions_employee_status_index');
            $table->index(['sales_transaction_id'], 'commissions_transaction_index');
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
            $table->string('commission_type', 30)->comment('PERCENTAGE | FIXED');
            $table->decimal('commission_value', 20, 2);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->index(['employee_id', 'service_id', 'effective_date'], 'commission_rules_lookup_index');
        });

        Schema::create('payslip_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->json('layout_config')->comment('Konfigurasi layout/field untuk PDF');
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('payslip_templates')->nullOnDelete();
            $table->string('payslip_number', 50)->unique();
            $table->date('issue_date');
            $table->decimal('gross_salary', 20, 2)->default(0);
            $table->decimal('total_deduction', 20, 2)->default(0);
            $table->decimal('net_salary', 20, 2)->default(0);
            $table->string('status', 30)->default('DRAFT')->index()
                ->comment('DRAFT | REVIEW | APPROVED | PUBLISHED | VOID');
            $table->string('pdf_path', 191)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('verification_code', 100)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'issue_date'], 'payslips_employee_date_index');
        });

        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $table->string('component_name', 191)->comment('Snapshot nama komponen');
            $table->string('type', 30)->comment('EARNING | DEDUCTION');
            $table->decimal('amount', 20, 2);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['payslip_id', 'sort_order'], 'payslip_items_payslip_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payslip_templates');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('employee_commissions');
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('employee_salary_structures');
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('payroll_periods');
    }
};