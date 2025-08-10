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
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('sequence_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('invoice_id');
            $table->string('action_reference')->unique(); // unique identifier for tracking
            
            // Action details
            $table->enum('action_type', [
                'email', 'sms', 'phone_call', 'letter', 'portal_notification',
                'service_suspension', 'service_restoration', 'legal_handoff',
                'collection_agency', 'payment_plan_offer', 'settlement_offer',
                'account_hold', 'credit_hold', 'writeoff'
            ]);
            $table->enum('status', [
                'pending', 'scheduled', 'processing', 'sent', 'delivered', 
                'failed', 'bounced', 'opened', 'clicked', 'responded',
                'completed', 'cancelled', 'escalated'
            ])->default('pending');
            
            // Timing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            
            // Communication details
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_name')->nullable();
            $table->text('message_subject')->nullable();
            $table->longText('message_content')->nullable();
            $table->string('template_used')->nullable();
            
            // Channel-specific tracking
            $table->string('email_message_id')->nullable(); // tracking ID from email provider
            $table->string('sms_message_id')->nullable(); // tracking ID from SMS provider
            $table->string('call_session_id')->nullable(); // tracking ID from voice provider
            $table->json('delivery_metadata')->nullable(); // provider-specific data
            
            // Response tracking
            $table->boolean('opened')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->boolean('clicked')->default(false);
            $table->timestamp('clicked_at')->nullable();
            $table->string('response_type')->nullable(); // payment, contact, dispute
            $table->timestamp('responded_at')->nullable();
            $table->json('response_data')->nullable();
            
            // Financial details
            $table->decimal('invoice_amount', 15, 2);
            $table->decimal('amount_due', 15, 2);
            $table->decimal('late_fees', 10, 2)->default(0);
            $table->integer('days_overdue');
            $table->decimal('settlement_offer_amount', 15, 2)->nullable();
            $table->decimal('amount_collected', 15, 2)->default(0);
            
            // Service suspension details
            $table->json('suspended_services')->nullable();
            $table->json('maintained_services')->nullable(); // essential services
            $table->timestamp('suspension_effective_at')->nullable();
            $table->timestamp('restoration_scheduled_at')->nullable();
            $table->text('suspension_reason')->nullable();
            
            // Legal and compliance
            $table->boolean('final_notice')->default(false);
            $table->boolean('legal_action_threatened')->default(false);
            $table->json('compliance_flags')->nullable(); // FDCPA, TCPA, etc.
            $table->text('legal_disclaimer')->nullable();
            $table->boolean('dispute_period_active')->default(false);
            $table->timestamp('dispute_deadline')->nullable();
            
            // Escalation tracking
            $table->boolean('escalated')->default(false);
            $table->unsignedBigInteger('escalated_to_user_id')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->text('escalation_reason')->nullable();
            $table->enum('escalation_level', ['manager', 'legal', 'collection_agency', 'writeoff'])->nullable();
            
            // Performance metrics
            $table->decimal('cost_per_action', 10, 4)->default(0); // cost to send/execute
            $table->boolean('resulted_in_payment')->default(false);
            $table->decimal('roi', 10, 4)->default(0); // return on investment
            $table->integer('client_satisfaction_score')->nullable(); // if feedback received
            
            // Error handling
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->boolean('requires_manual_review')->default(false);
            
            // Workflow control
            $table->boolean('pause_sequence')->default(false);
            $table->text('pause_reason')->nullable();
            $table->timestamp('sequence_resumed_at')->nullable();
            $table->unsignedBigInteger('next_action_id')->nullable(); // next scheduled action
            
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['campaign_id', 'sequence_id']);
            $table->index(['client_id', 'invoice_id']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['action_type', 'status']);
            $table->index(['company_id', 'created_at']);
            $table->index('action_reference');
            $table->index(['escalated', 'escalation_level']);
            $table->index(['resulted_in_payment', 'amount_collected']);
            
            // Foreign key constraints
            $table->foreign('campaign_id')->references('id')->on('dunning_campaigns')->onDelete('cascade');
            $table->foreign('sequence_id')->references('id')->on('dunning_sequences')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('escalated_to_user_id')->references('id')->on('users')->onDelete('set null');
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