<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §23 Asset & Document, §24 Digital Account
 * asset_categories, assets, asset_movements, media_files, media_attachments,
 * digital_account_categories, digital_accounts
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 50)->unique();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->string('name', 191);
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 20, 2)->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('location', 191)->nullable();
            $table->string('condition', 30)->default('GOOD')->index()
                ->comment('GOOD | FAIR | POOR | DAMAGED');
            $table->string('status', 30)->default('IN_USE')->index()
                ->comment('IN_USE | IDLE | IN_MAINTENANCE | DISPOSED');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status'], 'assets_branch_status_index');
        });

        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('from_location', 191)->nullable();
            $table->string('to_location', 191)->nullable();
            $table->date('movement_date');
            $table->text('reason')->nullable();
            $table->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'movement_date'], 'asset_movements_asset_date_index');
        });

        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', 191);
            $table->string('original_name', 191);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path', 500);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('checksum', 100)->nullable();
            $table->timestamps();

            $table->index(['checksum'], 'media_files_checksum_index');
            $table->index(['mime_type'], 'media_files_mime_type_index');
        });

        Schema::create('media_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->string('attachable_type', 191);
            $table->unsignedBigInteger('attachable_id');
            $table->string('collection_name', 100)->nullable();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id'], 'media_attachments_attachable_index');
            $table->index(['media_file_id'], 'media_attachments_file_index');
        });

        Schema::create('digital_account_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamps();
        });

        Schema::create('digital_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('digital_account_categories')->restrictOnDelete();
            $table->string('name', 191);
            $table->string('username', 191)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('owner_type', 191)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->date('renewal_date')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'status'], 'digital_accounts_category_status_index');
            $table->index(['renewal_date'], 'digital_accounts_renewal_index');
            $table->index(['owner_type', 'owner_id'], 'digital_accounts_owner_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_accounts');
        Schema::dropIfExists('digital_account_categories');
        Schema::dropIfExists('media_attachments');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('asset_movements');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};