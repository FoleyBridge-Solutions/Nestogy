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
        Schema::table('dunning_actions', function (Blueprint $table) {
            // Drop name column if it exists (it was wrong)
            if (Schema::hasColumn('dunning_actions', 'name')) {
                $table->dropColumn('name');
            }
            
            // Add foreign keys
            $table->foreignId('campaign_id')->nullable()->after('company_id')->constrained('dunning_campaigns')->onDelete('cascade');
            $table->foreignId('sequence_id')->nullable()->after('campaign_id')->constrained('dunning_sequences')->onDelete('cascade');
            $table->foreignId('client_id')->after('sequence_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->after('client_id')->constrained()->onDelete('cascade');
            
            // Reference and type
            $table->string('action_reference')->unique()->after('invoice_id');
            $table->enum('action_type', ['email', 'sms', 'phone_call', 'letter', 'service_suspension', 'legal_notice'])->after('action_reference');
            
            // Status and scheduling
            $table->enum('status', ['pending', 'scheduled', 'processing', 'sent', 'delivered', 'failed', 'bounced', 'opened', 'clicked', 'responded', 'completed', 'cancelled', 'escalated'])->default('pending')->after('action_type');
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->timestamp('attempted_at')->nullable()->after('scheduled_at');
            $table->timestamp('completed_at')->nullable()->after('attempted_at');
            $table->timestamp('expires_at')->nullable()->after('completed_at');
            
            // Retry logic
            $table->integer('retry_count')->default(0)->after('expires_at');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count');
            
            // Recipient information
            $table->string('recipient_email')->nullable()->after('next_retry_at');
            $table->string('recipient_phone')->nullable()->after('recipient_email');
            $table->string('recipient_name')->nullable()->after('recipient_phone');
            
            // Message content
            $table->string('message_subject')->nullable()->after('recipient_name');
            $table->text('message_content')->nullable()->after('message_subject');
            $table->string('template_used')->nullable()->after('message_content');
            
            // Delivery tracking
            $table->string('email_message_id')->nullable()->after('template_used');
            $table->string('sms_message_id')->nullable()->after('email_message_id');
            $table->string('call_session_id')->nullable()->after('sms_message_id');
            $table->json('delivery_metadata')->nullable()->after('call_session_id');
            
            // Engagement tracking
            $table->boolean('opened')->default(false)->after('delivery_metadata');
            $table->timestamp('opened_at')->nullable()->after('opened');
            $table->boolean('clicked')->default(false)->after('opened_at');
            $table->timestamp('clicked_at')->nullable()->after('clicked');
            
            // Response tracking
            $table->enum('response_type', ['payment', 'dispute', 'promise_to_pay', 'no_response'])->nullable()->after('clicked_at');
            $table->timestamp('responded_at')->nullable()->after('response_type');
            $table->json('response_data')->nullable()->after('responded_at');
            
            // Financial information
            $table->decimal('invoice_amount', 15, 2)->default(0)->after('response_data');
            $table->decimal('amount_due', 15, 2)->default(0)->after('invoice_amount');
            $table->decimal('late_fees', 15, 2)->default(0)->after('amount_due');
            $table->integer('days_overdue')->default(0)->after('late_fees');
            $table->decimal('settlement_offer_amount', 15, 2)->nullable()->after('days_overdue');
            $table->decimal('amount_collected', 15, 2)->nullable()->after('settlement_offer_amount');
            
            // Service suspension
            $table->json('suspended_services')->nullable()->after('amount_collected');
            $table->json('maintained_services')->nullable()->after('suspended_services');
            $table->timestamp('suspension_effective_at')->nullable()->after('maintained_services');
            $table->timestamp('restoration_scheduled_at')->nullable()->after('suspension_effective_at');
            $table->string('suspension_reason')->nullable()->after('restoration_scheduled_at');
            
            // Legal and compliance
            $table->boolean('final_notice')->default(false)->after('suspension_reason');
            $table->boolean('legal_action_threatened')->default(false)->after('final_notice');
            $table->json('compliance_flags')->nullable()->after('legal_action_threatened');
            $table->text('legal_disclaimer')->nullable()->after('compliance_flags');
            
            // Dispute handling
            $table->boolean('dispute_period_active')->default(false)->after('legal_disclaimer');
            $table->timestamp('dispute_deadline')->nullable()->after('dispute_period_active');
            
            // Escalation
            $table->boolean('escalated')->default(false)->after('dispute_deadline');
            $table->foreignId('escalated_to_user_id')->nullable()->after('escalated')->constrained('users')->onDelete('set null');
            $table->timestamp('escalated_at')->nullable()->after('escalated_to_user_id');
            $table->string('escalation_reason')->nullable()->after('escalated_at');
            $table->integer('escalation_level')->nullable()->after('escalation_reason');
            
            // Analytics
            $table->decimal('cost_per_action', 10, 4)->nullable()->after('escalation_level');
            $table->boolean('resulted_in_payment')->default(false)->after('cost_per_action');
            $table->decimal('roi', 10, 4)->nullable()->after('resulted_in_payment');
            $table->integer('client_satisfaction_score')->nullable()->after('roi');
            
            // Error handling
            $table->text('error_message')->nullable()->after('client_satisfaction_score');
            $table->text('error_details')->nullable()->after('error_message');
            $table->timestamp('last_error_at')->nullable()->after('error_details');
            
            // Manual review and pause
            $table->boolean('requires_manual_review')->default(false)->after('last_error_at');
            $table->boolean('pause_sequence')->default(false)->after('requires_manual_review');
            $table->string('pause_reason')->nullable()->after('pause_sequence');
            $table->timestamp('sequence_resumed_at')->nullable()->after('pause_reason');
            
            // Sequence management
            $table->unsignedBigInteger('next_action_id')->nullable()->after('sequence_resumed_at');
            
            // User tracking
            $table->foreignId('created_by')->nullable()->after('next_action_id')->constrained('users')->onDelete('set null');
            $table->foreignId('processed_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['invoice_id']);
            $table->index(['scheduled_at']);
            $table->index(['campaign_id', 'sequence_id']);
            $table->index(['action_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dunning_actions', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['sequence_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['escalated_to_user_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['processed_by']);
            
            $table->dropColumn([
                'campaign_id', 'sequence_id', 'client_id', 'invoice_id',
                'action_reference', 'action_type', 'status', 'scheduled_at',
                'attempted_at', 'completed_at', 'expires_at', 'retry_count',
                'next_retry_at', 'recipient_email', 'recipient_phone', 'recipient_name',
                'message_subject', 'message_content', 'template_used',
                'email_message_id', 'sms_message_id', 'call_session_id',
                'delivery_metadata', 'opened', 'opened_at', 'clicked', 'clicked_at',
                'response_type', 'responded_at', 'response_data', 'invoice_amount',
                'amount_due', 'late_fees', 'days_overdue', 'settlement_offer_amount',
                'amount_collected', 'suspended_services', 'maintained_services',
                'suspension_effective_at', 'restoration_scheduled_at', 'suspension_reason',
                'final_notice', 'legal_action_threatened', 'compliance_flags',
                'legal_disclaimer', 'dispute_period_active', 'dispute_deadline',
                'escalated', 'escalated_to_user_id', 'escalated_at', 'escalation_reason',
                'escalation_level', 'cost_per_action', 'resulted_in_payment', 'roi',
                'client_satisfaction_score', 'error_message', 'error_details',
                'last_error_at', 'requires_manual_review', 'pause_sequence',
                'pause_reason', 'sequence_resumed_at', 'next_action_id',
                'created_by', 'processed_by'
            ]);
            
            $table->string('name')->after('company_id');
        });
    }
};
