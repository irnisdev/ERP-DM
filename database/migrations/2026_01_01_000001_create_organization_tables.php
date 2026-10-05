<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §4.1 Organization
 * companies, branches, departments, positions
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->string('legal_name', 191)->nullable();
            $table->string('tax_number', 50)->nullable()->comment('NPWP');
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('address')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 191);
            $table->string('branch_type', 50)->default('BRANCH')->comment('CENTRAL | BRANCH | OUTLET | CLINIC');
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('address')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'branches_company_code_unique');
            $table->unique(['company_id', 'name'], 'branches_company_name_unique');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 191);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'departments_company_code_unique');
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 191);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->unique(['department_id', 'code'], 'positions_department_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};