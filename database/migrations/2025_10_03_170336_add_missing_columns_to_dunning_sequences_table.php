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
        Schema::table('dunning_sequences', function (Blueprint $table) {
            // Foreign key to campaign
            $table->foreignId('campaign_id')->after('company_id')->constrained('dunning_campaigns')->onDelete('cascade');
            
            // Basic info
            $table->text('description')->nullable()->after('name');
            $table->integer('step_number')->default(1)->after('description');
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active')->after('step_number');
            
            // Timing
            $table->integer('days_after_previous')->default(0)->after('status');
            $table->integer('days_after_trigger')->default(0)->after('days_after_previous');
            $table->time('preferred_send_time')->nullable()->after('days_after_trigger');
            $table->json('excluded_days')->nullable()->after('preferred_send_time');
            
            // Action configuration
            $table->enum('action_type', ['email', 'sms', 'phone_call', 'letter', 'service_suspension', 'legal_notice'])->after('excluded_days');
            $table->unsignedBigInteger('email_template_id')->nullable()->after('action_type');
            $table->unsignedBigInteger('sms_template_id')->nullable()->after('email_template_id');
            $table->unsignedBigInteger('letter_template_id')->nullable()->after('sms_template_id');
            $table->text('custom_message')->nullable()->after('letter_template_id');
            $table->json('personalization_tokens')->nullable()->after('custom_message');
            
            // Escalation
            $table->boolean('is_escalation_step')->default(false)->after('personalization_tokens');
            $table->string('escalation_severity')->nullable()->after('is_escalation_step');
            $table->boolean('requires_manager_approval')->default(false)->after('escalation_severity');
            $table->boolean('auto_escalate_on_failure')->default(false)->after('requires_manager_approval');
            
            // Service suspension
            $table->json('services_to_suspend')->nullable()->after('auto_escalate_on_failure');
            $table->json('essential_services_to_maintain')->nullable()->after('services_to_suspend');
            $table->boolean('graceful_suspension')->default(true)->after('essential_services_to_maintain');
            $table->integer('suspension_notice_hours')->nullable()->after('graceful_suspension');
            
            // Payment options
            $table->boolean('include_payment_link')->default(true)->after('suspension_notice_hours');
            $table->boolean('offer_payment_plan')->default(false)->after('include_payment_link');
            $table->decimal('settlement_percentage', 5, 2)->nullable()->after('offer_payment_plan');
            $table->integer('settlement_deadline_days')->nullable()->after('settlement_percentage');
            
            // Fees and notices
            $table->decimal('late_fee_amount', 15, 2)->nullable()->after('settlement_deadline_days');
            $table->boolean('compound_late_fees')->default(false)->after('late_fee_amount');
            $table->boolean('final_notice')->default(false)->after('compound_late_fees');
            $table->boolean('legal_threat')->default(false)->after('final_notice');
            $table->text('legal_disclaimer')->nullable()->after('legal_threat');
            $table->json('required_disclosures')->nullable()->after('legal_disclaimer');
            $table->boolean('right_to_dispute_notice')->default(true)->after('required_disclosures');
            
            // Conditions and retry
            $table->json('success_conditions')->nullable()->after('right_to_dispute_notice');
            $table->json('failure_conditions')->nullable()->after('success_conditions');
            $table->integer('max_retry_attempts')->default(3)->after('failure_conditions');
            $table->integer('retry_interval_hours')->default(24)->after('max_retry_attempts');
            
            // Performance
            $table->integer('times_executed')->default(0)->after('retry_interval_hours');
            $table->decimal('success_rate', 5, 2)->default(0)->after('times_executed');
            $table->integer('average_response_time')->nullable()->after('success_rate');
            $table->json('performance_metrics')->nullable()->after('average_response_time');
            
            // Pause conditions
            $table->boolean('pause_sequence_on_contact')->default(true)->after('performance_metrics');
            $table->boolean('pause_sequence_on_payment')->default(true)->after('pause_sequence_on_contact');
            $table->boolean('pause_sequence_on_dispute')->default(true)->after('pause_sequence_on_payment');
            $table->integer('sequence_timeout_days')->nullable()->after('pause_sequence_on_dispute');
            
            // User tracking
            $table->foreignId('created_by')->nullable()->after('sequence_timeout_days')->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            
            // Indexes
            $table->index(['campaign_id', 'step_number']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dunning_sequences', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            $table->dropColumn([
                'campaign_id', 'description', 'step_number', 'status', 'days_after_previous',
                'days_after_trigger', 'preferred_send_time', 'excluded_days', 'action_type',
                'email_template_id', 'sms_template_id', 'letter_template_id', 'custom_message',
                'personalization_tokens', 'is_escalation_step', 'escalation_severity',
                'requires_manager_approval', 'auto_escalate_on_failure', 'services_to_suspend',
                'essential_services_to_maintain', 'graceful_suspension', 'suspension_notice_hours',
                'include_payment_link', 'offer_payment_plan', 'settlement_percentage',
                'settlement_deadline_days', 'late_fee_amount', 'compound_late_fees',
                'final_notice', 'legal_threat', 'legal_disclaimer', 'required_disclosures',
                'right_to_dispute_notice', 'success_conditions', 'failure_conditions',
                'max_retry_attempts', 'retry_interval_hours', 'times_executed',
                'success_rate', 'average_response_time', 'performance_metrics',
                'pause_sequence_on_contact', 'pause_sequence_on_payment', 'pause_sequence_on_dispute',
                'sequence_timeout_days', 'created_by', 'updated_by'
            ]);
        });
    }
};
