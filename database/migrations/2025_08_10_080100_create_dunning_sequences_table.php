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
            $table->unsignedBigInteger('campaign_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('step_number'); // 1, 2, 3, etc.
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            
            // Timing configuration
            $table->integer('days_after_previous')->default(0); // Days after previous step or trigger
            $table->integer('days_after_trigger')->nullable(); // Days after initial trigger
            $table->time('preferred_send_time')->nullable();
            $table->json('excluded_days')->nullable(); // weekends, holidays
            
            // Action configuration
            $table->enum('action_type', [
                'email', 'sms', 'phone_call', 'letter', 'portal_notification',
                'service_suspension', 'service_restoration', 'legal_handoff',
                'collection_agency', 'payment_plan_offer', 'settlement_offer'
            ]);
            
            // Communication settings
            $table->string('email_template_id')->nullable();
            $table->string('sms_template_id')->nullable();
            $table->string('letter_template_id')->nullable();
            $table->text('custom_message')->nullable();
            $table->json('personalization_tokens')->nullable();
            
            // Escalation settings
            $table->boolean('is_escalation_step')->default(false);
            $table->enum('escalation_severity', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->boolean('requires_manager_approval')->default(false);
            $table->boolean('auto_escalate_on_failure')->default(false);
            
            // Service suspension configuration
            $table->json('services_to_suspend')->nullable(); // which services to suspend
            $table->json('essential_services_to_maintain')->nullable(); // E911, etc.
            $table->boolean('graceful_suspension')->default(true);
            $table->integer('suspension_notice_hours')->nullable();
            
            // Payment and settlement options
            $table->boolean('include_payment_link')->default(false);
            $table->boolean('offer_payment_plan')->default(false);
            $table->decimal('settlement_percentage', 5, 2)->nullable(); // % of original amount
            $table->integer('settlement_deadline_days')->nullable();
            $table->decimal('late_fee_amount', 10, 2)->nullable();
            $table->boolean('compound_late_fees')->default(false);
            
            // Legal and compliance
            $table->boolean('final_notice')->default(false);
            $table->boolean('legal_threat')->default(false);
            $table->text('legal_disclaimer')->nullable();
            $table->json('required_disclosures')->nullable(); // FDCPA, state law
            $table->boolean('right_to_dispute_notice')->default(false);
            
            // Success criteria and conditions
            $table->json('success_conditions')->nullable(); // payment, contact, etc.
            $table->json('failure_conditions')->nullable(); // bounce, no response
            $table->integer('max_retry_attempts')->default(3);
            $table->integer('retry_interval_hours')->default(24);
            
            // Tracking and analytics
            $table->integer('times_executed')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->decimal('average_response_time', 8, 2)->default(0); // hours
            $table->json('performance_metrics')->nullable();
            
            // Workflow control
            $table->boolean('pause_sequence_on_contact')->default(false);
            $table->boolean('pause_sequence_on_payment')->default(true);
            $table->boolean('pause_sequence_on_dispute')->default(true);
            $table->integer('sequence_timeout_days')->nullable(); // abandon sequence after X days
            
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['campaign_id', 'step_number']);
            $table->index(['company_id', 'status']);
            $table->index(['action_type', 'is_escalation_step']);
            $table->index('days_after_previous');
            
            $table->foreign('campaign_id')->references('id')->on('dunning_campaigns')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
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