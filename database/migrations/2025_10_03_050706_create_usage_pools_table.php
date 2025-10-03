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
        Schema::create('usage_pools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('parent_pool_id')->nullable();
            $table->string('pool_name');
            $table->string('pool_code')->nullable();
            $table->string('pool_type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('usage_type')->nullable();
            $table->string('service_types')->nullable();
            $table->string('included_services')->nullable();
            $table->string('excluded_services')->nullable();
            $table->decimal('total_capacity', 15, 2)->default(0);
            $table->string('allocated_capacity')->nullable();
            $table->string('used_capacity')->nullable();
            $table->string('capacity_unit')->nullable();
            $table->string('warning_threshold')->nullable();
            $table->string('critical_threshold')->nullable();
            $table->string('allocation_method')->nullable();
            $table->string('allocation_weights')->nullable();
            $table->string('priority_rules')->nullable();
            $table->string('allow_overallocation')->nullable();
            $table->string('overallocation_limit')->nullable();
            $table->string('pool_members')->nullable();
            $table->string('member_allocations')->nullable();
            $table->string('member_priorities')->nullable();
            $table->string('max_members')->nullable();
            $table->string('current_period_usage')->nullable();
            $table->string('previous_period_usage')->nullable();
            $table->string('lifetime_usage')->nullable();
            $table->timestamp('last_usage_update')->nullable();
            $table->string('usage_history')->nullable();
            $table->string('allows_rollover')->nullable();
            $table->string('rollover_percentage')->nullable();
            $table->string('rollover_months')->nullable();
            $table->string('rollover_capacity')->nullable();
            $table->timestamp('rollover_expires_at')->nullable();
            $table->string('billing_model')->nullable();
            $table->string('pool_cost_per_unit')->nullable();
            $table->string('overage_rate')->nullable();
            $table->string('cost_allocation_method')->nullable();
            $table->string('billing_preferences')->nullable();
            $table->string('has_time_restrictions')->nullable();
            $table->string('time_restrictions')->nullable();
            $table->string('peak_hour_rules')->nullable();
            $table->string('weekend_restrictions')->nullable();
            $table->string('has_geographic_restrictions')->nullable();
            $table->string('geographic_rules')->nullable();
            $table->string('allowed_locations')->nullable();
            $table->string('restricted_destinations')->nullable();
            $table->boolean('auto_refill_enabled')->default(false);
            $table->string('auto_refill_threshold')->nullable();
            $table->decimal('auto_refill_amount', 15, 2)->default(0);
            $table->string('auto_refill_frequency')->nullable();
            $table->string('auto_suspend_on_depletion')->nullable();
            $table->string('alert_settings')->nullable();
            $table->string('notification_recipients')->nullable();
            $table->string('email_alerts_enabled')->nullable();
            $table->boolean('sms_alerts_enabled')->default(false);
            $table->string('last_alert_sent')->nullable();
            $table->string('pool_status')->default('active');
            $table->string('status_reason')->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('billing_cycle')->nullable();
            $table->timestamp('cycle_start_date')->nullable();
            $table->timestamp('cycle_end_date')->nullable();
            $table->timestamp('next_reset_date')->nullable();
            $table->boolean('auto_reset_enabled')->default(false);
            $table->string('contract_reference')->nullable();
            $table->string('contract_terms')->nullable();
            $table->string('committed_spend')->nullable();
            $table->string('discount_rate')->nullable();
            $table->string('reporting_tags')->nullable();
            $table->boolean('detailed_reporting_enabled')->default(false);
            $table->string('kpi_metrics')->nullable();
            $table->string('benchmark_data')->nullable();
            $table->unsignedBigInteger('external_pool_id')->nullable();
            $table->string('integration_settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status')->default('active');
            $table->string('created_by')->nullable();
            $table->timestamp('updated_by')->nullable();
            $table->string('change_reason')->nullable();
            $table->string('audit_log')->nullable();
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
        Schema::dropIfExists('usage_pools');
    }
};
