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
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('requested_by')->nullable();
            $table->string('approval_level')->nullable();
            $table->string('sequence_order')->nullable();
            $table->string('approval_threshold')->nullable();
            $table->string('approval_role')->nullable();
            $table->string('status')->default('active');
            $table->string('approval_type')->nullable();
            $table->string('approval_reason')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('comments')->nullable();
            $table->string('decision_factors')->nullable();
            $table->string('approval_criteria')->nullable();
            $table->decimal('amount_based', 15, 2)->default(0);
            $table->string('risk_based')->nullable();
            $table->string('client_based')->nullable();
            $table->string('reason_based')->nullable();
            $table->string('risk_score')->nullable();
            $table->string('risk_factors')->nullable();
            $table->string('risk_level')->nullable();
            $table->string('emergency_approval')->nullable();
            $table->string('approval_bypassed')->nullable();
            $table->string('bypass_reason')->nullable();
            $table->string('bypassed_by')->nullable();
            $table->timestamp('bypassed_at')->nullable();
            $table->string('escalated')->nullable();
            $table->string('escalated_to')->nullable();
            $table->string('escalated_by')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->string('escalation_reason')->nullable();
            $table->string('delegated')->nullable();
            $table->string('delegated_from')->nullable();
            $table->string('delegated_to')->nullable();
            $table->timestamp('delegated_at')->nullable();
            $table->string('delegation_reason')->nullable();
            $table->string('delegation_expiry')->nullable();
            $table->string('sla_hours')->nullable();
            $table->string('sla_deadline')->nullable();
            $table->string('sla_breached')->nullable();
            $table->string('response_time_hours')->nullable();
            $table->string('auto_approved')->nullable();
            $table->string('auto_approval_rules')->nullable();
            $table->string('auto_approval_reason')->nullable();
            $table->string('notification_sent')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->string('reminder_count')->nullable();
            $table->string('last_reminder_sent')->nullable();
            $table->string('notification_history')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->string('workflow_context')->nullable();
            $table->string('approval_conditions')->nullable();
            $table->string('conditional_approval')->nullable();
            $table->string('conditions_met')->nullable();
            $table->string('supporting_documents')->nullable();
            $table->string('approval_evidence')->nullable();
            $table->string('compliance_checks')->nullable();
            $table->unsignedBigInteger('external_approval_id')->nullable();
            $table->string('external_system')->nullable();
            $table->string('external_response')->nullable();
            $table->string('audit_trail')->nullable();
            $table->string('requires_documentation')->nullable();
            $table->string('documentation_complete')->nullable();
            $table->string('compliance_verification')->nullable();
            $table->string('policy_version')->nullable();
            $table->string('applicable_policies')->nullable();
            $table->string('rule_violations')->nullable();
            $table->string('policy_exception')->nullable();
            $table->string('jurisdiction')->nullable();
            $table->string('regulatory_requirements')->nullable();
            $table->string('cross_border_approval')->nullable();
            $table->string('approval_source')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_data')->nullable();
            $table->string('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes('archived_at');
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
