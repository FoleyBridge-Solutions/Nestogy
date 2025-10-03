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
        Schema::create('dunning_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('step_number')->nullable();
            $table->string('status')->default('active');
            $table->string('days_after_previous')->nullable();
            $table->string('days_after_trigger')->nullable();
            $table->string('preferred_send_time')->nullable();
            $table->string('excluded_days')->nullable();
            $table->string('action_type')->nullable();
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->unsignedBigInteger('sms_template_id')->nullable();
            $table->unsignedBigInteger('letter_template_id')->nullable();
            $table->string('custom_message')->nullable();
            $table->string('personalization_tokens')->nullable();
            $table->boolean('is_escalation_step')->default(false);
            $table->string('escalation_severity')->nullable();
            $table->string('requires_manager_approval')->nullable();
            $table->string('auto_escalate_on_failure')->nullable();
            $table->string('services_to_suspend')->nullable();
            $table->string('essential_services_to_maintain')->nullable();
            $table->string('graceful_suspension')->nullable();
            $table->string('suspension_notice_hours')->nullable();
            $table->string('include_payment_link')->nullable();
            $table->string('offer_payment_plan')->nullable();
            $table->string('settlement_percentage')->nullable();
            $table->string('settlement_deadline_days')->nullable();
            $table->decimal('late_fee_amount', 15, 2)->default(0);
            $table->string('compound_late_fees')->nullable();
            $table->string('final_notice')->nullable();
            $table->string('legal_threat')->nullable();
            $table->string('legal_disclaimer')->nullable();
            $table->string('required_disclosures')->nullable();
            $table->string('right_to_dispute_notice')->nullable();
            $table->string('success_conditions')->nullable();
            $table->string('failure_conditions')->nullable();
            $table->timestamp('max_retry_attempts')->nullable();
            $table->string('retry_interval_hours')->nullable();
            $table->string('times_executed')->nullable();
            $table->string('success_rate')->nullable();
            $table->string('average_response_time')->nullable();
            $table->string('performance_metrics')->nullable();
            $table->string('pause_sequence_on_contact')->nullable();
            $table->string('pause_sequence_on_payment')->nullable();
            $table->string('pause_sequence_on_dispute')->nullable();
            $table->string('sequence_timeout_days')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_by')->nullable();
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
        Schema::dropIfExists('dunning_sequences');
    }
};
