<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration for Usage Tiers Table
 * 
 * Manages tiered pricing structures for usage-based billing with support for
 * progressive rates, volume discounts, and time-of-day pricing variations.
 */
class CreateUsageTiersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('pricing_rule_id')->index();

            // Tier Configuration
            $table->string('tier_name', 100)->comment('Display name for the tier');
            $table->string('tier_code', 50)->index()->comment('Unique code for the tier');
            $table->text('description')->nullable();
            $table->integer('tier_order')->comment('Order for tier evaluation');
            $table->boolean('is_active')->default(true)->index();

            // Usage Type and Service Classification
            $table->string('usage_type', 50)->index()->comment('voice, data, sms, mms, feature, equipment, api');
            $table->string('service_type', 50)->index()->comment('local, long_distance, international, etc.');
            $table->json('applicable_services')->nullable()->comment('Array of specific services this tier applies to');

            // Tier Boundaries
            $table->decimal('min_usage', 15, 4)->default(0)->comment('Minimum usage for this tier');
            $table->decimal('max_usage', 15, 4)->nullable()->comment('Maximum usage for this tier (null = unlimited)');
            $table->string('usage_unit', 20)->comment('minute, mb, gb, message, call, line, api_call');
            $table->boolean('is_unlimited_tier')->default(false)->comment('True if this is the final unlimited tier');

            // Pricing Configuration
            $table->string('pricing_model', 30)->comment('flat_rate, per_unit, block_pricing, progressive');
            $table->decimal('base_rate', 10, 6)->nullable()->comment('Base rate for the tier');
            $table->decimal('per_unit_rate', 10, 6)->nullable()->comment('Rate per unit within the tier');
            $table->decimal('block_size', 10, 2)->nullable()->comment('Block size for block pricing');
            $table->decimal('block_rate', 10, 4)->nullable()->comment('Rate per block');
            $table->decimal('setup_fee', 10, 4)->default(0)->comment('One-time setup fee for reaching this tier');

            // Time-based Pricing Variations
            $table->boolean('has_peak_pricing')->default(false);
            $table->decimal('peak_rate_multiplier', 4, 3)->default(1.000)->comment('Multiplier for peak hours');
            $table->decimal('off_peak_rate_multiplier', 4, 3)->default(1.000)->comment('Multiplier for off-peak hours');
            $table->decimal('weekend_rate_multiplier', 4, 3)->default(1.000)->comment('Multiplier for weekends');
            $table->json('peak_hours')->nullable()->comment('Array of peak hour definitions');
            $table->json('time_zone_rules')->nullable()->comment('Time zone specific pricing rules');

            // Geographic Pricing
            $table->boolean('has_geographic_pricing')->default(false);
            $table->json('geographic_rates')->nullable()->comment('Country/state specific rate overrides');
            $table->json('destination_rates')->nullable()->comment('Destination-based rate overrides');

            // Volume and Commitment Discounts
            $table->boolean('has_volume_discounts')->default(false);
            $table->json('volume_discount_rules')->nullable()->comment('Volume-based discount rules');
            $table->decimal('commitment_discount', 5, 3)->default(0)->comment('Discount for committed usage');
            $table->decimal('loyalty_discount', 5, 3)->default(0)->comment('Loyalty-based discount percentage');

            // Overage and Rollover Rules
            $table->string('overage_handling', 30)->default('charge')->comment('charge, block, throttle, pool');
            $table->decimal('overage_rate', 10, 6)->nullable()->comment('Rate for usage exceeding tier limits');
            $table->boolean('allows_rollover')->default(false)->comment('Allow unused allowance rollover');
            $table->integer('rollover_months')->default(0)->comment('Number of months rollover is valid');
            $table->decimal('rollover_percentage', 5, 2)->default(100)->comment('Percentage of unused allowance that rolls over');

            // Billing and Proration
            $table->string('billing_frequency', 20)->default('monthly')->comment('monthly, daily, usage_based');
            $table->boolean('is_prorated')->default(true)->comment('Prorate charges for partial periods');
            $table->string('proration_method', 20)->default('daily')->comment('daily, hourly, usage_based');
            $table->boolean('requires_advance_payment')->default(false);
            $table->integer('advance_payment_days')->default(0);

            // Tier Conditions and Rules
            $table->json('tier_conditions')->nullable()->comment('Complex conditions for tier activation');
            $table->json('bundling_rules')->nullable()->comment('Rules for bundling with other services');
            $table->json('exclusion_rules')->nullable()->comment('Conditions that exclude this tier');

            // Tax and Compliance
            $table->boolean('is_taxable')->default(true);
            $table->json('tax_category_mapping')->nullable()->comment('Tax category assignments by jurisdiction');
            $table->json('regulatory_compliance')->nullable()->comment('Regulatory requirements and classifications');

            // Effective Period
            $table->timestamp('effective_date')->useCurrent()->index();
            $table->timestamp('expiry_date')->nullable();
            $table->boolean('is_promotional')->default(false)->index();
            $table->string('promotion_code', 50)->nullable();

            // Reporting and Analytics
            $table->json('reporting_categories')->nullable()->comment('Categories for reporting and analytics');
            $table->boolean('track_detailed_usage')->default(true)->comment('Enable detailed usage tracking');
            $table->json('kpi_targets')->nullable()->comment('Key performance indicator targets');

            // External System Integration
            $table->string('external_tier_id', 100)->nullable()->index();
            $table->string('billing_system_code', 50)->nullable();
            $table->json('integration_metadata')->nullable();

            // Audit and History
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('change_reason')->nullable();
            $table->json('tier_history')->nullable()->comment('Historical changes to tier configuration');

            $table->timestamps();
            $table->softDeletes();

            // Foreign Key Constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('pricing_rule_id')->references('id')->on('pricing_rules')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for Performance
            $table->index(['company_id', 'usage_type', 'service_type'], 'usage_tiers_service_idx');
            $table->index(['company_id', 'is_active', 'effective_date'], 'usage_tiers_active_idx');
            $table->index(['pricing_rule_id', 'tier_order'], 'usage_tiers_order_idx');
            $table->index(['tier_code', 'company_id'], 'usage_tiers_code_idx');
            $table->index(['effective_date', 'expiry_date'], 'usage_tiers_period_idx');
            $table->index(['usage_type', 'min_usage', 'max_usage'], 'usage_tiers_range_idx');

            // Unique Constraints
            $table->unique(['company_id', 'tier_code'], 'usage_tiers_code_unique');
            $table->unique(['pricing_rule_id', 'tier_order'], 'usage_tiers_rule_order_unique');
        });

        // Add table comment
        DB::statement("ALTER TABLE usage_tiers COMMENT = 'Tiered pricing structures for usage-based billing with progressive rates and time-based variations'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_tiers');
    }
}