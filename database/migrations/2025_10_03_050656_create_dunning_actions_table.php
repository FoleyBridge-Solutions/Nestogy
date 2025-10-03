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
        Schema::create('dunning_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('sequence_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('action_reference')->nullable();
            $table->string('action_type')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('retry_count')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_name');
            $table->string('message_subject')->nullable();
            $table->string('message_content')->nullable();
            $table->string('template_used')->nullable();
            $table->unsignedBigInteger('email_message_id')->nullable();
            $table->unsignedBigInteger('sms_message_id')->nullable();
            $table->unsignedBigInteger('call_session_id')->nullable();
            $table->string('delivery_metadata')->nullable();
            $table->string('opened')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->string('clicked')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->string('response_type')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('response_data')->nullable();
            $table->decimal('invoice_amount', 15, 2)->default(0);
            $table->decimal('amount_due', 15, 2)->default(0);
            $table->string('late_fees')->nullable();
            $table->string('days_overdue')->nullable();
            $table->decimal('settlement_offer_amount', 15, 2)->default(0);
            $table->decimal('amount_collected', 15, 2)->default(0);
            $table->string('suspended_services')->nullable();
            $table->string('maintained_services')->nullable();
            $table->timestamp('suspension_effective_at')->nullable();
            $table->timestamp('restoration_scheduled_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->string('final_notice')->nullable();
            $table->string('legal_action_threatened')->nullable();
            $table->string('compliance_flags')->nullable();
            $table->string('legal_disclaimer')->nullable();
            $table->boolean('dispute_period_active')->default(false);
            $table->string('dispute_deadline')->nullable();
            $table->string('escalated')->nullable();
            $table->unsignedBigInteger('escalated_to_user_id')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->string('escalation_reason')->nullable();
            $table->string('escalation_level')->nullable();
            $table->string('cost_per_action')->nullable();
            $table->string('resulted_in_payment')->nullable();
            $table->string('roi')->nullable();
            $table->string('client_satisfaction_score')->nullable();
            $table->string('error_message')->nullable();
            $table->string('error_details')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->string('requires_manual_review')->nullable();
            $table->string('pause_sequence')->nullable();
            $table->string('pause_reason')->nullable();
            $table->timestamp('sequence_resumed_at')->nullable();
            $table->unsignedBigInteger('next_action_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('processed_by')->nullable();
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
        Schema::dropIfExists('dunning_actions');
    }
};
