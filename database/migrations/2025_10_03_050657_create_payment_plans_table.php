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
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('plan_number')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('dunning_action_id')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('status')->default('active');
            $table->text('description')->nullable();
            $table->decimal('original_amount', 15, 2)->default(0);
            $table->decimal('plan_amount', 15, 2)->default(0);
            $table->string('down_payment')->nullable();
            $table->string('monthly_payment')->nullable();
            $table->string('number_of_payments')->nullable();
            $table->string('interest_rate')->nullable();
            $table->string('setup_fee')->nullable();
            $table->string('late_fee')->nullable();
            $table->string('compound_interest')->nullable();
            $table->boolean('is_settlement')->default(false);
            $table->string('settlement_percentage')->nullable();
            $table->decimal('settlement_amount', 15, 2)->default(0);
            $table->string('settlement_terms')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('first_payment_date')->nullable();
            $table->string('payment_frequency')->nullable();
            $table->string('payment_day_of_month')->nullable();
            $table->timestamp('final_payment_date')->nullable();
            $table->string('grace_period_days')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_token')->nullable();
            $table->string('payment_method_details')->nullable();
            $table->string('auto_retry_failed_payments')->nullable();
            $table->timestamp('max_retry_attempts')->nullable();
            $table->string('payments_made')->nullable();
            $table->string('payments_missed')->nullable();
            $table->string('consecutive_missed')->nullable();
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->string('remaining_balance')->nullable();
            $table->timestamp('last_payment_date')->nullable();
            $table->decimal('last_payment_amount', 15, 2)->default(0);
            $table->string('in_default')->nullable();
            $table->timestamp('default_date')->nullable();
            $table->string('default_reason')->nullable();
            $table->string('cure_period_days')->nullable();
            $table->string('cure_deadline')->nullable();
            $table->string('cured')->nullable();
            $table->timestamp('cured_date')->nullable();
            $table->string('modification_count')->nullable();
            $table->string('modification_history')->nullable();
            $table->string('modification_reason')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamp('modified_at')->nullable();
            $table->string('report_to_credit_bureau')->nullable();
            $table->string('credit_reporting_details')->nullable();
            $table->string('positive_payment_history')->nullable();
            $table->string('negative_payment_history')->nullable();
            $table->string('terms_and_conditions')->nullable();
            $table->string('client_acknowledged_terms')->nullable();
            $table->timestamp('terms_acknowledged_at')->nullable();
            $table->string('electronic_signature')->nullable();
            $table->string('state_law_compliance')->nullable();
            $table->string('right_to_cancel_disclosed')->nullable();
            $table->string('send_payment_reminders')->nullable();
            $table->string('reminder_days_before')->nullable();
            $table->string('send_confirmation_receipts')->nullable();
            $table->string('send_default_notices')->nullable();
            $table->string('communication_preferences')->nullable();
            $table->string('success_probability')->nullable();
            $table->string('risk_factors')->nullable();
            $table->decimal('expected_recovery_amount', 15, 2)->default(0);
            $table->string('estimated_completion_days')->nullable();
            $table->string('approval_status')->default('active');
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->string('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
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
        Schema::dropIfExists('payment_plans');
    }
};
