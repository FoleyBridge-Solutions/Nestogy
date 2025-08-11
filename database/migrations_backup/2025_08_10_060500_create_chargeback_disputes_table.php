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
        Schema::create('chargeback_disputes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('refund_request_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            
            // Chargeback identification
            $table->string('chargeback_id', 100)->unique();
            $table->string('case_number', 100)->nullable();
            $table->string('processor_reference', 100)->nullable();
            $table->string('merchant_reference', 100)->nullable();
            
            // Chargeback details
            $table->enum('chargeback_type', [
                'fraud', 'authorization', 'processing_error', 'consumer_dispute',
                'duplicate_processing', 'credit_not_processed', 'cancelled_recurring',
                'product_not_received', 'product_unacceptable', 'other'
            ]);
            
            $table->string('reason_code', 20);
            $table->text('reason_description');
            $table->text('cardholder_explanation')->nullable();
            
            // Status and lifecycle
            $table->enum('status', [
                'received', 'under_review', 'disputing', 'accepted',
                'won', 'lost', 'closed', 'expired'
            ])->default('received');
            
            $table->enum('dispute_stage', [
                'notification', 'chargeback', 'pre_arbitration', 
                'arbitration', 'compliance', 'fraud'
            ])->default('chargeback');
            
            // Financial information
            $table->decimal('chargeback_amount', 15, 2);
            $table->decimal('liability_amount', 15, 2);
            $table->decimal('chargeback_fee', 10, 2)->default(0.00);
            $table->decimal('representation_fee', 10, 2)->default(0.00);
            $table->string('currency_code', 3)->default('USD');
            
            // Original transaction details
            $table->decimal('original_transaction_amount', 15, 2);
            $table->date('original_transaction_date');
            $table->string('original_authorization_code', 20)->nullable();
            $table->string('original_gateway', 50)->nullable();
            $table->string('original_transaction_id')->nullable();
            
            // Card and payment details (masked for security)
            $table->string('card_last_four', 4);
            $table->string('card_brand', 20); // Visa, MasterCard, etc.
            $table->string('card_type', 20)->nullable(); // Credit, Debit
            $table->string('cardholder_name')->nullable();
            
            // Important dates
            $table->date('chargeback_date');
            $table->date('notification_date');
            $table->date('response_due_date');
            $table->date('final_due_date')->nullable();
            $table->datetime('received_at');
            $table->datetime('responded_at')->nullable();
            $table->datetime('resolved_at')->nullable();
            
            // Dispute response and evidence
            $table->boolean('will_contest')->default(false);
            $table->text('contest_reason')->nullable();
            $table->json('evidence_submitted')->nullable();
            $table->json('supporting_documents')->nullable();
            $table->text('merchant_response')->nullable();
            $table->decimal('evidence_strength_score', 5, 2)->nullable();
            
            // Representment details
            $table->boolean('represented')->default(false);
            $table->datetime('representment_submitted_at')->nullable();
            $table->decimal('representment_amount', 15, 2)->nullable();
            $table->text('representment_reason')->nullable();
            $table->json('representment_evidence')->nullable();
            
            // Risk and fraud analysis
            $table->decimal('fraud_score', 5, 2)->nullable();
            $table->json('risk_indicators')->nullable();
            $table->boolean('suspected_friendly_fraud')->default(false);
            $table->boolean('customer_dispute_history')->default(false);
            $table->integer('previous_chargebacks_count')->default(0);
            
            // Customer information and history
            $table->json('customer_profile')->nullable();
            $table->json('transaction_history')->nullable();
            $table->boolean('customer_contacted')->default(false);
            $table->datetime('customer_contact_date')->nullable();
            $table->text('customer_response')->nullable();
            
            // Service and product details
            $table->json('service_details')->nullable();
            $table->boolean('service_delivered')->default(true);
            $table->date('service_delivery_date')->nullable();
            $table->json('delivery_confirmation')->nullable();
            $table->text('service_description')->nullable();
            
            // Communication and correspondence
            $table->json('correspondence_log')->nullable();
            $table->datetime('last_contact_date')->nullable();
            $table->boolean('processor_notified')->default(false);
            $table->datetime('processor_notification_date')->nullable();
            
            // Financial impact and accounting
            $table->decimal('revenue_impact', 15, 2)->default(0.00);
            $table->decimal('fee_impact', 10, 2)->default(0.00);
            $table->boolean('accounting_entry_posted')->default(false);
            $table->string('gl_account_code', 50)->nullable();
            $table->json('accounting_entries')->nullable();
            
            // Prevention and learning
            $table->text('prevention_notes')->nullable();
            $table->json('lessons_learned')->nullable();
            $table->boolean('process_improvement_needed')->default(false);
            $table->text('improvement_suggestions')->nullable();
            
            // External integrations
            $table->string('processor_case_id')->nullable();
            $table->string('acquirer_reference')->nullable();
            $table->json('processor_data')->nullable();
            $table->json('gateway_data')->nullable();
            
            // Monitoring and alerts
            $table->boolean('high_priority')->default(false);
            $table->boolean('alerts_sent')->default(false);
            $table->json('alert_history')->nullable();
            $table->datetime('escalation_date')->nullable();
            
            // Resolution and outcome
            $table->enum('resolution_outcome', [
                'won_full', 'won_partial', 'lost_full', 'lost_partial',
                'settled', 'withdrawn', 'expired', 'liability_shifted'
            ])->nullable();
            
            $table->decimal('recovery_amount', 15, 2)->nullable();
            $table->decimal('net_loss', 15, 2)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('final_settlement')->nullable();
            
            // Compliance and reporting
            $table->json('compliance_data')->nullable();
            $table->boolean('reported_to_networks')->default(false);
            $table->json('network_reporting')->nullable();
            $table->boolean('requires_regulatory_reporting')->default(false);
            
            // Metadata and audit
            $table->json('metadata')->nullable();
            $table->json('audit_trail')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'chargeback_date']);
            $table->index(['payment_id']);
            $table->index(['chargeback_id']);
            $table->index(['status', 'response_due_date']);
            $table->index(['chargeback_type', 'reason_code']);
            $table->index(['will_contest', 'response_due_date']);
            $table->index(['high_priority', 'escalation_date']);
            $table->index(['suspected_friendly_fraud']);
            $table->index(['processor_case_id']);
            $table->index(['received_at', 'resolved_at']);
            $table->index(['card_last_four', 'card_brand']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('refund_request_id')->references('id')->on('refund_requests')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chargeback_disputes');
    }
};