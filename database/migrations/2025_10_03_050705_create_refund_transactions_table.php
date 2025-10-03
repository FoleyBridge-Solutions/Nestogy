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
            $table->unsignedBigInteger('refund_request_id')->nullable();
            $table->unsignedBigInteger('original_payment_id')->nullable();
            $table->string('processed_by')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('external_transaction_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('status')->default('active');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency_code')->nullable();
            $table->string('processing_fee')->nullable();
            $table->string('gateway_fee')->nullable();
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->string('exchange_rate')->nullable();
            $table->string('gateway')->nullable();
            $table->unsignedBigInteger('gateway_transaction_id')->nullable();
            $table->string('gateway_reference_number')->nullable();
            $table->string('gateway_request')->nullable();
            $table->string('gateway_response')->nullable();
            $table->string('gateway_status_code')->default('active');
            $table->string('gateway_message')->nullable();
            $table->string('gateway_metadata')->nullable();
            $table->string('card_last_four')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_type')->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('bank_name');
            $table->string('account_last_four')->nullable();
            $table->string('routing_number_masked')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_holder_name');
            $table->string('ach_trace_number')->nullable();
            $table->string('check_number')->nullable();
            $table->decimal('check_amount', 15, 2)->default(0);
            $table->timestamp('check_date')->nullable();
            $table->string('payee_address')->nullable();
            $table->string('check_memo')->nullable();
            $table->string('check_printed')->nullable();
            $table->string('check_mailed')->nullable();
            $table->string('tracking_number')->nullable();
            $table->unsignedBigInteger('paypal_transaction_id')->nullable();
            $table->unsignedBigInteger('paypal_payer_id')->nullable();
            $table->unsignedBigInteger('paypal_correlation_id')->nullable();
            $table->unsignedBigInteger('stripe_charge_id')->nullable();
            $table->unsignedBigInteger('stripe_refund_id')->nullable();
            $table->unsignedBigInteger('stripe_payment_intent_id')->nullable();
            $table->string('retry_count')->nullable();
            $table->string('max_retries')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->string('error_log')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('risk_score')->nullable();
            $table->string('risk_factors')->nullable();
            $table->string('requires_manual_review')->nullable();
            $table->string('flagged_as_suspicious')->nullable();
            $table->text('risk_notes')->nullable();
            $table->string('chargeback_eligible')->nullable();
            $table->string('chargeback_deadline')->nullable();
            $table->string('chargeback_reason_code')->nullable();
            $table->decimal('chargeback_liability_amount', 15, 2)->default(0);
            $table->string('settled')->nullable();
            $table->timestamp('settlement_date')->nullable();
            $table->unsignedBigInteger('settlement_batch_id')->nullable();
            $table->decimal('settlement_amount', 15, 2)->default(0);
            $table->string('reconciled')->nullable();
            $table->timestamp('reconciliation_date')->nullable();
            $table->string('pci_compliant')->nullable();
            $table->string('compliance_data')->nullable();
            $table->string('security_token')->nullable();
            $table->string('tokenized')->nullable();
            $table->string('customer_notified')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->string('notification_details')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('processing_time_seconds')->nullable();
            $table->string('sla_met')->nullable();
            $table->string('sla_deadline')->nullable();
            $table->string('audit_trail')->nullable();
            $table->unsignedBigInteger('correlation_id')->nullable();
            $table->string('metadata')->nullable();
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
        Schema::dropIfExists('refund_transactions');
    }
};
