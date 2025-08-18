<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_approvals', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Contract relationship
            $table->unsignedBigInteger('contract_id')->index();
            
            // Approval workflow
            $table->string('approval_type'); // 'initial', 'renewal', 'amendment', 'termination', 'budget'
            $table->string('approval_level')->nullable(); // 'level_1', 'level_2', 'executive', etc.
            $table->unsignedInteger('approval_order')->default(1);
            
            // Approver information
            $table->unsignedBigInteger('approver_id')->index();
            $table->string('approver_role')->nullable(); // Role at the time of approval
            $table->unsignedBigInteger('delegated_to_id')->nullable(); // If approval was delegated
            
            // Approval status
            $table->string('status')->default('pending')->index();
            // Statuses: pending, approved, rejected, skipped, expired, withdrawn
            
            // Approval details
            $table->timestamp('requested_at');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('comments')->nullable();
            $table->text('conditions')->nullable(); // Any conditions attached to approval
            
            // Rejection details
            $table->text('rejection_reason')->nullable();
            $table->boolean('can_resubmit')->default(true);
            
            // Financial approval specifics
            $table->decimal('amount_limit', 10, 2)->nullable(); // Max amount this approver can approve
            $table->boolean('amount_exceeded')->default(false);
            
            // Notification tracking
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->timestamp('escalated_at')->nullable();
            $table->unsignedBigInteger('escalated_to_id')->nullable();
            
            // Approval requirements
            $table->json('required_documents')->nullable(); // List of required documents
            $table->boolean('all_documents_received')->default(false);
            $table->json('checklist')->nullable(); // Approval checklist items
            
            // Audit and compliance
            $table->string('approval_method')->nullable(); // 'manual', 'email', 'system', 'meeting'
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('audit_trail')->nullable();
            
            // Dependencies
            $table->unsignedBigInteger('depends_on_approval_id')->nullable();
            $table->boolean('can_approve_parallel')->default(false); // Can approve while others pending
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->onDelete('cascade');
            
            $table->foreign('approver_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            $table->foreign('delegated_to_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            $table->foreign('escalated_to_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            $table->foreign('depends_on_approval_id')
                ->references('id')
                ->on('contract_approvals')
                ->onDelete('set null');
            
            // Composite indexes
            $table->index(['company_id', 'contract_id']);
            $table->index(['company_id', 'status']);
            $table->index(['contract_id', 'approval_order']);
            $table->index(['approver_id', 'status']);
            $table->index(['company_id', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_approvals');
    }
};