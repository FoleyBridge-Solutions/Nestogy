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
        Schema::create('usage_buckets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('usage_pool_id')->nullable();
            $table->unsignedBigInteger('parent_bucket_id')->nullable();
            $table->string('bucket_name');
            $table->string('bucket_code')->nullable();
            $table->string('bucket_type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('usage_type')->nullable();
            $table->string('service_types')->nullable();
            $table->string('included_categories')->nullable();
            $table->string('excluded_categories')->nullable();
            $table->string('bucket_capacity')->nullable();
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('used_amount', 15, 2)->default(0);
            $table->decimal('reserved_amount', 15, 2)->default(0);
            $table->string('capacity_unit')->nullable();
            $table->string('usage_priority')->nullable();
            $table->string('billing_priority')->nullable();
            $table->boolean('is_primary_bucket')->default(false);
            $table->string('allocation_order')->nullable();
            $table->string('current_period_usage')->nullable();
            $table->string('daily_usage')->nullable();
            $table->string('weekly_usage')->nullable();
            $table->string('monthly_usage')->nullable();
            $table->string('lifetime_usage')->nullable();
            $table->timestamp('last_usage_at')->nullable();
            $table->timestamp('first_usage_at')->nullable();
            $table->string('daily_limit')->nullable();
            $table->string('weekly_limit')->nullable();
            $table->string('monthly_limit')->nullable();
            $table->string('warning_threshold')->nullable();
            $table->string('critical_threshold')->nullable();
            $table->string('allows_overflow')->nullable();
            $table->unsignedBigInteger('overflow_bucket_id')->nullable();
            $table->string('overflow_behavior')->nullable();
            $table->string('overflow_rate')->nullable();
            $table->string('overflow_rules')->nullable();
            $table->string('has_time_restrictions')->nullable();
            $table->string('allowed_time_periods')->nullable();
            $table->string('blackout_periods')->nullable();
            $table->string('peak_hour_only')->nullable();
            $table->string('off_peak_only')->nullable();
            $table->string('has_location_restrictions')->nullable();
            $table->string('allowed_locations')->nullable();
            $table->string('restricted_destinations')->nullable();
            $table->string('roaming_behavior')->nullable();
            $table->string('allows_rollover')->nullable();
            $table->string('rollover_percentage')->nullable();
            $table->string('rollover_months')->nullable();
            $table->string('rollover_balance')->nullable();
            $table->timestamp('rollover_expires_at')->nullable();
            $table->timestamp('bucket_expires_at')->nullable();
            $table->string('included_rate')->nullable();
            $table->string('overage_rate')->nullable();
            $table->boolean('is_billable')->default(false);
            $table->boolean('is_taxable')->default(false);
            $table->string('billing_frequency')->nullable();
            $table->string('pricing_rules')->nullable();
            $table->string('bucket_status')->default('active');
            $table->string('status_reason')->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('depleted_at')->nullable();
            $table->string('reset_frequency')->nullable();
            $table->timestamp('last_reset_date')->nullable();
            $table->timestamp('next_reset_date')->nullable();
            $table->boolean('auto_reset_enabled')->default(false);
            $table->string('reset_rules')->nullable();
            $table->boolean('is_shared_bucket')->default(false);
            $table->string('sharing_rules')->nullable();
            $table->string('distribution_weights')->nullable();
            $table->string('sharing_percentage')->nullable();
            $table->boolean('is_promotional')->default(false);
            $table->string('promotion_code')->nullable();
            $table->timestamp('promotion_expires_at')->nullable();
            $table->string('promotion_rules')->nullable();
            $table->boolean('is_bonus_bucket')->default(false);
            $table->string('bonus_type')->nullable();
            $table->string('quality_restrictions')->nullable();
            $table->string('service_restrictions')->nullable();
            $table->string('emergency_services_only')->nullable();
            $table->string('feature_restrictions')->nullable();
            $table->string('usage_analytics')->nullable();
            $table->string('average_daily_usage')->nullable();
            $table->string('peak_usage_rate')->nullable();
            $table->string('usage_trends')->nullable();
            $table->string('efficiency_metrics')->nullable();
            $table->unsignedBigInteger('external_bucket_id')->nullable();
            $table->string('integration_metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status')->default('active');
            $table->string('alert_preferences')->nullable();
            $table->string('email_alerts_enabled')->nullable();
            $table->boolean('sms_alerts_enabled')->default(false);
            $table->string('notification_recipients')->nullable();
            $table->string('last_alert_sent')->nullable();
            $table->string('compliance_rules')->nullable();
            $table->string('requires_audit_trail')->nullable();
            $table->string('audit_settings')->nullable();
            $table->text('compliance_notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_by')->nullable();
            $table->string('change_reason')->nullable();
            $table->string('configuration_history')->nullable();
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
        Schema::dropIfExists('usage_buckets');
    }
};
