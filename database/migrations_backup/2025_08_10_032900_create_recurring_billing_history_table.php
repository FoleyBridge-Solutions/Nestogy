<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Recurring Billing History Table
 * 
 * Maintains a complete audit trail of all recurring billing operations,
 * invoice generations, and billing calculations for compliance and tracking.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recurring_billing_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('recurring_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('invoice_id');
            
            // Billing period information
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->datetime('billing_date');
            $table->datetime('invoice_generated_at');
            
            // Amount breakdown
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('usage_charges', 12, 2)->default(0);
            $table->decimal('overage_charges', 12, 2)->default(0);
            $table->decimal('proration_adjustments', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            
            // Usage statistics
            $table->decimal('total_usage', 15, 4)->default(0);
            $table->string('usage_unit', 20)->default('minutes');
            $table->decimal('allowance_used_percentage', 5, 2)->default(0);
            $table->json('service_usage_breakdown')->nullable(); // Usage by service type
            
            // Billing configuration snapshot
            $table->string('frequency', 20);
            $table->string('billing_type', 20);
            $table->json('pricing_model_snapshot')->nullable();
            $table->json('service_tiers_snapshot')->nullable();
            
            // Processing information
            $table->enum('processing_status', ['successful', 'failed', 'retrying', 'cancelled'])
                  ->default('successful');
            $table->text('processing_notes')->nullable();
            $table->json('error_details')->nullable();
            $table->integer('retry_count')->default(0);
            $table->datetime('next_retry_at')->nullable();
            
            // Tax calculation details
            $table->json('tax_breakdown')->nullable();
            $table->json('exemptions_applied')->nullable();
            $table->string('tax_calculation_method', 50)->nullable();
            
            // External system integration
            $table->string('external_invoice_id', 100)->nullable();
            $table->json('integration_data')->nullable();
            
            // Audit trail
            $table->unsignedBigInteger('generated_by')->nullable(); // User ID
            $table->string('generation_method', 50)->default('automatic'); // automatic, manual
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('recurring_id')->references('id')->on('recurring')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['recurring_id', 'billing_date'], 'history_recurring_date_idx');
            $table->index(['client_id', 'billing_period_start', 'billing_period_end'], 'history_client_period_idx');
            $table->index(['invoice_id'], 'history_invoice_idx');
            $table->index(['processing_status', 'next_retry_at'], 'history_retry_idx');
            $table->index(['billing_date', 'total_amount'], 'history_date_amount_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_billing_history');
    }
};