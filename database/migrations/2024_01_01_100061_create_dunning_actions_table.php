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

                        // Foreign keys (constraints will be added after all tables exist)
                        $table->unsignedBigInteger('campaign_id')->nullable();
                        $table->unsignedBigInteger('sequence_id')->nullable();
                        $table->unsignedBigInteger('client_id');
                        $table->unsignedBigInteger('invoice_id')->nullable();

                        // Reference and type
                        $table->string('action_reference')->unique();
                        $table->enum('action_type', ['email', 'sms', 'phone_call', 'letter', 'service_suspension', 'legal_notice']);

                        // Status and scheduling
                        $table->enum('status', ['pending', 'scheduled', 'processing', 'sent', 'delivered', 'failed', 'bounced', 'opened', 'clicked', 'responded', 'completed', 'cancelled', 'escalated'])->default('pending');
                        $table->timestamp('scheduled_at')->nullable();
                        $table->timestamp('attempted_at')->nullable();
                        $table->timestamp('completed_at')->nullable();
                        $table->timestamp('expires_at')->nullable();

                        // Retry logic
                        $table->integer('retry_count')->default(0);
                        $table->timestamp('next_retry_at')->nullable();

                        // Recipient information
                        $table->string('recipient_email')->nullable();
                        $table->string('recipient_phone')->nullable();
                        $table->string('recipient_name')->nullable();

                        // Message content
                        $table->string('message_subject')->nullable();
                        $table->text('message_content')->nullable();
                        $table->string('template_used')->nullable();

                        // Delivery tracking
                        $table->string('email_message_id')->nullable();
                        $table->string('sms_message_id')->nullable();
                        $table->string('call_session_id')->nullable();
                        $table->json('delivery_metadata')->nullable();

                        // Engagement tracking
                        $table->boolean('opened')->default(false);
                        $table->timestamp('opened_at')->nullable();
                        $table->boolean('clicked')->default(false);
                        $table->timestamp('clicked_at')->nullable();

                        // Response tracking
                        $table->enum('response_type', ['payment', 'dispute', 'promise_to_pay', 'no_response'])->nullable();
                        $table->timestamp('responded_at')->nullable();
                        $table->json('response_data')->nullable();

                        // Financial information
                        $table->decimal('invoice_amount', 15, 2)->default(0);
                        $table->decimal('amount_due', 15, 2)->default(0);
                        $table->decimal('late_fees', 15, 2)->default(0);
                        $table->integer('days_overdue')->default(0);
                        $table->decimal('settlement_offer_amount', 15, 2)->nullable();
                        $table->decimal('amount_collected', 15, 2)->nullable();

                        // Service suspension
                        $table->json('suspended_services')->nullable();
                        $table->json('maintained_services')->nullable();
                        $table->timestamp('suspension_effective_at')->nullable();
                        $table->timestamp('restoration_scheduled_at')->nullable();
                        $table->string('suspension_reason')->nullable();

                        // Legal and compliance
                        $table->boolean('final_notice')->default(false);
                        $table->boolean('legal_action_threatened')->default(false);
                        $table->json('compliance_flags')->nullable();
                        $table->text('legal_disclaimer')->nullable();

                        // Dispute handling
                        $table->boolean('dispute_period_active')->default(false);
                        $table->timestamp('dispute_deadline')->nullable();

                        // Escalation
                        $table->boolean('escalated')->default(false);
                        $table->foreignId('escalated_to_user_id')->nullable()->constrained('users')->onDelete('set null');
                        $table->timestamp('escalated_at')->nullable();
                        $table->string('escalation_reason')->nullable();
                        $table->integer('escalation_level')->nullable();

                        // Analytics
                        $table->decimal('cost_per_action', 10, 4)->nullable();
                        $table->boolean('resulted_in_payment')->default(false);
                        $table->decimal('roi', 10, 4)->nullable();
                        $table->integer('client_satisfaction_score')->nullable();

                        // Error handling
                        $table->text('error_message')->nullable();
                        $table->text('error_details')->nullable();
                        $table->timestamp('last_error_at')->nullable();

                        // Manual review and pause
                        $table->boolean('requires_manual_review')->default(false);
                        $table->boolean('pause_sequence')->default(false);
                        $table->string('pause_reason')->nullable();
                        $table->timestamp('sequence_resumed_at')->nullable();

                        // Sequence management
                        $table->unsignedBigInteger('next_action_id')->nullable();

                        // User tracking
                        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                        $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');

                        $table->timestamps();
                        $table->softDeletes();

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
        Schema::dropIfExists('dunning_actions');
    }
};
