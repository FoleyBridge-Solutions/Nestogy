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
        Schema::create('contract_billing_calculations', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy - CRITICAL: Required for BelongsToCompany trait
            $table->unsignedBigInteger('company_id')->index();

            // Core relationship
            $table->unsignedBigInteger('contract_id')->index();

            // Billing period definition
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->enum('billing_type', ['monthly', 'quarterly', 'annually', 'custom', 'one_time'])->default('monthly');
            $table->string('period_description')->nullable(); // Human-readable period description

            // Base contract calculations
            $table->decimal('base_contract_amount', 10, 2)->default(0.00);
            $table->decimal('fixed_monthly_charges', 10, 2)->default(0.00);
            $table->json('base_charges_breakdown')->nullable(); // Detailed breakdown of base charges

            // Asset-based billing calculations
            $table->integer('total_assets')->default(0);
            $table->integer('workstation_count')->default(0);
            $table->integer('server_count')->default(0);
            $table->integer('network_device_count')->default(0);
            $table->integer('mobile_device_count')->default(0);
            $table->json('asset_counts_by_type')->nullable(); // Detailed asset type breakdown
            $table->decimal('asset_billing_total', 10, 2)->default(0.00);
            $table->json('asset_billing_breakdown')->nullable(); // Per-asset billing details

            // Contact-based billing calculations
            $table->integer('total_contacts')->default(0);
            $table->integer('basic_access_contacts')->default(0);
            $table->integer('standard_access_contacts')->default(0);
            $table->integer('premium_access_contacts')->default(0);
            $table->integer('admin_access_contacts')->default(0);
            $table->json('contact_access_breakdown')->nullable(); // Detailed contact tier breakdown
            $table->decimal('contact_billing_total', 10, 2)->default(0.00);
            $table->json('contact_billing_breakdown')->nullable(); // Per-contact billing details

            // Usage-based calculations
            $table->integer('total_tickets_created')->default(0);
            $table->decimal('total_support_hours', 8, 2)->default(0.00);
            $table->integer('total_incidents_resolved')->default(0);
            $table->decimal('usage_charges', 10, 2)->default(0.00);
            $table->json('usage_breakdown')->nullable(); // Detailed usage metrics

            // Service-specific calculations
            $table->json('service_charges')->nullable(); // Charges per service type
            $table->decimal('monitoring_charges', 10, 2)->default(0.00);
            $table->decimal('backup_charges', 10, 2)->default(0.00);
            $table->decimal('security_charges', 10, 2)->default(0.00);
            $table->decimal('maintenance_charges', 10, 2)->default(0.00);
            $table->json('additional_service_charges')->nullable(); // Other service charges

            // Adjustments and modifiers
            $table->decimal('discounts_applied', 10, 2)->default(0.00);
            $table->decimal('surcharges_applied', 10, 2)->default(0.00);
            $table->json('pricing_adjustments')->nullable(); // Detailed adjustments
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 4)->default(0.0000);

            // Totals and summary
            $table->decimal('subtotal_before_tax', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2); // Final total amount
            $table->string('currency_code', 3)->default('USD');

            // Calculation metadata
            $table->enum('calculation_method', ['manual', 'automatic', 'scheduled', 'triggered'])->default('automatic');
            $table->json('calculation_rules')->nullable(); // Rules used for this calculation
            $table->json('formula_applied')->nullable(); // Formula/logic used
            $table->json('line_items')->nullable(); // Detailed line-by-line breakdown
            $table->json('calculation_log')->nullable(); // Step-by-step calculation process

            // Approval and processing status
            $table->enum('status', ['draft', 'calculated', 'reviewed', 'approved', 'invoiced', 'disputed'])->default('draft')->index();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('invoiced_at')->nullable();

            // Integration with invoicing
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->string('invoice_number')->nullable();
            $table->boolean('auto_invoice')->default(true);
            $table->date('invoice_due_date')->nullable();

            // Variance and analysis
            $table->decimal('previous_period_amount', 10, 2)->nullable();
            $table->decimal('amount_variance', 10, 2)->nullable();
            $table->decimal('variance_percentage', 5, 2)->nullable();
            $table->json('variance_analysis')->nullable(); // Analysis of changes

            // Forecasting and predictions
            $table->decimal('projected_next_period', 10, 2)->nullable();
            $table->json('forecasting_data')->nullable(); // Data used for projections
            $table->json('trend_analysis')->nullable(); // Usage trends

            // Dispute and adjustment tracking
            $table->boolean('has_disputes')->default(false);
            $table->json('dispute_details')->nullable(); // Dispute information
            $table->decimal('disputed_amount', 10, 2)->default(0.00);
            $table->json('adjustments_made')->nullable(); // Post-calculation adjustments

            // Performance metrics
            $table->integer('calculation_duration_ms')->nullable(); // How long calculation took
            $table->json('performance_metrics')->nullable(); // Calculation performance data
            $table->text('calculation_notes')->nullable();

            // User tracking
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->onDelete('cascade');

            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('set null');

            $table->foreign('calculated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'contract_id'], 'cbc_company_contract_idx'); // Calculations by company and contract
            $table->index(['billing_period_start', 'billing_period_end'], 'cbc_billing_period_idx'); // Period-based queries
            $table->index(['contract_id', 'billing_period_start'], 'cbc_contract_start_idx'); // Contract billing history
            $table->index(['status', 'billing_period_end'], 'cbc_status_end_idx'); // Status-based filtering
            $table->index(['billing_type'], 'cbc_billing_type_idx'); // Billing frequency analysis
            $table->index(['calculated_at'], 'cbc_calculated_at_idx'); // Calculation timeline
            $table->index(['total_amount'], 'cbc_total_amount_idx'); // Amount-based sorting
            $table->index(['invoice_id'], 'cbc_invoice_id_idx'); // Invoice relationship
            $table->index(['has_disputes'], 'cbc_has_disputes_idx'); // Dispute tracking
            $table->index(['auto_invoice', 'status'], 'cbc_auto_invoice_status_idx'); // Auto-invoicing queries

            // Unique constraints to prevent duplicate calculations
            $table->unique(['contract_id', 'billing_period_start', 'billing_period_end'], 'unique_contract_billing_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_billing_calculations');
    }
};
