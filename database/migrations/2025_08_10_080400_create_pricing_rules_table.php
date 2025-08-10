<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration for Pricing Rules Table
 * 
 * Complex pricing rule engine for dynamic usage-based billing with conditional logic,
 * time-based variations, and contract-specific pricing overrides.
 */
class CreatePricingRulesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index()->comment('Client-specific rule override');
            $table->unsignedBigInteger('contract_id')->nullable()->index()->comment('Contract-specific pricing');

            // Rule Configuration
            $table->string('rule_name', 100)->comment('Descriptive name for the pricing rule');
            $table->string('rule_code', 50)->index()->comment('Unique rule identifier');
            $table->string('rule_type', 30)->comment('standard, promotional, contract, override, emergency');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();

            // Rule Priority and Application
            $table->integer('rule_priority')->default(100)->comment('Priority for rule evaluation (1=highest)');
            $table->boolean('is_global_rule')->default(false)->comment('Applies to all clients');
            $table->boolean('is_default_rule')->default(false)->comment('Default rule for service type');
            $table->string('rule_scope', 30)->default('client')->comment('client, group, global, contract');

            // Service and Usage Classification
            $table->string('usage_type', 50)->index()->comment('voice, data, sms, mms, feature, equipment, api');
            $table->json('service_types')->nullable()->comment('Array of applicable service types');
            $table->json('included_services')->nullable()->comment('Specific services this rule applies to');
            $table->json('excluded_services')->nullable()->comment('Services excluded from this rule');

            // Pricing Model Configuration
            $table->string('pricing_model', 30)->comment('tiered, flat_rate, usage_based, block, hybrid');
            $table->string('billing_frequency', 20)->default('monthly')->comment('monthly, daily, usage_based, real_time');
            $table->boolean('is_prepaid')->default(false)->comment('Prepaid or postpaid billing');
            $table->boolean('requires_commitment')->default(false)->comment('Minimum usage commitment required');

            // Base Pricing Structure
            $table->decimal('base_rate', 10, 6)->nullable()->comment('Base rate per unit');
            $table->decimal('setup_fee', 10, 4)->default(0)->comment('One-time setup fee');
            $table->decimal('monthly_fee', 10, 4)->default(0)->comment('Monthly recurring fee');
            $table->decimal('minimum_charge', 10, 4)->default(0)->comment('Minimum monthly charge');
            $table->string('rate_unit', 20)->comment('minute, mb, gb, message, call, line, month');

            // Time-based Pricing Variations
            $table->boolean('has_time_based_pricing')->default(false);
            $table->json('time_based_rates')->nullable()->comment('Peak/off-peak rate configurations');
            $table->json('peak_hour_definitions')->nullable()->comment('Peak hour schedule definitions');
            $table->json('holiday_rates')->nullable()->comment('Holiday-specific rates');
            $table->json('weekend_rates')->nullable()->comment('Weekend-specific rates');

            // Geographic Pricing Rules
            $table->boolean('has_geographic_pricing')->default(false);
            $table->json('geographic_rates')->nullable()->comment('Location-based rate overrides');
            $table->json('international_rates')->nullable()->comment('International calling rates');
            $table->json('roaming_rates')->nullable()->comment('Roaming charges');
            $table->string('default_geographic_zone', 50)->nullable();

            // Volume and Tier Discounts
            $table->boolean('has_volume_discounts')->default(false);
            $table->json('volume_discount_tiers')->nullable()->comment('Volume-based discount structure');
            $table->decimal('loyalty_discount', 5, 3)->default(0)->comment('Loyalty discount percentage');
            $table->decimal('contract_discount', 5, 3)->default(0)->comment('Contract-based discount');
            $table->json('bulk_pricing_rules')->nullable()->comment('Bulk usage pricing');

            // Commitment and Minimum Usage Rules
            $table->decimal('minimum_monthly_commitment', 12, 2)->nullable()->comment('Minimum monthly spend commitment');
            $table->decimal('minimum_usage_commitment', 15, 4)->nullable()->comment('Minimum usage commitment');
            $table->decimal('commitment_penalty_rate', 10, 6)->nullable()->comment('Penalty rate for under-commitment');
            $table->json('commitment_terms')->nullable()->comment('Commitment terms and conditions');

            // Overage and Excess Usage
            $table->string('overage_handling', 30)->default('charge')->comment('charge, block, throttle, pool');
            $table->decimal('overage_rate', 10, 6)->nullable()->comment('Rate for overage usage');
            $table->decimal('overage_threshold', 15, 4)->nullable()->comment('Usage threshold for overage');
            $table->json('overage_rules')->nullable()->comment('Complex overage handling rules');

            // Promotional and Special Pricing
            $table->boolean('is_promotional')->default(false)->index();
            $table->string('promotion_code', 50)->nullable();
            $table->timestamp('promotion_start_date')->nullable();
            $table->timestamp('promotion_end_date')->nullable();
            $table->json('promotion_conditions')->nullable()->comment('Promotional terms and conditions');
            $table->decimal('promotional_discount', 5, 3)->default(0);

            // Rule Conditions and Logic
            $table->json('rule_conditions')->nullable()->comment('Complex conditional logic for rule application');
            $table->json('client_criteria')->nullable()->comment('Client-based criteria for rule application');
            $table->json('usage_criteria')->nullable()->comment('Usage-based criteria');
            $table->json('time_criteria')->nullable()->comment('Time-based criteria');
            $table->text('conditional_logic')->nullable()->comment('Custom conditional logic');

            // A/B Testing and Optimization
            $table->boolean('is_ab_test_rule')->default(false);
            $table->string('ab_test_group', 20)->nullable()->comment('A/B test group identifier');
            $table->decimal('test_allocation_percentage', 5, 2)->nullable()->comment('Percentage of traffic for test');
            $table->json('test_parameters')->nullable()->comment('A/B test parameters');
            $table->timestamp('test_start_date')->nullable();
            $table->timestamp('test_end_date')->nullable();

            // Rule Performance and Analytics
            $table->json('performance_metrics')->nullable()->comment('Rule performance data');
            $table->decimal('average_revenue_per_user', 10, 2)->default(0);
            $table->integer('total_applications')->default(0)->comment('Times rule has been applied');
            $table->decimal('total_revenue_generated', 15, 2)->default(0);
            $table->timestamp('last_applied_at')->nullable();

            // Tax and Regulatory Compliance
            $table->boolean('is_taxable')->default(true);
            $table->json('tax_category_mapping')->nullable()->comment('Tax category assignments');
            $table->json('regulatory_requirements')->nullable()->comment('Regulatory compliance data');
            $table->boolean('requires_regulatory_approval')->default(false);
            $table->string('regulatory_status', 20)->default('approved');

            // Billing Integration and Processing
            $table->json('billing_integration_settings')->nullable()->comment('Integration with billing systems');
            $table->string('billing_system_code', 50)->nullable();
            $table->boolean('auto_invoice_generation')->default(true);
            $table->json('invoice_line_item_mapping')->nullable()->comment('How to display on invoices');

            // Currency and Multi-Currency Support
            $table->string('primary_currency', 3)->default('USD');
            $table->json('supported_currencies')->nullable()->comment('Multi-currency support');
            $table->json('currency_conversion_rules')->nullable()->comment('Currency conversion settings');
            $table->boolean('dynamic_currency_rates')->default(false);

            // Effective Period and Versioning
            $table->timestamp('effective_date')->useCurrent()->index();
            $table->timestamp('expiry_date')->nullable();
            $table->string('rule_version', 20)->default('1.0');
            $table->unsignedBigInteger('superseded_by_rule_id')->nullable()->index();
            $table->boolean('is_current_version')->default(true);

            // External System Integration
            $table->string('external_rule_id', 100)->nullable()->index();
            $table->json('integration_metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status', 20)->nullable();

            // Rule Dependencies and Relationships
            $table->json('prerequisite_rules')->nullable()->comment('Rules that must be satisfied first');
            $table->json('conflicting_rules')->nullable()->comment('Rules that conflict with this one');
            $table->json('related_rules')->nullable()->comment('Related or dependent rules');

            // Approval and Workflow
            $table->string('approval_status', 20)->default('pending')->index()->comment('pending, approved, rejected');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->json('approval_workflow')->nullable()->comment('Approval workflow configuration');

            // Change Management and History
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('change_reason')->nullable();
            $table->json('change_history')->nullable()->comment('Historical changes to the rule');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedBigInteger('last_reviewed_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Key Constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('superseded_by_rule_id')->references('id')->on('pricing_rules')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('last_reviewed_by')->references('id')->on('users')->onDelete('set null');

            // Performance Indexes
            $table->index(['company_id', 'rule_type', 'is_active'], 'pricing_rules_type_active_idx');
            $table->index(['usage_type', 'pricing_model', 'effective_date'], 'pricing_rules_usage_model_idx');
            $table->index(['rule_priority', 'is_active', 'effective_date'], 'pricing_rules_priority_idx');
            $table->index(['client_id', 'is_active', 'effective_date'], 'pricing_rules_client_active_idx');
            $table->index(['is_promotional', 'promotion_start_date', 'promotion_end_date'], 'pricing_rules_promotion_idx');
            $table->index(['approval_status', 'effective_date'], 'pricing_rules_approval_idx');
            $table->index(['last_applied_at', 'total_applications'], 'pricing_rules_usage_stats_idx');

            // Unique Constraints
            $table->unique(['company_id', 'rule_code'], 'pricing_rules_code_unique');
        });

        // Add table comment
        DB::statement("ALTER TABLE pricing_rules COMMENT = 'Complex pricing rule engine for dynamic usage-based billing with conditional logic and time-based variations'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
}