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
            $table->string('plan_number')->unique(); // PP-0001, etc.
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('invoice_id')->nullable(); // can be for specific invoice or general
            $table->unsignedBigInteger('dunning_action_id')->nullable(); // if created from dunning action
            
            // Plan details
            $table->string('plan_type')->default('standard'); // standard, hardship, settlement, custom
            $table->enum('status', [
                'draft', 'pending_approval', 'active', 'completed', 
                'defaulted', 'cancelled', 'renegotiated', 'settled'
            ])->default('draft');
            $table->text('description')->nullable();
            
            // Financial terms
            $table->decimal('original_amount', 15, 2); // total amount owed
            $table->decimal('plan_amount', 15, 2); // total plan amount (may include fees/interest)
            $table->decimal('down_payment', 10, 2)->default(0);
            $table->decimal('monthly_payment', 10, 2);
            $table->integer('number_of_payments');
            $table->decimal('interest_rate', 5, 4)->default(0); // annual percentage
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->boolean('compound_interest')->default(false);
            
            // Settlement terms (if applicable)
            $table->boolean('is_settlement')->default(false);
            $table->decimal('settlement_percentage', 5, 2)->nullable(); // % of original amount
            $table->decimal('settlement_amount', 15, 2)->nullable();
            $table->text('settlement_terms')->nullable();
            
            // Schedule and timing
            $table->date('start_date');
            $table->date('first_payment_date');
            $table->enum('payment_frequency', ['weekly', 'biweekly', 'monthly', 'quarterly'])->default('monthly');
            $table->integer('payment_day_of_month')->default(1); // 1-31, day of month for payments
            $table->date('final_payment_date');
            $table->integer('grace_period_days')->default(5); // days before payment is considered late
            
            // Payment method
            $table->enum('payment_method', [
                'auto_ach', 'auto_credit_card', 'manual_payment', 'check', 'wire_transfer'
            ])->default('manual_payment');
            $table->string('payment_token')->nullable(); // tokenized payment method
            $table->json('payment_method_details')->nullable(); // last 4 digits, etc.
            $table->boolean('auto_retry_failed_payments')->default(true);
            $table->integer('max_retry_attempts')->default(3);
            
            // Performance tracking
            $table->integer('payments_made')->default(0);
            $table->integer('payments_missed')->default(0);
            $table->integer('consecutive_missed')->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('remaining_balance', 15, 2);
            $table->date('last_payment_date')->nullable();
            $table->decimal('last_payment_amount', 10, 2)->nullable();
            
            // Default and recovery
            $table->boolean('in_default')->default(false);
            $table->date('default_date')->nullable();
            $table->text('default_reason')->nullable();
            $table->integer('cure_period_days')->default(30); // days to cure default
            $table->date('cure_deadline')->nullable();
            $table->boolean('cured')->default(false);
            $table->date('cured_date')->nullable();
            
            // Modification history
            $table->integer('modification_count')->default(0);
            $table->json('modification_history')->nullable(); // track changes
            $table->text('modification_reason')->nullable(); // reason for last modification
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_at')->nullable();
            
            // Credit impact
            $table->boolean('report_to_credit_bureau')->default(false);
            $table->json('credit_reporting_details')->nullable();
            $table->boolean('positive_payment_history')->default(false); // report good payments
            $table->boolean('negative_payment_history')->default(false); // report missed payments
            
            // Legal and compliance
            $table->json('terms_and_conditions')->nullable();
            $table->boolean('client_acknowledged_terms')->default(false);
            $table->timestamp('terms_acknowledged_at')->nullable();
            $table->string('electronic_signature')->nullable();
            $table->json('state_law_compliance')->nullable();
            $table->boolean('right_to_cancel_disclosed')->default(false);
            
            // Communication preferences
            $table->boolean('send_payment_reminders')->default(true);
            $table->integer('reminder_days_before')->default(3);
            $table->boolean('send_confirmation_receipts')->default(true);
            $table->boolean('send_default_notices')->default(true);
            $table->json('communication_preferences')->nullable(); // email, sms, mail
            
            // Success metrics
            $table->decimal('success_probability', 5, 2)->default(0); // ML-based prediction
            $table->json('risk_factors')->nullable(); // factors affecting success
            $table->decimal('expected_recovery_amount', 15, 2)->nullable();
            $table->integer('estimated_completion_days')->nullable();
            
            // Approval workflow
            $table->enum('approval_status', [
                'not_required', 'pending', 'approved', 'rejected', 'expired'
            ])->default('not_required');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['client_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['status', 'first_payment_date']);
            $table->index(['in_default', 'cure_deadline']);
            $table->index(['payment_method', 'first_payment_date']);
            $table->index(['approval_status', 'created_at']);
            $table->index('plan_number');
            
            // Foreign key constraints
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('dunning_action_id')->references('id')->on('dunning_actions')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('modified_by')->references('id')->on('users')->onDelete('set null');
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