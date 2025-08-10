<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration for Usage Commitments Table
 * 
 * Manages minimum usage agreements and commitment tracking for enterprise clients
 * with penalty calculations and commitment fulfillment monitoring.
 */
class CreateUsageCommitmentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_commitments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('contract_id')->nullable()->index();
            $table->unsignedBigInteger('pricing_rule_id')->nullable()->index();

            // Commitment Configuration
            $table->string('commitment_name', 100)->comment('Descriptive name for the commitment');
            $table->string('commitment_code', 50)->index()->comment('Unique commitment identifier');
            $table->string('commitment_type', 30)->comment('usage_volume, spend_amount, hybrid, service_level');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();

            // Usage and Service Classification
            $table->string('usage_type', 50)->index()->comment('voice, data, sms, mms, feature, equipment, api');
            $table->json('service_types')->nullable()->comment('Specific services included in commitment');
            $table->json('included_categories')->nullable()->comment('Usage categories included');
            $table->json('excluded_categories')->nullable()->comment('Usage categories excluded');

            // Commitment Amounts and Targets
            $table->decimal('committed_usage_amount', 18, 4)->nullable()->comment('Committed usage quantity');
            $table->decimal('committed_spend_amount', 15, 2)->nullable()->comment('Committed spend amount');
            $table->string('commitment_unit', 20)->comment('minute, mb, gb, dollars, calls, lines');
            $table->string('commitment_period', 20)->comment('monthly, quarterly, annually, contract_term');
            $table->integer('commitment_term_months')->comment('Term length in months');

            // Current Status and Progress
            $table->decimal('current_period_usage', 18, 4)->default(0)->comment('Usage in current commitment period');
            $table->decimal('current_period_spend', 15, 2)->default(0)->comment('Spend in current commitment period');
            $table->decimal('lifetime_usage', 18, 4)->default(0)->comment('Total usage since commitment start');
            $table->decimal('lifetime_spend', 15, 2)->default(0)->comment('Total spend since commitment start');
            $table->decimal('fulfillment_percentage', 5, 2)->default(0)->comment('Current fulfillment percentage');

            // Penalty and Shortfall Configuration
            $table->boolean('has_shortfall_penalties')->default(true);
            $table->string('penalty_calculation_method', 30)->default('difference')->comment('difference, percentage, tiered');
            $table->decimal('penalty_rate', 10, 6)->nullable()->comment('Penalty rate for shortfall');
            $table->decimal('minimum_penalty_amount', 10, 2)->default(0)->comment('Minimum penalty amount');
            $table->decimal('maximum_penalty_amount', 12, 2)->nullable()->comment('Maximum penalty cap');
            $table->boolean('waive_penalties_for_overage')->default(false)->comment('Waive penalties if client exceeds in other areas');

            // Bonus and Incentive Configuration
            $table->boolean('has_overachievement_bonuses')->default(false);
            $table->decimal('bonus_threshold_percentage', 5, 2)->default(110)->comment('Threshold for bonus eligibility');
            $table->decimal('bonus_rate', 5, 3)->default(0)->comment('Bonus rate for overachievement');
            $table->decimal('maximum_bonus_amount', 12, 2)->nullable()->comment('Maximum bonus cap');
            $table->json('bonus_tiers')->nullable()->comment('Tiered bonus structure');

            // Commitment Period Tracking
            $table->date('commitment_start_date')->index();
            $table->date('commitment_end_date')->index();
            $table->date('current_period_start_date');
            $table->date('current_period_end_date');
            $table->date('next_evaluation_date');
            $table->boolean('auto_renew')->default(false);
            $table->integer('auto_renew_months')->nullable();

            // Proration and Adjustment Rules
            $table->boolean('allow_proration')->default(true);
            $table->string('proration_method', 20)->default('daily')->comment('daily, weekly, monthly');
            $table->boolean('adjust_for_service_changes')->default(true);
            $table->json('adjustment_rules')->nullable()->comment('Rules for commitment adjustments');

            // Pooling and Sharing Configuration
            $table->boolean('allows_pooling')->default(false)->comment('Allow pooling across services/locations');
            $table->json('pooling_rules')->nullable()->comment('Pooling configuration');
            $table->json('sharing_allocations')->nullable()->comment('How commitment is shared');
            $table->boolean('cross_service_fulfillment')->default(false);

            // Commitment History and Tracking
            $table->json('historical_periods')->nullable()->comment('Historical commitment period data');
            $table->integer('periods_met')->default(0)->comment('Number of periods commitment was met');
            $table->integer('periods_missed')->default(0)->comment('Number of periods commitment was missed');
            $table->decimal('total_penalties_incurred', 15, 2)->default(0);
            $table->decimal('total_bonuses_earned', 15, 2)->default(0);

            // Financial Impact Tracking
            $table->decimal('discount_percentage', 5, 3)->default(0)->comment('Discount earned through commitment');
            $table->decimal('effective_rate', 10, 6)->nullable()->comment('Effective rate with commitment discount');
            $table->decimal('savings_generated', 15, 2)->default(0)->comment('Total savings from commitment pricing');
            $table->decimal('revenue_impact', 15, 2)->default(0)->comment('Revenue impact (positive or negative)');

            // Forecasting and Prediction
            $table->boolean('enable_usage_forecasting')->default(true);
            $table->decimal('forecasted_period_usage', 18, 4)->nullable()->comment('Predicted usage for current period');
            $table->decimal('forecasted_shortfall', 18, 4)->nullable()->comment('Predicted shortfall');
            $table->decimal('forecast_confidence', 5, 2)->nullable()->comment('Forecast confidence percentage');
            $table->json('forecast_model_params')->nullable();

            // Alerts and Notifications
            $table->boolean('enable_commitment_alerts')->default(true);
            $table->decimal('warning_threshold_percentage', 5, 2)->default(75)->comment('Warning threshold for commitment progress');
            $table->decimal('critical_threshold_percentage', 5, 2)->default(90)->comment('Critical threshold for commitment progress');
            $table->json('alert_recipients')->nullable()->comment('Who receives commitment alerts');
            $table->timestamp('last_alert_sent_at')->nullable();

            // Compliance and Reporting
            $table->boolean('requires_reporting')->default(true);
            $table->string('reporting_frequency', 20)->default('monthly')->comment('Reporting schedule');
            $table->json('reporting_recipients')->nullable();
            $table->timestamp('last_report_sent_at')->nullable();
            $table->json('compliance_requirements')->nullable();

            // Contract Integration
            $table->string('contract_reference', 100)->nullable();
            $table->json('contract_terms')->nullable()->comment('Specific contract terms');
            $table->boolean('is_master_service_agreement')->default(false);
            $table->json('sla_requirements')->nullable()->comment('Service level agreement requirements');

            // Performance Metrics and Analytics
            $table->decimal('commitment_efficiency_score', 5, 2)->default(0)->comment('Overall efficiency score');
            $table->json('performance_trends')->nullable()->comment('Performance trend analysis');
            $table->json('benchmark_comparisons')->nullable()->comment('Benchmark against industry/peers');
            $table->decimal('roi_percentage', 8, 3)->default(0)->comment('Return on investment');

            // External System Integration
            $table->string('external_commitment_id', 100)->nullable()->index();
            $table->json('integration_metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status', 20)->nullable();

            // Commitment Status and Lifecycle
            $table->string('commitment_status', 20)->default('active')->index()->comment('active, suspended, completed, cancelled, breached');
            $table->text('status_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Amendment and Modification Tracking
            $table->integer('amendment_number')->default(0);
            $table->json('amendment_history')->nullable()->comment('History of commitment changes');
            $table->unsignedBigInteger('amended_by')->nullable();
            $table->timestamp('last_amended_at')->nullable();
            $table->text('amendment_reason')->nullable();

            // Risk Assessment and Management
            $table->string('risk_level', 20)->default('medium')->comment('low, medium, high, critical');
            $table->decimal('default_probability', 5, 2)->default(0)->comment('Probability of default percentage');
            $table->json('risk_factors')->nullable()->comment('Identified risk factors');
            $table->json('mitigation_strategies')->nullable()->comment('Risk mitigation approaches');

            // Approval and Workflow
            $table->string('approval_status', 20)->default('approved')->comment('pending, approved, rejected');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->json('approval_workflow')->nullable();

            // Audit and Change Management
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('change_reason')->nullable();
            $table->json('audit_trail')->nullable()->comment('Complete audit trail');

            $table->timestamps();
            $table->softDeletes();

            // Foreign Key Constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('pricing_rule_id')->references('id')->on('pricing_rules')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('amended_by')->references('id')->on('users')->onDelete('set null');

            // Performance Indexes
            $table->index(['company_id', 'commitment_type', 'is_active'], 'usage_commitments_type_active_idx');
            $table->index(['client_id', 'commitment_status', 'is_active'], 'usage_commitments_client_status_idx');
            $table->index(['usage_type', 'commitment_period'], 'usage_commitments_usage_period_idx');
            $table->index(['commitment_start_date', 'commitment_end_date'], 'usage_commitments_date_range_idx');
            $table->index(['next_evaluation_date', 'is_active'], 'usage_commitments_evaluation_idx');
            $table->index(['current_period_start_date', 'current_period_end_date'], 'usage_commitments_current_period_idx');
            $table->index(['fulfillment_percentage', 'commitment_status'], 'usage_commitments_fulfillment_idx');

            // Unique Constraints
            $table->unique(['company_id', 'commitment_code'], 'usage_commitments_code_unique');
        });

        // Add table comment
        DB::statement("ALTER TABLE usage_commitments COMMENT = 'Minimum usage agreements and commitment tracking with penalty calculations and forecasting'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_commitments');
    }
}