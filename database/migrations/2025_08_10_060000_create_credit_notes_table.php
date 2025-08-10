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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            
            // Credit note identification
            $table->string('prefix', 10)->nullable();
            $table->string('number', 50);
            $table->string('reference_number', 100)->nullable(); // External reference
            
            // Credit note details
            $table->enum('type', [
                'full_refund', 'partial_refund', 'service_credit', 
                'adjustment_credit', 'promotional_credit', 'goodwill_credit', 
                'chargeback_credit', 'tax_adjustment', 'billing_correction'
            ]);
            
            $table->enum('status', [
                'draft', 'pending_approval', 'approved', 'applied', 
                'partially_applied', 'voided', 'expired'
            ])->default('draft');
            
            // Financial information
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('applied_amount', 15, 2)->default(0.00);
            $table->decimal('remaining_balance', 15, 2)->default(0.00);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1.000000);
            
            // Tax reversal information
            $table->json('tax_breakdown')->nullable(); // Detailed tax calculations
            $table->decimal('voip_tax_reversal', 10, 4)->default(0.0000);
            $table->json('jurisdiction_taxes')->nullable(); // VoIP jurisdiction-specific taxes
            
            // Reason and justification
            $table->enum('reason_code', [
                'billing_error', 'service_cancellation', 'equipment_return',
                'porting_failure', 'service_quality', 'customer_request',
                'chargeback', 'duplicate_billing', 'rate_adjustment',
                'regulatory_adjustment', 'goodwill', 'promotional'
            ]);
            $table->text('reason_description');
            $table->text('internal_notes')->nullable();
            $table->text('customer_notes')->nullable();
            
            // Contract and billing integration
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('recurring_invoice_id')->nullable();
            $table->boolean('affects_recurring')->default(false);
            $table->json('proration_details')->nullable();
            
            // Approval workflow
            $table->json('approval_workflow')->nullable(); // Multi-level approval tracking
            $table->decimal('approval_threshold', 15, 2)->nullable();
            $table->boolean('requires_executive_approval')->default(false);
            $table->boolean('requires_finance_review')->default(false);
            $table->boolean('requires_legal_review')->default(false);
            
            // Revenue recognition impact
            $table->boolean('affects_revenue_recognition')->default(true);
            $table->json('revenue_impact')->nullable();
            $table->string('gl_account_code', 50)->nullable();
            
            // Dates
            $table->date('credit_date');
            $table->date('expiry_date')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->datetime('applied_at')->nullable();
            $table->datetime('voided_at')->nullable();
            
            // Integration and external references
            $table->string('external_id', 100)->nullable();
            $table->string('gateway_refund_id')->nullable();
            $table->json('gateway_response')->nullable();
            
            // Metadata and audit
            $table->json('metadata')->nullable();
            $table->json('original_invoice_data')->nullable(); // Snapshot of original invoice
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['invoice_id']);
            $table->index(['credit_date', 'company_id']);
            $table->index(['type', 'status']);
            $table->index(['reason_code', 'type']);
            $table->index(['created_by', 'approved_by']);
            $table->index(['expiry_date']);
            $table->index(['requires_executive_approval', 'status']);
            $table->index(['number', 'company_id']);
            $table->index(['external_id']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};