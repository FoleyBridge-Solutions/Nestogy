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
            
            // Company and contract relationships
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            
            // Approval details
            $table->string('approval_level', 50); // manager, director, executive, legal, etc.
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_role', 50)->nullable(); // fallback role if user is null
            $table->timestamp('required_by')->nullable(); // deadline for approval
            $table->integer('approval_order')->default(1); // order in approval chain
            $table->boolean('is_required')->default(true); // whether this approval is mandatory
            
            // Status tracking
            $table->enum('status', [
                'pending', 
                'approved', 
                'rejected', 
                'changes_requested', 
                'escalated', 
                'expired'
            ])->default('pending');
            
            // Workflow tracking
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('requested_at')->nullable(); // when changes were requested
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('escalated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('escalated_from')->nullable()->constrained('contract_approvals')->nullOnDelete();
            
            // Comments and conditions
            $table->text('comments')->nullable(); // approval/rejection comments
            $table->json('conditions')->nullable(); // conditional approval terms
            $table->json('metadata')->nullable(); // additional data, audit trail
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['contract_id', 'approval_order']);
            $table->index(['approver_user_id', 'status']);
            $table->index(['approver_role', 'status']);
            $table->index(['required_by']);
            $table->index(['status', 'required_by']); // for overdue approvals
            $table->index(['approval_level', 'status']);
            
            // Composite indexes for common queries
            $table->index(['company_id', 'approver_user_id', 'status']);
            $table->index(['contract_id', 'status', 'approval_order']);
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