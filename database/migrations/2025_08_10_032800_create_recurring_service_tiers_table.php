<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Recurring Service Tiers Table
 * 
 * Defines tiered pricing structures for VoIP services with usage allowances,
 * rates, and thresholds for sophisticated billing calculations.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recurring_service_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('recurring_id');
            
            // Tier definition
            $table->string('service_type', 50); // hosted_pbx, sip_trunking, etc.
            $table->string('tier_name', 100); // "Basic", "Professional", "Enterprise"
            $table->integer('tier_level')->default(1); // 1, 2, 3 for ordering
            $table->text('description')->nullable();
            
            // Usage allowances
            $table->decimal('monthly_allowance', 15, 4)->default(0); // Minutes, MB, calls
            $table->string('allowance_unit', 20)->default('minutes'); // minutes, mb, gb, calls
            $table->decimal('base_rate', 8, 4)->default(0); // Base monthly rate
            
            // Overage pricing
            $table->decimal('overage_rate', 8, 4)->default(0); // Rate per unit over allowance
            $table->decimal('overage_minimum', 8, 4)->default(0); // Minimum overage charge
            $table->decimal('overage_maximum', 10, 2)->nullable(); // Maximum overage cap
            
            // Tier thresholds
            $table->decimal('min_usage', 15, 4)->default(0); // Minimum usage for this tier
            $table->decimal('max_usage', 15, 4)->nullable(); // Maximum usage (null = unlimited)
            
            // Volume discounts
            $table->decimal('volume_discount_threshold', 15, 4)->nullable();
            $table->decimal('volume_discount_percentage', 5, 2)->default(0);
            
            // Pricing configuration
            $table->enum('pricing_model', ['flat', 'tiered', 'volume', 'usage_based'])
                  ->default('tiered');
            $table->json('pricing_config')->nullable(); // Additional pricing parameters
            
            // Billing configuration
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('priority')->default(0); // For ordering multiple tiers
            
            // Features and restrictions
            $table->json('included_features')->nullable(); // Features included in tier
            $table->json('restrictions')->nullable(); // Usage or feature restrictions
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('recurring_id')->references('id')->on('recurring')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['recurring_id', 'service_type'], 'tiers_recurring_service_idx');
            $table->index(['service_type', 'tier_level'], 'tiers_service_level_idx');
            $table->index(['is_active', 'effective_date'], 'tiers_active_date_idx');
            $table->index('priority', 'tiers_priority_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_service_tiers');
    }
};