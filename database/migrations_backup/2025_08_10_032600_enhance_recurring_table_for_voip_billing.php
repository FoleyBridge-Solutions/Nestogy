<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enhance Recurring Table for VoIP Billing
 * 
 * Adds comprehensive VoIP-specific fields to support sophisticated recurring billing
 * including usage-based billing, tiered pricing, proration, contract escalations,
 * and automated invoice generation with VoIP tax integration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recurring', function (Blueprint $table) {
            // Add company_id if it doesn't exist (for multi-tenancy)
            if (!Schema::hasColumn('recurring', 'company_id')) {
                $table->unsignedBigInteger('company_id')->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }

            // Add quote reference if it doesn't exist
            if (!Schema::hasColumn('recurring', 'quote_id')) {
                $table->unsignedBigInteger('quote_id')->nullable()->after('category_id');
                $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            }

            // Add end date and billing configuration fields
            if (!Schema::hasColumn('recurring', 'end_date')) {
                $table->datetime('end_date')->nullable()->after('next_date');
            }

            // Billing type and configuration
            if (!Schema::hasColumn('recurring', 'billing_type')) {
                $table->enum('billing_type', ['fixed', 'usage_based', 'tiered', 'hybrid'])
                      ->default('fixed')
                      ->after('status');
            }

            // Enhanced discount handling
            if (!Schema::hasColumn('recurring', 'discount_type')) {
                $table->enum('discount_type', ['fixed', 'percentage'])
                      ->default('fixed')
                      ->after('discount_amount');
            }

            // Internal notes separate from client-facing notes
            if (!Schema::hasColumn('recurring', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('note');
            }

            // VoIP-specific configuration fields
            if (!Schema::hasColumn('recurring', 'voip_config')) {
                $table->json('voip_config')->nullable()->after('internal_notes')
                      ->comment('VoIP service configuration including line counts, features, etc.');
            }

            if (!Schema::hasColumn('recurring', 'pricing_model')) {
                $table->json('pricing_model')->nullable()->after('voip_config')
                      ->comment('Pricing model configuration for tiered and usage-based billing');
            }

            if (!Schema::hasColumn('recurring', 'service_tiers')) {
                $table->json('service_tiers')->nullable()->after('pricing_model')
                      ->comment('Service tier definitions with allowances and rates');
            }

            if (!Schema::hasColumn('recurring', 'usage_allowances')) {
                $table->json('usage_allowances')->nullable()->after('service_tiers')
                      ->comment('Monthly usage allowances by service type');
            }

            if (!Schema::hasColumn('recurring', 'overage_rates')) {
                $table->json('overage_rates')->nullable()->after('usage_allowances')
                      ->comment('Overage rates for usage beyond allowances');
            }

            // Automated invoice generation settings
            if (!Schema::hasColumn('recurring', 'auto_invoice_generation')) {
                $table->boolean('auto_invoice_generation')->default(true)->after('overage_rates');
            }

            if (!Schema::hasColumn('recurring', 'invoice_terms_days')) {
                $table->integer('invoice_terms_days')->default(30)->after('auto_invoice_generation');
            }

            if (!Schema::hasColumn('recurring', 'email_invoice')) {
                $table->boolean('email_invoice')->default(true)->after('invoice_terms_days');
            }

            if (!Schema::hasColumn('recurring', 'email_template')) {
                $table->string('email_template', 100)->nullable()->after('email_invoice');
            }

            // Proration settings
            if (!Schema::hasColumn('recurring', 'proration_enabled')) {
                $table->boolean('proration_enabled')->default(true)->after('email_template');
            }

            if (!Schema::hasColumn('recurring', 'proration_method')) {
                $table->enum('proration_method', ['daily', 'monthly', 'none'])
                      ->default('daily')
                      ->after('proration_enabled');
            }

            // Contract escalation settings
            if (!Schema::hasColumn('recurring', 'contract_escalation')) {
                $table->boolean('contract_escalation')->default(false)->after('proration_method');
            }

            if (!Schema::hasColumn('recurring', 'escalation_percentage')) {
                $table->decimal('escalation_percentage', 5, 2)->nullable()->after('contract_escalation');
            }

            if (!Schema::hasColumn('recurring', 'escalation_months')) {
                $table->integer('escalation_months')->nullable()->after('escalation_percentage');
            }

            if (!Schema::hasColumn('recurring', 'last_escalation')) {
                $table->datetime('last_escalation')->nullable()->after('escalation_months');
            }

            // Tax settings
            if (!Schema::hasColumn('recurring', 'tax_settings')) {
                $table->json('tax_settings')->nullable()->after('last_escalation')
                      ->comment('VoIP tax calculation settings and exemptions');
            }

            // Invoice generation limits
            if (!Schema::hasColumn('recurring', 'max_invoices')) {
                $table->integer('max_invoices')->nullable()->after('tax_settings')
                      ->comment('Maximum number of invoices to generate (null = unlimited)');
            }

            if (!Schema::hasColumn('recurring', 'invoices_generated')) {
                $table->integer('invoices_generated')->default(0)->after('max_invoices');
            }

            // Metadata for storing additional configuration
            if (!Schema::hasColumn('recurring', 'metadata')) {
                $table->json('metadata')->nullable()->after('invoices_generated')
                      ->comment('Additional metadata and temporary data storage');
            }

            // Update archived_at column to match soft delete pattern
            if (Schema::hasColumn('recurring', 'archived_at')) {
                $table->datetime('archived_at')->nullable()->change();
            }
        });

        // Add indexes for performance
        Schema::table('recurring', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'recurring_company_status_idx');
            $table->index(['next_date', 'status'], 'recurring_next_date_status_idx');
            $table->index(['client_id', 'status'], 'recurring_client_status_idx');
            $table->index('billing_type', 'recurring_billing_type_idx');
            $table->index('frequency', 'recurring_frequency_idx');
            $table->index(['end_date', 'status'], 'recurring_end_date_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('recurring_company_status_idx');
            $table->dropIndex('recurring_next_date_status_idx');
            $table->dropIndex('recurring_client_status_idx');
            $table->dropIndex('recurring_billing_type_idx');
            $table->dropIndex('recurring_frequency_idx');
            $table->dropIndex('recurring_end_date_status_idx');
        });

        Schema::table('recurring', function (Blueprint $table) {
            // Drop added columns in reverse order
            $columns = [
                'metadata',
                'invoices_generated',
                'max_invoices',
                'tax_settings',
                'last_escalation',
                'escalation_months',
                'escalation_percentage',
                'contract_escalation',
                'proration_method',
                'proration_enabled',
                'email_template',
                'email_invoice',
                'invoice_terms_days',
                'auto_invoice_generation',
                'overage_rates',
                'usage_allowances',
                'service_tiers',
                'pricing_model',
                'voip_config',
                'internal_notes',
                'discount_type',
                'billing_type',
                'end_date',
                'quote_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('recurring', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};