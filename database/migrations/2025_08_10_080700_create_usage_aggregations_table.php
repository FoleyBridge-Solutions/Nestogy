<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration for Usage Aggregations Table
 * 
 * Pre-calculated usage aggregations for performance optimization in reporting,
 * billing, and analytics. Supports real-time and batch aggregation processing.
 */
class CreateUsageAggregationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_aggregations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('usage_pool_id')->nullable()->index();
            $table->unsignedBigInteger('usage_bucket_id')->nullable()->index();

            // Aggregation Configuration
            $table->string('aggregation_type', 30)->comment('usage_summary, billing_summary, performance_metrics, cost_analysis');
            $table->string('aggregation_level', 20)->comment('client, pool, bucket, service, geographic, time_based');
            $table->string('aggregation_period', 20)->comment('hourly, daily, weekly, monthly, quarterly, yearly');
            $table->date('aggregation_date')->index()->comment('Date this aggregation represents');
            $table->string('time_zone', 50)->default('UTC');

            // Usage Classification
            $table->string('usage_type', 50)->index()->comment('voice, data, sms, mms, feature, equipment, api, all');
            $table->string('service_type', 50)->nullable()->index()->comment('Specific service type or null for all');
            $table->json('service_breakdown')->nullable()->comment('Breakdown by individual services');
            $table->string('geographic_scope', 30)->nullable()->comment('local, national, international, global');

            // Usage Volume Aggregations
            $table->decimal('total_usage_volume', 20, 4)->default(0)->comment('Total usage volume for period');
            $table->decimal('billable_usage_volume', 20, 4)->default(0)->comment('Billable usage volume');
            $table->decimal('included_usage_volume', 20, 4)->default(0)->comment('Usage covered by allowances');
            $table->decimal('overage_usage_volume', 20, 4)->default(0)->comment('Usage exceeding allowances');
            $table->decimal('bonus_usage_volume', 20, 4)->default(0)->comment('Bonus or promotional usage');
            $table->string('volume_unit', 20)->comment('Unit of measurement');

            // Transaction Count Aggregations
            $table->bigInteger('total_transactions')->default(0)->comment('Total number of usage transactions');
            $table->bigInteger('successful_transactions')->default(0)->comment('Successfully completed transactions');
            $table->bigInteger('failed_transactions')->default(0)->comment('Failed transactions');
            $table->bigInteger('disputed_transactions')->default(0)->comment('Disputed transactions');
            $table->bigInteger('unique_sessions')->default(0)->comment('Unique usage sessions');

            // Financial Aggregations
            $table->decimal('total_revenue', 15, 2)->default(0)->comment('Total revenue generated');
            $table->decimal('base_charges', 15, 2)->default(0)->comment('Base service charges');
            $table->decimal('usage_charges', 15, 2)->default(0)->comment('Usage-based charges');
            $table->decimal('overage_charges', 15, 2)->default(0)->comment('Overage charges');
            $table->decimal('discount_amount', 15, 2)->default(0)->comment('Total discounts applied');
            $table->decimal('tax_amount', 15, 2)->default(0)->comment('Total taxes charged');
            $table->decimal('net_revenue', 15, 2)->default(0)->comment('Revenue after discounts and taxes');
            $table->string('currency_code', 3)->default('USD');

            // Cost and Margin Analysis
            $table->decimal('total_costs', 15, 2)->default(0)->comment('Total costs incurred');
            $table->decimal('carrier_costs', 15, 2)->default(0)->comment('Carrier and provider costs');
            $table->decimal('infrastructure_costs', 15, 2)->default(0)->comment('Infrastructure and platform costs');
            $table->decimal('processing_costs', 15, 2)->default(0)->comment('Processing and administrative costs');
            $table->decimal('gross_margin', 15, 2)->default(0)->comment('Gross margin amount');
            $table->decimal('gross_margin_percentage', 8, 3)->default(0)->comment('Gross margin percentage');

            // Usage Pattern Analysis
            $table->decimal('peak_hour_usage', 18, 4)->default(0)->comment('Peak hour usage volume');
            $table->decimal('off_peak_usage', 18, 4)->default(0)->comment('Off-peak usage volume');
            $table->decimal('weekend_usage', 18, 4)->default(0)->comment('Weekend usage volume');
            $table->decimal('average_session_duration', 10, 2)->default(0)->comment('Average session duration in minutes');
            $table->decimal('average_transaction_value', 10, 2)->default(0)->comment('Average transaction value');
            $table->integer('peak_concurrent_sessions')->default(0)->comment('Peak concurrent sessions');

            // Geographic Distribution
            $table->json('geographic_breakdown')->nullable()->comment('Usage breakdown by geography');
            $table->decimal('domestic_usage', 18, 4)->default(0)->comment('Domestic usage volume');
            $table->decimal('international_usage', 18, 4)->default(0)->comment('International usage volume');
            $table->json('top_destinations')->nullable()->comment('Top usage destinations');
            $table->json('country_distribution')->nullable()->comment('Usage distribution by country');

            // Quality and Performance Metrics
            $table->decimal('average_quality_score', 5, 2)->default(0)->comment('Average quality score');
            $table->decimal('completion_rate', 5, 2)->default(0)->comment('Transaction completion rate percentage');
            $table->decimal('error_rate', 5, 2)->default(0)->comment('Error rate percentage');
            $table->decimal('customer_satisfaction_score', 5, 2)->default(0)->comment('Customer satisfaction rating');
            $table->integer('support_tickets_generated')->default(0)->comment('Support tickets related to usage');

            // Capacity and Utilization
            $table->decimal('capacity_utilization', 5, 2)->default(0)->comment('Capacity utilization percentage');
            $table->decimal('pool_utilization', 5, 2)->default(0)->comment('Pool utilization percentage');
            $table->decimal('peak_utilization', 5, 2)->default(0)->comment('Peak utilization percentage');
            $table->integer('capacity_exceeded_periods')->default(0)->comment('Periods where capacity was exceeded');
            $table->decimal('efficiency_score', 5, 2)->default(0)->comment('Overall efficiency score');

            // Trend and Comparison Data
            $table->decimal('period_over_period_growth', 8, 3)->default(0)->comment('Growth compared to previous period');
            $table->decimal('year_over_year_growth', 8, 3)->default(0)->comment('Year-over-year growth percentage');
            $table->decimal('seasonal_index', 6, 3)->default(1.000)->comment('Seasonal adjustment index');
            $table->json('trend_indicators')->nullable()->comment('Trend analysis indicators');
            $table->json('anomaly_flags')->nullable()->comment('Detected anomalies');

            // Billing and Invoice Integration
            $table->boolean('is_billed')->default(false)->index()->comment('Whether this period has been billed');
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->date('billing_date')->nullable()->comment('Date when billing occurred');
            $table->decimal('billed_amount', 15, 2)->nullable()->comment('Amount actually billed');
            $table->json('billing_adjustments')->nullable()->comment('Any billing adjustments made');

            // Commitment and Pool Tracking
            $table->decimal('commitment_usage', 18, 4)->default(0)->comment('Usage counting toward commitments');
            $table->decimal('commitment_shortfall', 18, 4)->default(0)->comment('Shortfall from commitments');
            $table->decimal('pool_allocation_used', 18, 4)->default(0)->comment('Pool allocation consumed');
            $table->decimal('pool_overage', 18, 4)->default(0)->comment('Usage exceeding pool allocation');

            // Alert and Threshold Tracking
            $table->integer('alerts_triggered')->default(0)->comment('Number of alerts triggered');
            $table->json('threshold_breaches')->nullable()->comment('Record of threshold breaches');
            $table->timestamp('last_alert_at')->nullable()->comment('Last alert timestamp');
            $table->json('alert_summary')->nullable()->comment('Summary of alert activity');

            // Data Quality and Validation
            $table->decimal('data_completeness_score', 5, 2)->default(100)->comment('Data completeness percentage');
            $table->integer('missing_records_count')->default(0)->comment('Count of missing records');
            $table->integer('duplicate_records_count')->default(0)->comment('Count of duplicate records');
            $table->json('data_quality_issues')->nullable()->comment('Identified data quality issues');
            $table->boolean('requires_review')->default(false)->comment('Flagged for manual review');

            // Processing and Update Tracking
            $table->string('aggregation_status', 20)->default('pending')->index()->comment('pending, processing, completed, failed');
            $table->timestamp('aggregation_started_at')->nullable();
            $table->timestamp('aggregation_completed_at')->nullable();
            $table->integer('processing_duration_seconds')->nullable()->comment('Time taken to process');
            $table->text('processing_notes')->nullable();
            $table->json('processing_metadata')->nullable();

            // Data Sources and Lineage
            $table->json('source_systems')->nullable()->comment('Systems that contributed data');
            $table->bigInteger('source_record_count')->default(0)->comment('Number of source records aggregated');
            $table->timestamp('earliest_source_timestamp')->nullable()->comment('Earliest source data timestamp');
            $table->timestamp('latest_source_timestamp')->nullable()->comment('Latest source data timestamp');
            $table->string('aggregation_method', 30)->default('batch')->comment('batch, real_time, hybrid');

            // Forecast and Prediction Data
            $table->decimal('forecasted_next_period', 18, 4)->nullable()->comment('Forecast for next period');
            $table->decimal('forecast_confidence', 5, 2)->nullable()->comment('Forecast confidence percentage');
            $table->json('predictive_indicators')->nullable()->comment('Predictive analytics indicators');
            $table->decimal('demand_forecast', 18, 4)->nullable()->comment('Demand forecast');

            // External System Integration
            $table->string('external_aggregation_id', 100)->nullable()->index();
            $table->json('integration_metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status', 20)->nullable();

            // Audit and Compliance
            $table->boolean('audit_reviewed')->default(false);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('compliance_flags')->nullable()->comment('Regulatory compliance indicators');
            $table->text('audit_notes')->nullable();

            // Performance Optimization
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('calculation_notes')->nullable();
            $table->json('performance_stats')->nullable()->comment('Aggregation performance statistics');

            $table->timestamps();
            $table->index(['created_at', 'aggregation_status']); // For cleanup operations

            // Foreign Key Constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('usage_pool_id')->references('id')->on('usage_pools')->onDelete('set null');
            $table->foreign('usage_bucket_id')->references('id')->on('usage_buckets')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');

            // High-Performance Indexes for Analytics
            $table->index(['company_id', 'aggregation_type', 'aggregation_period', 'aggregation_date'], 'usage_agg_company_type_period_idx');
            $table->index(['client_id', 'usage_type', 'aggregation_date'], 'usage_agg_client_usage_date_idx');
            $table->index(['aggregation_date', 'aggregation_period', 'usage_type'], 'usage_agg_date_period_usage_idx');
            $table->index(['company_id', 'is_billed', 'aggregation_date'], 'usage_agg_billing_status_idx');
            $table->index(['aggregation_status', 'aggregation_started_at'], 'usage_agg_processing_status_idx');
            $table->index(['service_type', 'geographic_scope', 'aggregation_date'], 'usage_agg_service_geo_idx');
            $table->index(['total_revenue', 'aggregation_date', 'company_id'], 'usage_agg_revenue_idx');
            
            // Composite indexes for common query patterns
            $table->index(['company_id', 'client_id', 'aggregation_period', 'aggregation_date'], 'usage_agg_client_period_date_idx');
            $table->index(['usage_type', 'service_type', 'aggregation_date', 'company_id'], 'usage_agg_type_service_date_idx');

            // Unique Constraints to Prevent Duplicate Aggregations
            $table->unique([
                'company_id', 'client_id', 'aggregation_type', 'aggregation_level', 
                'aggregation_period', 'aggregation_date', 'usage_type', 'service_type'
            ], 'usage_agg_unique_idx');
        });

        // Add table comment
        DB::statement("ALTER TABLE usage_aggregations COMMENT = 'Pre-calculated usage aggregations for performance optimization in reporting and analytics'");

        // Create partitioning for better performance with large datasets
        // This would typically be done based on aggregation_date
        DB::statement("
            ALTER TABLE usage_aggregations 
            PARTITION BY RANGE (YEAR(aggregation_date)) (
                PARTITION p_historical VALUES LESS THAN (YEAR(CURDATE()) - 1),
                PARTITION p_current VALUES LESS THAN (YEAR(CURDATE()) + 1),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_aggregations');
    }
}