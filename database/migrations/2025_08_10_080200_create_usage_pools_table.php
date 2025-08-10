<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration for Usage Pools Table
 * 
 * Manages shared usage allowances and limits across multiple clients, services, or locations.
 * Supports complex pooling scenarios for enterprise VoIP deployments.
 */
class CreateUsagePoolsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_pools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('parent_pool_id')->nullable()->index()->comment('For hierarchical pools');

            // Pool Configuration
            $table->string('pool_name', 100)->comment('Descriptive name for the pool');
            $table->string('pool_code', 50)->index()->comment('Unique identifier code');
            $table->string('pool_type', 30)->comment('shared, client_specific, location_based, service_based');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();

            // Usage Type and Service Classification
            $table->string('usage_type', 50)->index()->comment('voice, data, sms, mms, feature, equipment, api');
            $table->json('service_types')->nullable()->comment('Array of applicable service types');
            $table->json('included_services')->nullable()->comment('Specific services included in pool');
            $table->json('excluded_services')->nullable()->comment('Services excluded from pool');

            // Pool Capacity and Limits
            $table->decimal('total_capacity', 18, 4)->comment('Total pool capacity');
            $table->decimal('allocated_capacity', 18, 4)->default(0)->comment('Currently allocated capacity');
            $table->decimal('used_capacity', 18, 4)->default(0)->comment('Currently used capacity');
            $table->string('capacity_unit', 20)->comment('minute, mb, gb, message, call, line');
            $table->decimal('warning_threshold', 5, 2)->default(80)->comment('Warning threshold percentage');
            $table->decimal('critical_threshold', 5, 2)->default(95)->comment('Critical threshold percentage');

            // Allocation and Distribution Rules
            $table->string('allocation_method', 30)->comment('equal_share, weighted, priority_based, first_come_first_served');
            $table->json('allocation_weights')->nullable()->comment('Weights for allocation distribution');
            $table->json('priority_rules')->nullable()->comment('Priority-based allocation rules');
            $table->boolean('allow_overallocation')->default(false)->comment('Allow allocation beyond capacity');
            $table->decimal('overallocation_limit', 5, 2)->default(0)->comment('Maximum overallocation percentage');

            // Pool Members and Participants
            $table->json('pool_members')->nullable()->comment('Clients/services participating in pool');
            $table->json('member_allocations')->nullable()->comment('Individual member allocation limits');
            $table->json('member_priorities')->nullable()->comment('Member priority levels');
            $table->integer('max_members')->nullable()->comment('Maximum number of pool members');

            // Usage Tracking and Monitoring
            $table->decimal('current_period_usage', 18, 4)->default(0);
            $table->decimal('previous_period_usage', 18, 4)->default(0);
            $table->decimal('lifetime_usage', 18, 4)->default(0);
            $table->timestamp('last_usage_update')->nullable();
            $table->json('usage_history')->nullable()->comment('Historical usage data');

            // Rollover and Carryover Rules
            $table->boolean('allows_rollover')->default(false);
            $table->decimal('rollover_percentage', 5, 2)->default(0)->comment('Percentage that rolls over');
            $table->integer('rollover_months')->default(0)->comment('Months rollover is valid');
            $table->decimal('rollover_capacity', 18, 4)->default(0)->comment('Current rollover capacity');
            $table->timestamp('rollover_expires_at')->nullable();

            // Billing and Cost Management
            $table->string('billing_model', 30)->comment('shared_cost, individual_billing, hybrid');
            $table->decimal('pool_cost_per_unit', 10, 6)->nullable();
            $table->decimal('overage_rate', 10, 6)->nullable();
            $table->string('cost_allocation_method', 30)->comment('usage_based, equal_share, weighted');
            $table->json('billing_preferences')->nullable();

            // Time-based Rules and Restrictions
            $table->boolean('has_time_restrictions')->default(false);
            $table->json('time_restrictions')->nullable()->comment('Time-based usage restrictions');
            $table->json('peak_hour_rules')->nullable()->comment('Peak hour specific rules');
            $table->boolean('weekend_restrictions')->default(false);

            // Geographic and Location Rules
            $table->boolean('has_geographic_restrictions')->default(false);
            $table->json('geographic_rules')->nullable()->comment('Location-based restrictions');
            $table->json('allowed_locations')->nullable()->comment('Permitted usage locations');
            $table->json('restricted_destinations')->nullable()->comment('Restricted destination rules');

            // Automation and Management
            $table->boolean('auto_refill_enabled')->default(false);
            $table->decimal('auto_refill_threshold', 5, 2)->default(0);
            $table->decimal('auto_refill_amount', 18, 4)->default(0);
            $table->string('auto_refill_frequency', 20)->nullable();
            $table->boolean('auto_suspend_on_depletion')->default(false);

            // Alerting and Notifications
            $table->json('alert_settings')->nullable()->comment('Alert configuration');
            $table->json('notification_recipients')->nullable()->comment('Who gets notifications');
            $table->boolean('email_alerts_enabled')->default(true);
            $table->boolean('sms_alerts_enabled')->default(false);
            $table->timestamp('last_alert_sent')->nullable();

            // Pool Status and Lifecycle
            $table->string('pool_status', 20)->default('active')->index()->comment('active, suspended, depleted, expired');
            $table->text('status_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Billing Cycle Integration
            $table->string('billing_cycle', 20)->default('monthly');
            $table->date('cycle_start_date')->nullable();
            $table->date('cycle_end_date')->nullable();
            $table->date('next_reset_date')->nullable();
            $table->boolean('auto_reset_enabled')->default(true);

            // Contract and Agreement References
            $table->string('contract_reference', 100)->nullable();
            $table->json('contract_terms')->nullable()->comment('Specific contract terms');
            $table->decimal('committed_spend', 12, 2)->nullable()->comment('Minimum committed spend');
            $table->decimal('discount_rate', 5, 3)->default(0)->comment('Pool-specific discount rate');

            // Reporting and Analytics
            $table->json('reporting_tags')->nullable()->comment('Tags for reporting and analytics');
            $table->boolean('detailed_reporting_enabled')->default(true);
            $table->json('kpi_metrics')->nullable()->comment('Key performance indicators');
            $table->json('benchmark_data')->nullable()->comment('Performance benchmarks');

            // External System Integration
            $table->string('external_pool_id', 100)->nullable()->index();
            $table->json('integration_settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('sync_status')->nullable();

            // Audit and Compliance
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('change_reason')->nullable();
            $table->json('audit_log')->nullable()->comment('Pool modification history');

            $table->timestamps();
            $table->softDeletes();

            // Foreign Key Constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('parent_pool_id')->references('id')->on('usage_pools')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Performance Indexes
            $table->index(['company_id', 'pool_type', 'is_active'], 'usage_pools_type_active_idx');
            $table->index(['client_id', 'usage_type', 'is_active'], 'usage_pools_client_usage_idx');
            $table->index(['pool_status', 'expires_at'], 'usage_pools_status_expiry_idx');
            $table->index(['warning_threshold', 'used_capacity'], 'usage_pools_threshold_idx');
            $table->index(['billing_cycle', 'cycle_start_date', 'cycle_end_date'], 'usage_pools_billing_cycle_idx');
            $table->index(['next_reset_date', 'auto_reset_enabled'], 'usage_pools_reset_idx');

            // Unique Constraints
            $table->unique(['company_id', 'pool_code'], 'usage_pools_code_unique');
        });

        // Add table comment
        DB::statement("ALTER TABLE usage_pools COMMENT = 'Shared usage allowances and limits for enterprise VoIP deployments with complex pooling scenarios'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_pools');
    }
}