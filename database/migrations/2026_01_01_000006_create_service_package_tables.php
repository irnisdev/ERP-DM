<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §9 Service/Treatment Domain & §10 Package Domain
 * service_categories, services, service_prices, service_packages, service_package_items
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('service_categories')->nullOnDelete();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_code', 50)->unique();
            $table->foreignId('category_id')->constrained('service_categories')->restrictOnDelete();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->string('pricing_method', 30)->default('FIXED')->index()
                ->comment('FIXED | DURATION | WEIGHT');
            $table->unsignedInteger('default_duration_minutes')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status'], 'services_category_status_index');
        });

        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->decimal('min_weight', 20, 4)->nullable();
            $table->decimal('max_weight', 20, 4)->nullable();
            $table->decimal('price', 20, 2);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(
                ['service_id', 'effective_date', 'duration_minutes'],
                'service_prices_duration_lookup_index'
            );
            $table->index(
                ['service_id', 'effective_date', 'min_weight', 'max_weight'],
                'service_prices_weight_lookup_index'
            );
        });

        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->decimal('price', 20, 2);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('service_packages')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->decimal('quantity', 20, 4)->default(1);
            $table->timestamps();

            $table->unique(['package_id', 'service_id'], 'service_package_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_package_items');
        Schema::dropIfExists('service_packages');
        Schema::dropIfExists('service_prices');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
    }
};