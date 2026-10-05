<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §6 Employee Domain
 * employees, employee_family_members, employee_insurances
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->string('nik', 30)->nullable();
            $table->string('full_name', 191);
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable()->comment('M | F');
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('address')->nullable();
            $table->string('marital_status', 30)->nullable()->comment('SINGLE | MARRIED | DIVORCED | WIDOWED');
            $table->string('employment_status', 30)->default('ACTIVE')->index()
                ->comment('ACTIVE | INACTIVE | RESIGNED | TERMINATED');
            $table->date('join_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
            $table->index(['branch_id', 'employment_status'], 'employees_branch_employment_status_index');
        });

        Schema::create('employee_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name', 191);
            $table->string('relationship', 50)->comment('Spouse | Child | Father | Mother | Sibling');
            $table->string('nik', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_dependent')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'relationship'], 'emp_family_employee_relationship_index');
        });

        Schema::create('employee_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('insurance_type', 50)->comment('BPJS_KES | BPJS_KETENAGAKERJAAN | ASURANSI | OTHER');
            $table->string('provider', 100)->nullable();
            $table->string('membership_number', 100);
            $table->string('insurance_class', 50)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('employee_contribution', 20, 2)->default(0);
            $table->decimal('company_contribution', 20, 2)->default(0);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->index(['employee_id', 'insurance_type'], 'emp_insurance_employee_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_insurances');
        Schema::dropIfExists('employee_family_members');
        Schema::dropIfExists('employees');
    }
};