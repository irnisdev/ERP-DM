<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD 01 §25 Approval & Audit
 * approval_requests, audit_logs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_type', 100)->index()->comment(
                'PURCHASE_ORDER | EXPENSE | STOCK_TRANSFER | STOCK_OPNAME | PAYROLL | PAYROLL_ADJUSTMENT | BANK_RECONCILIATION'
            );
            $table->unsignedBigInteger('request_id');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('DRAFT')->index()
                ->comment('DRAFT | SUBMITTED | APPROVED | REJECTED | CANCELLED');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['request_type', 'request_id'], 'approval_requests_target_index');
            $table->index(['status', 'requested_at'], 'approval_requests_status_date_index');
            $table->index(['approver_id', 'status'], 'approval_requests_approver_status_index');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('auditable_type', 191);
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
            $table->index(['user_id', 'created_at'], 'audit_logs_user_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('approval_requests');
    }
};