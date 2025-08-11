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
        Schema::create('refund_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('refund_request_id');
            $table->unsignedBigInteger('original_payment_id')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            
            // Transaction identification
            $table->string('transaction_id', 100)->unique();
            $table->string('external_transaction_id', 100)->nullable();
            $table->string('batch_id', 100)->nullable(); // For batch processing
            
            // Transaction details
            $table->enum('transaction_type', [
                'credit_card_refund', 'ach_refund', 'wire_transfer_refund',
                'paypal_refund', 'stripe_refund', 'check_refund',
                'account_credit', 'manual_refund', 'chargeback_refund'
            ]);
            
            $table->enum('status', [
                'pending', 'processing', 'completed', 'failed',
                'cancelled', 'reversed', 'disputed', 'settled'
            ])->default('pending');
            
            // Financial details
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('processing_fee', 10, 2)->default(0.00);
            $table->decimal('gateway_fee', 10, 2)->default(0.00);
            $table->decimal('net_amount', 15, 2);
            $table->decimal('exchange_rate', 12, 6)->default(1.000000);
            
            // Gateway information
            $table->string('gateway', 50);
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_reference_number')->nullable();
            $table->json('gateway_request')->nullable();
            $table->json('gateway_response')->nullable();
            $table->string('gateway_status_code', 10)->nullable();
            $table->text('gateway_message')->nullable();
            $table->json('gateway_metadata')->nullable();
            
            // Credit card details (masked for security)
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand', 20)->nullable(); // Visa, MasterCard, etc.
            $table->string('card_type', 20)->nullable(); // Credit, Debit
            $table->string('authorization_code', 20)->nullable();
            
            // ACH and bank details (masked)
            $table->string('bank_name')->nullable();
            $table->string('account_last_four', 4)->nullable();
            $table->string('routing_number_masked')->nullable();
            $table->enum('account_type', ['checking', 'savings'])->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('ach_trace_number')->nullable();
            
            // Check details
            $table->string('check_number')->nullable();
            $table->decimal('check_amount', 15, 2)->nullable();
            $table->date('check_date')->nullable();
            $table->text('payee_address')->nullable();
            $table->string('check_memo')->nullable();
            $table->boolean('check_printed')->default(false);
            $table->boolean('check_mailed')->default(false);
            $table->string('tracking_number')->nullable();
            
            // PayPal specific
            $table->string('paypal_transaction_id')->nullable();
            $table->string('paypal_payer_id')->nullable();
            $table->string('paypal_correlation_id')->nullable();
            
            // Stripe specific
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_refund_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            
            // Retry and error handling
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->datetime('next_retry_at')->nullable();
            $table->json('error_log')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Risk and fraud detection
            $table->decimal('risk_score', 5, 2)->nullable();
            $table->json('risk_factors')->nullable();
            $table->boolean('requires_manual_review')->default(false);
            $table->boolean('flagged_as_suspicious')->default(false);
            $table->text('risk_notes')->nullable();
            
            // Chargeback and dispute information
            $table->boolean('chargeback_eligible')->default(false);
            $table->date('chargeback_deadline')->nullable();
            $table->string('chargeback_reason_code')->nullable();
            $table->decimal('chargeback_liability_amount', 15, 2)->nullable();
            
            // Settlement and reconciliation
            $table->boolean('settled')->default(false);
            $table->date('settlement_date')->nullable();
            $table->string('settlement_batch_id')->nullable();
            $table->decimal('settlement_amount', 15, 2)->nullable();
            $table->boolean('reconciled')->default(false);
            $table->date('reconciliation_date')->nullable();
            
            // Compliance and security
            $table->boolean('pci_compliant')->default(true);
            $table->json('compliance_data')->nullable();
            $table->string('security_token')->nullable();
            $table->boolean('tokenized')->default(false);
            
            // Notifications and customer communication
            $table->boolean('customer_notified')->default(false);
            $table->datetime('notification_sent_at')->nullable();
            $table->json('notification_details')->nullable();
            
            // Processing timestamps
            $table->datetime('initiated_at');
            $table->datetime('processed_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('failed_at')->nullable();
            $table->datetime('cancelled_at')->nullable();
            
            // SLA and performance metrics
            $table->integer('processing_time_seconds')->nullable();
            $table->boolean('sla_met')->default(true);
            $table->datetime('sla_deadline')->nullable();
            
            // Audit trail
            $table->json('audit_trail')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['refund_request_id', 'status']);
            $table->index(['transaction_id']);
            $table->index(['gateway', 'gateway_transaction_id']);
            $table->index(['status', 'initiated_at']);
            $table->index(['transaction_type', 'status']);
            $table->index(['batch_id']);
            $table->index(['settled', 'settlement_date']);
            $table->index(['reconciled', 'reconciliation_date']);
            $table->index(['requires_manual_review', 'flagged_as_suspicious'], 'refund_manual_review_idx');
            $table->index(['chargeback_eligible', 'chargeback_deadline'], 'refund_chargeback_idx');
            $table->index(['next_retry_at', 'retry_count']);
            $table->index(['external_transaction_id']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('refund_request_id')->references('id')->on('refund_requests')->onDelete('cascade');
            $table->foreign('original_payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_transactions');
    }
};