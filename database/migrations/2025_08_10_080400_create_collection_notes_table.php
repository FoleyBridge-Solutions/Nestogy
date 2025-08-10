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
        Schema::create('collection_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('dunning_action_id')->nullable();
            $table->unsignedBigInteger('payment_plan_id')->nullable();
            
            // Note classification
            $table->enum('note_type', [
                'contact_attempt', 'client_contact', 'promise_to_pay', 'dispute',
                'hardship', 'payment_arrangement', 'legal_action', 'settlement',
                'service_suspension', 'account_review', 'escalation', 'resolution',
                'compliance_issue', 'system_generated', 'manual_entry'
            ]);
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('visibility', ['internal', 'client_visible', 'legal_only'])->default('internal');
            
            // Note content
            $table->string('subject');
            $table->longText('content');
            $table->json('metadata')->nullable(); // structured data about the note
            $table->json('tags')->nullable(); // searchable tags
            
            // Communication details
            $table->enum('communication_method', [
                'phone', 'email', 'sms', 'in_person', 'mail', 'portal', 
                'system_automated', 'third_party'
            ])->nullable();
            $table->string('contact_person')->nullable(); // who was contacted
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->timestamp('contact_datetime')->nullable();
            $table->integer('call_duration_seconds')->nullable(); // for phone calls
            
            // Outcomes and follow-ups
            $table->enum('outcome', [
                'no_answer', 'busy', 'disconnected', 'spoke_with_client',
                'left_message', 'email_sent', 'email_bounced', 'payment_promised',
                'dispute_raised', 'hardship_claimed', 'payment_made', 'plan_agreed',
                'refused_to_pay', 'requested_callback', 'hostile_response'
            ])->nullable();
            $table->text('outcome_details')->nullable();
            
            // Promise to pay tracking
            $table->boolean('contains_promise_to_pay')->default(false);
            $table->decimal('promised_amount', 15, 2)->nullable();
            $table->date('promised_payment_date')->nullable();
            $table->boolean('promise_kept')->nullable(); // null = pending, true/false = result
            $table->timestamp('promise_followed_up_at')->nullable();
            
            // Dispute information
            $table->boolean('contains_dispute')->default(false);
            $table->text('dispute_reason')->nullable();
            $table->decimal('disputed_amount', 15, 2)->nullable();
            $table->enum('dispute_status', ['pending', 'investigating', 'resolved', 'upheld', 'denied'])->nullable();
            $table->date('dispute_deadline')->nullable();
            
            // Follow-up requirements
            $table->boolean('requires_followup')->default(false);
            $table->date('followup_date')->nullable();
            $table->time('followup_time')->nullable();
            $table->enum('followup_type', [
                'call', 'email', 'sms', 'letter', 'in_person', 'legal_review'
            ])->nullable();
            $table->text('followup_instructions')->nullable();
            $table->boolean('followup_completed')->default(false);
            $table->timestamp('followup_completed_at')->nullable();
            
            // Legal and compliance
            $table->boolean('legally_significant')->default(false);
            $table->boolean('compliance_sensitive')->default(false);
            $table->json('compliance_flags')->nullable(); // FDCPA, TCPA violations, etc.
            $table->boolean('attorney_review_required')->default(false);
            $table->boolean('attorney_reviewed')->default(false);
            $table->unsignedBigInteger('reviewed_by_attorney_id')->nullable();
            $table->timestamp('attorney_reviewed_at')->nullable();
            
            // Client satisfaction and relationship
            $table->enum('client_mood', [
                'cooperative', 'neutral', 'frustrated', 'angry', 'hostile', 'threatening'
            ])->nullable();
            $table->integer('satisfaction_rating')->nullable(); // 1-10 scale
            $table->boolean('escalation_risk')->default(false);
            $table->text('relationship_notes')->nullable();
            
            // Financial context at time of note
            $table->decimal('invoice_balance_at_time', 15, 2)->nullable();
            $table->integer('days_overdue_at_time')->nullable();
            $table->decimal('total_account_balance', 15, 2)->nullable();
            $table->json('payment_history_summary')->nullable();
            
            // Attachments and references
            $table->json('attachments')->nullable(); // file paths/references
            $table->json('related_documents')->nullable(); // contracts, agreements, etc.
            $table->string('external_reference')->nullable(); // third-party system reference
            
            // Activity tracking
            $table->boolean('billable_time')->default(false);
            $table->integer('time_spent_minutes')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('billable_amount', 10, 2)->nullable();
            
            // Quality assurance
            $table->boolean('quality_reviewed')->default(false);
            $table->unsignedBigInteger('quality_reviewed_by')->nullable();
            $table->timestamp('quality_reviewed_at')->nullable();
            $table->integer('quality_score')->nullable(); // 1-10 scale
            $table->text('quality_feedback')->nullable();
            
            // Search and reporting
            $table->fullText(['subject', 'content']);
            $table->boolean('flagged_for_review')->default(false);
            $table->boolean('archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['client_id', 'created_at']);
            $table->index(['invoice_id', 'note_type']);
            $table->index(['company_id', 'note_type', 'created_at']);
            $table->index(['requires_followup', 'followup_date']);
            $table->index(['contains_promise_to_pay', 'promised_payment_date']);
            $table->index(['contains_dispute', 'dispute_status']);
            $table->index(['legally_significant', 'attorney_reviewed']);
            $table->index(['created_by', 'created_at']);
            $table->index(['priority', 'created_at']);
            
            // Foreign key constraints
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('dunning_action_id')->references('id')->on('dunning_actions')->onDelete('set null');
            $table->foreign('payment_plan_id')->references('id')->on('payment_plans')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by_attorney_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('quality_reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_notes');
    }
};