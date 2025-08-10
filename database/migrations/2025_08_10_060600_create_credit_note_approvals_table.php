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
        Schema::create('credit_note_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('approver_id');
            $table->unsignedBigInteger('requested_by')->nullable();
            
            // Approval workflow details
            $table->enum('approval_level', [
                'supervisor', 'manager', 'finance_manager', 
                'controller', 'cfo', 'executive', 'legal'
            ]);
            
            $table->integer('sequence_order')->default(1); // Order in approval chain
            $table->decimal('approval_threshold', 15, 2)->nullable();
            $table->string('approval_role', 100)->nullable(); // Role-based approval
            
            // Status tracking
            $table->enum('status', [
                'pending', 'approved', 'rejected', 'escalated',
                'bypassed', 'expired', 'cancelled'
            ])->default('pending');
            
            $table->enum('approval_type', [
                'manual', 'automatic', 'delegated', 'escalated', 'emergency'
            ])->default('manual');
            
            // Decision details
            $table->text('approval_reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('comments')->nullable();
            $table->json('decision_factors')->nullable();
            
            // Approval criteria
            $table->json('approval_criteria')->nullable(); // Criteria that triggered this approval
            $table->boolean('amount_based')->default(true);
            $table->boolean('risk_based')->default(false);
            $table->boolean('client_based')->default(false);
            $table->boolean('reason_based')->default(false);
            
            // Risk assessment
            $table->decimal('risk_score', 5, 2)->nullable();
            $table->json('risk_factors')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->nullable();
            
            // Emergency and bypass handling
            $table->boolean('emergency_approval')->default(false);
            $table->boolean('approval_bypassed')->default(false);
            $table->text('bypass_reason')->nullable();
            $table->unsignedBigInteger('bypassed_by')->nullable();
            $table->datetime('bypassed_at')->nullable();
            
            // Escalation handling
            $table->boolean('escalated')->default(false);
            $table->unsignedBigInteger('escalated_to')->nullable();
            $table->unsignedBigInteger('escalated_by')->nullable();
            $table->datetime('escalated_at')->nullable();
            $table->text('escalation_reason')->nullable();
            
            // Delegation handling
            $table->boolean('delegated')->default(false);
            $table->unsignedBigInteger('delegated_from')->nullable();
            $table->unsignedBigInteger('delegated_to')->nullable();
            $table->datetime('delegated_at')->nullable();
            $table->text('delegation_reason')->nullable();
            $table->date('delegation_expiry')->nullable();
            
            // SLA and timing
            $table->integer('sla_hours')->default(24);
            $table->datetime('sla_deadline');
            $table->boolean('sla_breached')->default(false);
            $table->integer('response_time_hours')->nullable();
            
            // Automatic approval rules
            $table->boolean('auto_approved')->default(false);
            $table->json('auto_approval_rules')->nullable();
            $table->text('auto_approval_reason')->nullable();
            
            // Notification tracking
            $table->boolean('notification_sent')->default(false);
            $table->datetime('notification_sent_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->datetime('last_reminder_sent')->nullable();
            $table->json('notification_history')->nullable();
            
            // Decision timestamps
            $table->datetime('requested_at');
            $table->datetime('reviewed_at')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->datetime('expired_at')->nullable();
            
            // Workflow context
            $table->json('workflow_context')->nullable(); // Additional context for approval
            $table->json('approval_conditions')->nullable(); // Conditions that must be met
            $table->boolean('conditional_approval')->default(false);
            $table->json('conditions_met')->nullable();
            
            // Supporting documentation
            $table->json('supporting_documents')->nullable();
            $table->json('approval_evidence')->nullable();
            $table->json('compliance_checks')->nullable();
            
            // External approval integration
            $table->string('external_approval_id')->nullable();
            $table->string('external_system')->nullable();
            $table->json('external_response')->nullable();
            
            // Audit and compliance
            $table->json('audit_trail')->nullable();
            $table->boolean('requires_documentation')->default(false);
            $table->boolean('documentation_complete')->default(false);
            $table->json('compliance_verification')->nullable();
            
            // Policy and rule tracking
            $table->string('policy_version', 20)->nullable();
            $table->json('applicable_policies')->nullable();
            $table->json('rule_violations')->nullable();
            $table->boolean('policy_exception')->default(false);
            
            // Geographic and jurisdictional
            $table->string('jurisdiction', 100)->nullable();
            $table->json('regulatory_requirements')->nullable();
            $table->boolean('cross_border_approval')->default(false);
            
            // Metadata and system info
            $table->string('approval_source', 50)->default('system'); // system, email, mobile, api
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('session_data')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['credit_note_id', 'sequence_order']);
            $table->index(['approver_id', 'status']);
            $table->index(['status', 'sla_deadline']);
            $table->index(['approval_level', 'approval_type']);
            $table->index(['escalated', 'escalated_at']);
            $table->index(['delegated', 'delegated_to']);
            $table->index(['emergency_approval', 'approval_bypassed']);
            $table->index(['sla_breached', 'sla_deadline']);
            $table->index(['auto_approved', 'approved_at']);
            $table->index(['requested_at', 'approved_at']);
            $table->index(['external_approval_id']);
            $table->index(['risk_level', 'risk_score']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('escalated_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('escalated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('delegated_from')->references('id')->on('users')->onDelete('set null');
            $table->foreign('delegated_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('bypassed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_approvals');
    }
};