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
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('requested_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('processed_by')->nullable();
            $table->string('request_number')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('refund_type')->nullable();
            $table->string('refund_method')->nullable();
            $table->string('status')->default('active');
            $table->string('priority')->nullable();
            $table->decimal('requested_amount', 15, 2)->default(0);
            $table->decimal('approved_amount', 15, 2)->default(0);
            $table->decimal('processed_amount', 15, 2)->default(0);
            $table->string('currency_code')->nullable();
            $table->string('exchange_rate')->nullable();
            $table->string('processing_fee')->nullable();
            $table->decimal('net_refund_amount', 15, 2)->default(0);
            $table->decimal('tax_refund_amount', 15, 2)->default(0);
            $table->string('tax_refund_breakdown')->nullable();
            $table->string('voip_tax_refund')->nullable();
            $table->string('jurisdiction_tax_refunds')->nullable();
            $table->string('reason_code')->nullable();
            $table->text('reason_description')->nullable();
            $table->string('customer_explanation')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('service_period_start')->nullable();
            $table->string('service_period_end')->nullable();
            $table->boolean('is_prorated')->default(false);
            $table->string('proration_calculation')->nullable();
            $table->string('unused_days')->nullable();
            $table->decimal('total_period_days', 15, 2)->default(0);
            $table->string('equipment_details')->nullable();
            $table->string('equipment_condition')->nullable();
            $table->string('condition_adjustment_percentage')->nullable();
            $table->string('equipment_received')->nullable();
            $table->timestamp('equipment_received_date')->nullable();
            $table->string('tracking_number')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('early_termination')->nullable();
            $table->string('early_termination_fee')->nullable();
            $table->timestamp('contract_end_date')->nullable();
            $table->string('remaining_contract_months')->nullable();
            $table->string('original_gateway')->nullable();
            $table->unsignedBigInteger('original_transaction_id')->nullable();
            $table->string('refund_gateway')->nullable();
            $table->unsignedBigInteger('refund_transaction_id')->nullable();
            $table->string('gateway_response')->nullable();
            $table->string('gateway_metadata')->nullable();
            $table->string('bank_account_last_four')->nullable();
            $table->string('routing_number_masked')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_holder_name');
            $table->string('check_number')->nullable();
            $table->string('mailing_address')->nullable();
            $table->timestamp('check_printed_date')->nullable();
            $table->timestamp('check_mailed_date')->nullable();
            $table->string('check_tracking_number')->nullable();
            $table->string('approval_workflow')->nullable();
            $table->string('requires_manager_approval')->nullable();
            $table->string('requires_finance_approval')->nullable();
            $table->string('requires_executive_approval')->nullable();
            $table->string('approval_threshold')->nullable();
            $table->string('sla_hours')->nullable();
            $table->string('sla_deadline')->nullable();
            $table->string('sla_breached')->nullable();
            $table->string('processing_time_hours')->nullable();
            $table->string('customer_notified')->nullable();
            $table->string('customer_notification_sent')->nullable();
            $table->string('notification_history')->nullable();
            $table->string('compliance_checks')->nullable();
            $table->string('requires_legal_review')->nullable();
            $table->string('pci_compliant')->nullable();
            $table->unsignedBigInteger('audit_trail_id')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('metadata')->nullable();
            $table->string('source_system')->nullable();
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
        Schema::dropIfExists('refund_requests');
    }
};
