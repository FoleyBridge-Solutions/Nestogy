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
        Schema::create('usage_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('pricing_rule_id')->nullable();
            $table->string('tier_name');
            $table->string('tier_code')->nullable();
            $table->text('description')->nullable();
            $table->string('tier_order')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('usage_type')->nullable();
            $table->string('service_type')->nullable();
            $table->string('applicable_services')->nullable();
            $table->string('min_usage')->nullable();
            $table->string('max_usage')->nullable();
            $table->string('usage_unit')->nullable();
            $table->boolean('is_unlimited_tier')->default(false);
            $table->string('pricing_model')->nullable();
            $table->string('base_rate')->nullable();
            $table->string('per_unit_rate')->nullable();
            $table->string('block_size')->nullable();
            $table->string('block_rate')->nullable();
            $table->string('setup_fee')->nullable();
            $table->string('has_peak_pricing')->nullable();
            $table->string('peak_rate_multiplier')->nullable();
            $table->string('off_peak_rate_multiplier')->nullable();
            $table->string('weekend_rate_multiplier')->nullable();
            $table->string('peak_hours')->nullable();
            $table->string('time_zone_rules')->nullable();
            $table->string('has_geographic_pricing')->nullable();
            $table->string('geographic_rates')->nullable();
            $table->string('destination_rates')->nullable();
            $table->string('has_volume_discounts')->nullable();
            $table->string('volume_discount_rules')->nullable();
            $table->string('commitment_discount')->nullable();
            $table->string('loyalty_discount')->nullable();
            $table->string('overage_handling')->nullable();
            $table->string('overage_rate')->nullable();
            $table->string('allows_rollover')->nullable();
            $table->string('rollover_months')->nullable();
            $table->string('rollover_percentage')->nullable();
            $table->string('billing_frequency')->nullable();
            $table->boolean('is_prorated')->default(false);
            $table->string('proration_method')->nullable();
            $table->string('requires_advance_payment')->nullable();
            $table->string('advance_payment_days')->nullable();
            $table->string('tier_conditions')->nullable();
            $table->string('bundling_rules')->nullable();
            $table->string('exclusion_rules')->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->string('tax_category_mapping')->nullable();
            $table->string('regulatory_compliance')->nullable();
            $table->timestamp('effective_date')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->boolean('is_promotional')->default(false);
            $table->string('promotion_code')->nullable();
            $table->string('reporting_categories')->nullable();
            $table->string('track_detailed_usage')->nullable();
            $table->string('kpi_targets')->nullable();
            $table->unsignedBigInteger('external_tier_id')->nullable();
            $table->string('billing_system_code')->nullable();
            $table->string('integration_metadata')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_by')->nullable();
            $table->string('change_reason')->nullable();
            $table->string('tier_history')->nullable();
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
        Schema::dropIfExists('usage_tiers');
    }
};
