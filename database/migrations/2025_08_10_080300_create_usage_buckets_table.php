<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration for Usage Buckets Table
 * 
 * Manages usage categorization and allocation for complex billing scenarios.
 * Supports bucket-based usage allocation with overflows and priority handling.
 */
class CreateUsageBucketsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_buckets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('usage_pool_id')->nullable()->index();
            $table->unsignedBigInteger('parent_bucket_id')->nullable()->index()->comment('For nested bucket hierarchies');

            // Bucket Configuration
            $table->string('bucket_name', 100)->comment('Display name for the bucket');
            $table->string('bucket_code', 50)->index()->comment('Unique bucket identifier');
            $table->string('bucket_type', 30)->comment('included, bonus, promotional, overage, rollover');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();

            // Usage Classification
            $table->string('usage_type', 50)->index()->comment('voice, data, sms, mms, feature, equipment, api');
            $table->json('service_types')->nullable()->comment('Applicable service types');
            $table->json('included_categories')->nullable()->comment('Usage categories included');
            $table->json('excluded_categories')->nullable()->comment('Usage categories excluded');

            // Bucket Capacity and Allocation
            $table->decimal('bucket_capacity', 18, 4)->comment('Total bucket capacity');
            $table->decimal('allocated_amount', 18, 4)->default(0)->comment('Amount allocated to bucket');
            $table->decimal('used_amount', 18, 4)->default(0)->comment('Currently used amount');
            $table->decimal('reserved_amount', 18, 4)->default(0)->comment('Reserved but not yet used');
            $table->string('capacity_unit', 20)->comment('minute, mb, gb, message, call, line');

            // Bucket Priority and Ordering
            $table->integer('usage_priority')->default(1)->comment('Priority for usage allocation (1=highest)');
            $table->integer('billing_priority')->default(1)->comment('Priority for billing order');
            $table->boolean('is_primary_bucket')->default(false)->comment('Primary bucket for this usage type');
            $table->string('allocation_order', 20)->default('fifo')->comment('fifo, lifo, priority, weighted');

            // Usage Tracking and Metrics
            $table->decimal('current_period_usage', 18, 4)->default(0);
            $table->decimal('daily_usage', 18, 4)->default(0);
            $table->decimal('weekly_usage', 18, 4)->default(0);
            $table->decimal('monthly_usage', 18, 4)->default(0);
            $table->decimal('lifetime_usage', 18, 4)->default(0);
            $table->timestamp('last_usage_at')->nullable();
            $table->timestamp('first_usage_at')->nullable();

            // Bucket Limits and Thresholds
            $table->decimal('daily_limit', 18, 4)->nullable()->comment('Maximum daily usage');
            $table->decimal('weekly_limit', 18, 4)->nullable()->comment('Maximum weekly usage');
            $table->decimal('monthly_limit', 18, 4)->nullable()->comment('Maximum monthly usage');
            $table->decimal('warning_threshold', 5, 2)->default(80)->comment('Warning threshold percentage');
            $table->decimal('critical_threshold', 5, 2)->default(95)->comment('Critical threshold percentage');

            // Overflow and Spillover Rules
            $table->boolean('allows_overflow')->default(true)->comment('Allow usage to overflow to next bucket');
            $table->unsignedBigInteger('overflow_bucket_id')->nullable()->index()->comment('Target bucket for overflow');
            $table->string('overflow_behavior', 30)->default('spillover')->comment('spillover, block, charge_overage');
            $table->decimal('overflow_rate', 10, 6)->nullable()->comment('Rate for overflow usage');
            $table->json('overflow_rules')->nullable()->comment('Complex overflow conditions');

            // Time-based Rules and Restrictions
            $table->boolean('has_time_restrictions')->default(false);
            $table->json('allowed_time_periods')->nullable()->comment('When bucket can be used');
            $table->json('blackout_periods')->nullable()->comment('When bucket cannot be used');
            $table->boolean('peak_hour_only')->default(false);
            $table->boolean('off_peak_only')->default(false);

            // Geographic and Location Rules
            $table->boolean('has_location_restrictions')->default(false);
            $table->json('allowed_locations')->nullable()->comment('Geographic restrictions');
            $table->json('restricted_destinations')->nullable()->comment('Destination restrictions');
            $table->string('roaming_behavior', 20)->default('allowed')->comment('allowed, blocked, charged');

            // Rollover and Expiration
            $table->boolean('allows_rollover')->default(false);
            $table->decimal('rollover_percentage', 5, 2)->default(0)->comment('Percentage that rolls over');
            $table->integer('rollover_months')->default(0)->comment('Months rollover is valid');
            $table->decimal('rollover_balance', 18, 4)->default(0)->comment('Current rollover balance');
            $table->timestamp('rollover_expires_at')->nullable();
            $table->timestamp('bucket_expires_at')->nullable();

            // Pricing and Billing Configuration
            $table->decimal('included_rate', 10, 6)->default(0)->comment('Rate for included usage');
            $table->decimal('overage_rate', 10, 6)->nullable()->comment('Rate for overage usage');
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_taxable')->default(true);
            $table->string('billing_frequency', 20)->default('monthly');
            $table->json('pricing_rules')->nullable()->comment('Complex pricing configurations');

            // Bucket Status and Lifecycle
            $table->string('bucket_status', 20)->default('active')->index()->comment('active, suspended, depleted, expired');
            $table->text('status_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('depleted_at')->nullable();

            // Reset and Renewal Configuration
            $table->string('reset_frequency', 20)->default('monthly')->comment('daily, weekly, monthly, billing_cycle');
            $table->date('last_reset_date')->nullable();
            $table->date('next_reset_date')->nullable();
            $table->boolean('auto_reset_enabled')->default(true);
            $table->json('reset_rules')->nullable()->comment('Custom reset configuration');

            // Bucket Sharing and Distribution
            $table->boolean('is_shared_bucket')->default(false);
            $table->json('sharing_rules')->nullable()->comment('How bucket is shared');
            $table->json('distribution_weights')->nullable()->comment('Distribution weights for sharing');
            $table->decimal('sharing_percentage', 5, 2)->default(100)->comment('Percentage available for sharing');

            // Promotional and Bonus Configuration
            $table->boolean('is_promotional')->default(false)->index();
            $table->string('promotion_code', 50)->nullable();
            $table->timestamp('promotion_expires_at')->nullable();
            $table->json('promotion_rules')->nullable()->comment('Promotional terms and conditions');
            $table->boolean('is_bonus_bucket')->default(false);
            $table->string('bonus_type', 30)->nullable()->comment('signup, loyalty, referral, upgrade');

            // Usage Quality and Restrictions
            $table->json('quality_restrictions')->nullable()->comment('Quality-based usage restrictions');
            $table->json('service_restrictions')->nullable()->comment('Service-specific restrictions');
            $table->boolean('emergency_services_only')->default(false);
            $table->json('feature_restrictions')->nullable()->comment('Feature usage restrictions');

            // Analytics and Reporting
            $table->json('usage_analytics')->nullable()->comment('Usage pattern analytics');
            $table->decimal('average_daily_usage', 12, 4)->default(0);
            $table->decimal('peak_usage_rate', 12, 4)->default(0);
            $table->json('usage_trends')->nullable()->comment('Usage trend data');
            $table->json('efficiency_metrics')->nullable()->comment('Bucket efficiency metrics');

            // Integration and External Systems
            $table->string('external_bucket_id', 100)->nullable()->index();
            $table->json('integration_metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status', 20)->nullable();

            // Notifications and Alerting
            $table->json('alert_preferences')->nullable()->comment('Alert configuration for bucket');
            $table->boolean('email_alerts_enabled')->default(true);
            $table->boolean('sms_alerts_enabled')->default(false);
            $table->json('notification_recipients')->nullable();
            $table->timestamp('last_alert_sent')->nullable();

            // Compliance and Audit
            $table->json('compliance_rules')->nullable()->comment('Regulatory compliance requirements');
            $table->boolean('requires_audit_trail')->default(false);
            $table->json('audit_settings')->nullable()->comment('Audit configuration');
            $table->text('compliance_notes')->nullable();

            // Bucket History and Versioning
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('change_reason')->nullable();
            $table->json('configuration_history')->nullable()->comment('Historical configuration changes');

            $table->timestamps();
            $table->softDeletes();

            // Foreign Key Constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('usage_pool_id')->references('id')->on('usage_pools')->onDelete('set null');
            $table->foreign('parent_bucket_id')->references('id')->on('usage_buckets')->onDelete('set null');
            $table->foreign('overflow_bucket_id')->references('id')->on('usage_buckets')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Performance Indexes
            $table->index(['company_id', 'client_id', 'bucket_type'], 'usage_buckets_client_type_idx');
            $table->index(['usage_type', 'bucket_status', 'is_active'], 'usage_buckets_type_status_idx');
            $table->index(['usage_priority', 'billing_priority'], 'usage_buckets_priority_idx');
            $table->index(['bucket_expires_at', 'bucket_status'], 'usage_buckets_expiry_idx');
            $table->index(['next_reset_date', 'reset_frequency'], 'usage_buckets_reset_idx');
            $table->index(['warning_threshold', 'used_amount', 'bucket_capacity'], 'usage_buckets_threshold_idx');
            $table->index(['last_usage_at', 'bucket_status'], 'usage_buckets_usage_date_idx');

            // Unique Constraints
            $table->unique(['company_id', 'client_id', 'bucket_code'], 'usage_buckets_code_unique');
        });

        // Add table comment
        DB::statement("ALTER TABLE usage_buckets COMMENT = 'Usage categorization and allocation buckets for complex VoIP billing scenarios with priority handling'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_buckets');
    }
}