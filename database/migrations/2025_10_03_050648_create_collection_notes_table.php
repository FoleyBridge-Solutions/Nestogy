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
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('dunning_action_id')->nullable();
            $table->unsignedBigInteger('payment_plan_id')->nullable();
            $table->string('note_type')->nullable();
            $table->string('priority')->nullable();
            $table->string('visibility')->nullable();
            $table->string('subject')->nullable();
            $table->string('content')->nullable();
            $table->string('metadata')->nullable();
            $table->string('tags')->nullable();
            $table->string('communication_method')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->timestamp('contact_datetime')->nullable();
            $table->string('call_duration_seconds')->nullable();
            $table->string('outcome')->nullable();
            $table->string('outcome_details')->nullable();
            $table->string('contains_promise_to_pay')->nullable();
            $table->decimal('promised_amount', 15, 2)->default(0);
            $table->timestamp('promised_payment_date')->nullable();
            $table->string('promise_kept')->nullable();
            $table->timestamp('promise_followed_up_at')->nullable();
            $table->string('contains_dispute')->nullable();
            $table->string('dispute_reason')->nullable();
            $table->decimal('disputed_amount', 15, 2)->default(0);
            $table->string('dispute_status')->default('active');
            $table->string('dispute_deadline')->nullable();
            $table->string('requires_followup')->nullable();
            $table->timestamp('followup_date')->nullable();
            $table->string('followup_time')->nullable();
            $table->string('followup_type')->nullable();
            $table->string('followup_instructions')->nullable();
            $table->string('followup_completed')->nullable();
            $table->timestamp('followup_completed_at')->nullable();
            $table->string('legally_significant')->nullable();
            $table->string('compliance_sensitive')->nullable();
            $table->string('compliance_flags')->nullable();
            $table->string('attorney_review_required')->nullable();
            $table->string('attorney_reviewed')->nullable();
            $table->unsignedBigInteger('reviewed_by_attorney_id')->nullable();
            $table->timestamp('attorney_reviewed_at')->nullable();
            $table->string('client_mood')->nullable();
            $table->string('satisfaction_rating')->nullable();
            $table->string('escalation_risk')->nullable();
            $table->text('relationship_notes')->nullable();
            $table->timestamp('invoice_balance_at_time')->nullable();
            $table->timestamp('days_overdue_at_time')->nullable();
            $table->decimal('total_account_balance', 15, 2)->default(0);
            $table->string('payment_history_summary')->nullable();
            $table->string('attachments')->nullable();
            $table->string('related_documents')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('billable_time')->nullable();
            $table->string('time_spent_minutes')->nullable();
            $table->string('hourly_rate')->nullable();
            $table->decimal('billable_amount', 15, 2)->default(0);
            $table->string('quality_reviewed')->nullable();
            $table->string('quality_reviewed_by')->nullable();
            $table->timestamp('quality_reviewed_at')->nullable();
            $table->string('quality_score')->nullable();
            $table->string('quality_feedback')->nullable();
            $table->string('flagged_for_review')->nullable();
            $table->string('archived')->nullable();
            $table->timestamp('archived_at')->nullable();
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
        Schema::dropIfExists('collection_notes');
    }
};
