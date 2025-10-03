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
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('prefix')->nullable();
            $table->string('number')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->default('active');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->string('remaining_balance')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('exchange_rate')->nullable();
            $table->string('tax_breakdown')->nullable();
            $table->string('voip_tax_reversal')->nullable();
            $table->string('jurisdiction_taxes')->nullable();
            $table->string('reason_code')->nullable();
            $table->text('reason_description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('recurring_invoice_id')->nullable();
            $table->string('affects_recurring')->nullable();
            $table->string('proration_details')->nullable();
            $table->string('approval_workflow')->nullable();
            $table->string('approval_threshold')->nullable();
            $table->string('requires_executive_approval')->nullable();
            $table->string('requires_finance_review')->nullable();
            $table->string('requires_legal_review')->nullable();
            $table->string('affects_revenue_recognition')->nullable();
            $table->string('revenue_impact')->nullable();
            $table->string('gl_account_code')->nullable();
            $table->timestamp('credit_date')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('external_id')->nullable();
            $table->unsignedBigInteger('gateway_refund_id')->nullable();
            $table->string('gateway_response')->nullable();
            $table->string('metadata')->nullable();
            $table->string('original_invoice_data')->nullable();
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
        Schema::dropIfExists('credit_notes');
    }
};
