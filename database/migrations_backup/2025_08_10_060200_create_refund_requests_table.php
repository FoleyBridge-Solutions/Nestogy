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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            
            // Request identification
            $table->string('request_number', 50)->unique();
            $table->string('external_reference', 100)->nullable();
            
            // Refund details
            $table->enum('refund_type', [
                'full_refund', 'partial_refund', 'service_credit',
                'equipment_return', 'chargeback_refund', 'goodwill_refund',
                'billing_error_refund', 'cancellation_refund', 'proration_refund'
            ]);
            
            $table->enum('refund_method', [
                'original_payment', 'credit_card', 'bank_transfer', 'ach',
                'check', 'paypal', 'stripe', 'account_credit', 'manual'
            ])->default('original_payment');
            
            // Status tracking
            $table->enum('status', [
                'pending', 'under_review', 'approved', 'rejected',
                'processing', 'completed', 'failed', 'cancelled'
            ])->default('pending');
            
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Financial details
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('processed_amount', 15, 2)->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1.000000);
            $table->decimal('processing_fee', 10, 2)->default(0.00);
            $table->decimal('net_refund_amount', 15, 2)->nullable();
            
            // Tax refund details
            $table->decimal('tax_refund_amount', 15, 2)->default(0.00);
            $table->json('tax_refund_breakdown')->nullable();
            $table->decimal('voip_tax_refund', 10, 4)->default(0.0000);
            $table->json('jurisdiction_tax_refunds')->nullable();
            
            // Reason and justification
            $table->enum('reason_code', [
                'billing_error', 'service_cancellation', 'equipment_return',
                'porting_failure', 'service_quality', 'duplicate_payment',
                'customer_request', 'chargeback', 'fraud', 'system_error',
                'regulatory_requirement', 'contract_termination'
            ]);
            
            $table->text('reason_description');
            $table->text('customer_explanation')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Service period and proration
            $table->date('service_period_start')->nullable();
            $table->date('service_period_end')->nullable();
            $table->boolean('is_prorated')->default(false);
            $table->json('proration_calculation')->nullable();
            $table->integer('unused_days')->nullable();
            $table->integer('total_period_days')->nullable();
            
            // Equipment return details
            $table->json('equipment_details')->nullable();
            $table->enum('equipment_condition', [
                'new', 'excellent', 'good', 'fair', 'poor', 'damaged', 'missing'
            ])->nullable();
            $table->decimal('condition_adjustment_percentage', 8, 4)->default(0.0000);
            $table->boolean('equipment_received')->default(false);
            $table->date('equipment_received_date')->nullable();
            $table->string('tracking_number')->nullable();
            
            // Contract and cancellation details
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->boolean('early_termination')->default(false);
            $table->decimal('early_termination_fee', 15, 2)->default(0.00);
            $table->date('contract_end_date')->nullable();
            $table->integer('remaining_contract_months')->nullable();
            
            // Gateway and payment processing
            $table->string('original_gateway', 50)->nullable();
            $table->string('original_transaction_id')->nullable();
            $table->string('refund_gateway', 50)->nullable();
            $table->string('refund_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->json('gateway_metadata')->nullable();
            
            // Banking and ACH details
            $table->string('bank_account_last_four', 4)->nullable();
            $table->string('routing_number_masked')->nullable();
            $table->enum('account_type', ['checking', 'savings'])->nullable();
            $table->string('account_holder_name')->nullable();
            
            // Check processing
            $table->string('check_number')->nullable();
            $table->text('mailing_address')->nullable();
            $table->date('check_printed_date')->nullable();
            $table->date('check_mailed_date')->nullable();
            $table->string('check_tracking_number')->nullable();
            
            // Approval workflow
            $table->json('approval_workflow')->nullable();
            $table->boolean('requires_manager_approval')->default(false);
            $table->boolean('requires_finance_approval')->default(false);
            $table->boolean('requires_executive_approval')->default(false);
            $table->decimal('approval_threshold', 15, 2)->nullable();
            
            // SLA and timing
            $table->integer('sla_hours')->default(48);
            $table->datetime('sla_deadline')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->integer('processing_time_hours')->nullable();
            
            // Customer communication
            $table->boolean('customer_notified')->default(false);
            $table->datetime('customer_notification_sent')->nullable();
            $table->json('notification_history')->nullable();
            
            // Compliance and audit
            $table->json('compliance_checks')->nullable();
            $table->boolean('requires_legal_review')->default(false);
            $table->boolean('pci_compliant')->default(true);
            $table->string('audit_trail_id')->nullable();
            
            // Important dates
            $table->datetime('requested_at');
            $table->datetime('reviewed_at')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->datetime('processed_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('cancelled_at')->nullable();
            
            // Metadata and integration
            $table->json('metadata')->nullable();
            $table->string('source_system', 50)->default('manual');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['request_number']);
            $table->index(['status', 'priority']);
            $table->index(['refund_type', 'status']);
            $table->index(['requested_at', 'company_id']);
            $table->index(['sla_deadline', 'status']);
            $table->index(['approved_by', 'processed_by']);
            $table->index(['refund_method', 'status']);
            $table->index(['requires_executive_approval', 'status']);
            $table->index(['equipment_received', 'equipment_received_date']);
            $table->index(['early_termination', 'contract_id']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};