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
        Schema::table('collection_notes', function (Blueprint $table) {
            // Foreign keys
            $table->foreignId('client_id')->after('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->after('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('dunning_action_id')->nullable()->after('invoice_id')->constrained()->onDelete('set null');
            $table->foreignId('payment_plan_id')->nullable()->after('dunning_action_id')->constrained()->onDelete('set null');
            
            // Note details
            $table->enum('note_type', ['general', 'call', 'email', 'meeting', 'promise_to_pay', 'dispute', 'payment', 'escalation'])->default('general')->after('payment_plan_id');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('note_type');
            $table->enum('visibility', ['private', 'internal', 'client_visible'])->default('internal')->after('priority');
            $table->string('subject')->nullable()->after('visibility');
            $table->text('content')->after('subject');
            $table->json('metadata')->nullable()->after('content');
            $table->json('tags')->nullable()->after('metadata');
            
            // Communication details
            $table->enum('communication_method', ['phone', 'email', 'sms', 'in_person', 'video_call', 'letter', 'portal'])->nullable()->after('tags');
            $table->string('contact_person')->nullable()->after('communication_method');
            $table->string('contact_phone')->nullable()->after('contact_person');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->timestamp('contact_datetime')->nullable()->after('contact_email');
            $table->integer('call_duration_seconds')->nullable()->after('contact_datetime');
            
            // Outcome
            $table->enum('outcome', ['successful', 'unsuccessful', 'voicemail', 'no_answer', 'wrong_number', 'promise_to_pay', 'dispute', 'payment_made', 'escalated'])->nullable()->after('call_duration_seconds');
            $table->text('outcome_details')->nullable()->after('outcome');
            
            // Promise to pay
            $table->boolean('contains_promise_to_pay')->default(false)->after('outcome_details');
            $table->decimal('promised_amount', 15, 2)->nullable()->after('contains_promise_to_pay');
            $table->date('promised_payment_date')->nullable()->after('promised_amount');
            $table->boolean('promise_kept')->nullable()->after('promised_payment_date');
            $table->timestamp('promise_followed_up_at')->nullable()->after('promise_kept');
            
            // Dispute
            $table->boolean('contains_dispute')->default(false)->after('promise_followed_up_at');
            $table->string('dispute_reason')->nullable()->after('contains_dispute');
            $table->decimal('disputed_amount', 15, 2)->nullable()->after('dispute_reason');
            $table->enum('dispute_status', ['pending', 'investigating', 'resolved', 'rejected'])->nullable()->after('disputed_amount');
            $table->date('dispute_deadline')->nullable()->after('dispute_status');
            
            // Follow-up
            $table->boolean('requires_followup')->default(false)->after('dispute_deadline');
            $table->date('followup_date')->nullable()->after('requires_followup');
            $table->time('followup_time')->nullable()->after('followup_date');
            $table->string('followup_type')->nullable()->after('followup_time');
            $table->text('followup_instructions')->nullable()->after('followup_type');
            $table->boolean('followup_completed')->default(false)->after('followup_instructions');
            $table->timestamp('followup_completed_at')->nullable()->after('followup_completed');
            
            // Compliance
            $table->boolean('legally_significant')->default(false)->after('followup_completed_at');
            $table->boolean('compliance_sensitive')->default(false)->after('legally_significant');
            $table->json('compliance_flags')->nullable()->after('compliance_sensitive');
            $table->boolean('attorney_review_required')->default(false)->after('compliance_flags');
            $table->boolean('attorney_reviewed')->default(false)->after('attorney_review_required');
            $table->foreignId('reviewed_by_attorney_id')->nullable()->after('attorney_reviewed')->constrained('users')->onDelete('set null');
            $table->timestamp('attorney_reviewed_at')->nullable()->after('reviewed_by_attorney_id');
            
            // Client sentiment
            $table->enum('client_mood', ['cooperative', 'neutral', 'frustrated', 'angry', 'threatening'])->nullable()->after('attorney_reviewed_at');
            $table->integer('satisfaction_rating')->nullable()->after('client_mood');
            $table->enum('escalation_risk', ['low', 'medium', 'high'])->nullable()->after('satisfaction_rating');
            $table->text('relationship_notes')->nullable()->after('escalation_risk');
            
            // Financial snapshot
            $table->decimal('invoice_balance_at_time', 15, 2)->nullable()->after('relationship_notes');
            $table->integer('days_overdue_at_time')->nullable()->after('invoice_balance_at_time');
            $table->decimal('total_account_balance', 15, 2)->nullable()->after('days_overdue_at_time');
            $table->text('payment_history_summary')->nullable()->after('total_account_balance');
            
            // Attachments and references
            $table->json('attachments')->nullable()->after('payment_history_summary');
            $table->json('related_documents')->nullable()->after('attachments');
            $table->string('external_reference')->nullable()->after('related_documents');
            
            // Time tracking
            $table->boolean('billable_time')->default(false)->after('external_reference');
            $table->integer('time_spent_minutes')->nullable()->after('billable_time');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('time_spent_minutes');
            $table->decimal('billable_amount', 15, 2)->nullable()->after('hourly_rate');
            
            // Quality control
            $table->boolean('quality_reviewed')->default(false)->after('billable_amount');
            $table->foreignId('quality_reviewed_by')->nullable()->after('quality_reviewed')->constrained('users')->onDelete('set null');
            $table->timestamp('quality_reviewed_at')->nullable()->after('quality_reviewed_by');
            $table->integer('quality_score')->nullable()->after('quality_reviewed_at');
            $table->text('quality_feedback')->nullable()->after('quality_score');
            
            // Flags
            $table->boolean('flagged_for_review')->default(false)->after('quality_feedback');
            $table->boolean('archived')->default(false)->after('flagged_for_review');
            
            // User tracking
            $table->foreignId('created_by')->nullable()->after('archived')->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            
            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['invoice_id']);
            $table->index(['note_type', 'priority']);
            $table->index(['followup_date']);
            $table->index(['contact_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_notes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['dunning_action_id']);
            $table->dropForeign(['payment_plan_id']);
            $table->dropForeign(['reviewed_by_attorney_id']);
            $table->dropForeign(['quality_reviewed_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            $table->dropColumn([
                'client_id', 'invoice_id', 'dunning_action_id', 'payment_plan_id',
                'note_type', 'priority', 'visibility', 'subject', 'content', 'metadata', 'tags',
                'communication_method', 'contact_person', 'contact_phone', 'contact_email',
                'contact_datetime', 'call_duration_seconds', 'outcome', 'outcome_details',
                'contains_promise_to_pay', 'promised_amount', 'promised_payment_date',
                'promise_kept', 'promise_followed_up_at', 'contains_dispute', 'dispute_reason',
                'disputed_amount', 'dispute_status', 'dispute_deadline', 'requires_followup',
                'followup_date', 'followup_time', 'followup_type', 'followup_instructions',
                'followup_completed', 'followup_completed_at', 'legally_significant',
                'compliance_sensitive', 'compliance_flags', 'attorney_review_required',
                'attorney_reviewed', 'reviewed_by_attorney_id', 'attorney_reviewed_at',
                'client_mood', 'satisfaction_rating', 'escalation_risk', 'relationship_notes',
                'invoice_balance_at_time', 'days_overdue_at_time', 'total_account_balance',
                'payment_history_summary', 'attachments', 'related_documents',
                'external_reference', 'billable_time', 'time_spent_minutes', 'hourly_rate',
                'billable_amount', 'quality_reviewed', 'quality_reviewed_by',
                'quality_reviewed_at', 'quality_score', 'quality_feedback',
                'flagged_for_review', 'archived', 'created_by', 'updated_by'
            ]);
        });
    }
};
