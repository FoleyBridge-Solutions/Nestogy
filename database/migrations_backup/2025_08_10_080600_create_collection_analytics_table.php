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
        Schema::create('collection_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->date('analysis_date');
            $table->enum('analysis_type', [
                'daily', 'weekly', 'monthly', 'quarterly', 'yearly',
                'campaign', 'client', 'invoice', 'custom'
            ])->default('daily');
            
            // Reference entities
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('custom_segment')->nullable(); // custom analysis segment
            
            // Collection performance metrics
            $table->decimal('total_amount_overdue', 15, 2)->default(0);
            $table->decimal('amount_collected', 15, 2)->default(0);
            $table->decimal('collection_rate', 5, 4)->default(0); // percentage collected
            $table->integer('total_overdue_invoices')->default(0);
            $table->integer('invoices_collected')->default(0);
            $table->decimal('average_collection_time', 8, 2)->default(0); // days
            $table->decimal('median_collection_time', 8, 2)->default(0); // days
            
            // Aging analysis
            $table->decimal('amount_0_30_days', 15, 2)->default(0);
            $table->decimal('amount_31_60_days', 15, 2)->default(0);
            $table->decimal('amount_61_90_days', 15, 2)->default(0);
            $table->decimal('amount_91_120_days', 15, 2)->default(0);
            $table->decimal('amount_over_120_days', 15, 2)->default(0);
            $table->integer('count_0_30_days')->default(0);
            $table->integer('count_31_60_days')->default(0);
            $table->integer('count_61_90_days')->default(0);
            $table->integer('count_91_120_days')->default(0);
            $table->integer('count_over_120_days')->default(0);
            
            // Campaign performance
            $table->integer('campaigns_active')->default(0);
            $table->integer('actions_executed')->default(0);
            $table->integer('emails_sent')->default(0);
            $table->integer('sms_sent')->default(0);
            $table->integer('calls_made')->default(0);
            $table->integer('letters_sent')->default(0);
            $table->decimal('email_open_rate', 5, 4)->default(0);
            $table->decimal('email_click_rate', 5, 4)->default(0);
            $table->decimal('sms_response_rate', 5, 4)->default(0);
            $table->decimal('call_connection_rate', 5, 4)->default(0);
            
            // Client interaction metrics
            $table->integer('clients_contacted')->default(0);
            $table->integer('promises_to_pay')->default(0);
            $table->integer('promises_kept')->default(0);
            $table->integer('promises_broken')->default(0);
            $table->decimal('promise_keep_rate', 5, 4)->default(0);
            $table->integer('disputes_raised')->default(0);
            $table->integer('disputes_resolved')->default(0);
            $table->integer('hardship_cases')->default(0);
            
            // Payment plan metrics
            $table->integer('payment_plans_created')->default(0);
            $table->integer('payment_plans_active')->default(0);
            $table->integer('payment_plans_completed')->default(0);
            $table->integer('payment_plans_defaulted')->default(0);
            $table->decimal('payment_plan_success_rate', 5, 4)->default(0);
            $table->decimal('average_plan_amount', 15, 2)->default(0);
            $table->decimal('total_plan_collections', 15, 2)->default(0);
            
            // Service suspension metrics
            $table->integer('accounts_suspended')->default(0);
            $table->integer('accounts_restored')->default(0);
            $table->decimal('suspension_collection_rate', 5, 4)->default(0);
            $table->decimal('average_suspension_duration', 8, 2)->default(0); // days
            $table->decimal('revenue_impact_suspension', 15, 2)->default(0);
            
            // Cost and ROI analysis
            $table->decimal('collection_costs', 10, 2)->default(0);
            $table->decimal('cost_per_collection', 10, 4)->default(0);
            $table->decimal('collection_roi', 8, 4)->default(0); // return on investment
            $table->decimal('staff_time_hours', 8, 2)->default(0);
            $table->decimal('average_cost_per_hour', 8, 2)->default(0);
            $table->decimal('technology_costs', 10, 2)->default(0);
            $table->decimal('legal_costs', 10, 2)->default(0);
            
            // Risk and write-off analysis
            $table->decimal('high_risk_amount', 15, 2)->default(0);
            $table->integer('high_risk_accounts')->default(0);
            $table->decimal('written_off_amount', 15, 2)->default(0);
            $table->integer('written_off_accounts')->default(0);
            $table->decimal('bad_debt_rate', 5, 4)->default(0);
            $table->decimal('recovery_after_writeoff', 15, 2)->default(0);
            
            // Legal and compliance metrics
            $table->integer('legal_actions_initiated')->default(0);
            $table->integer('collection_agency_referrals')->default(0);
            $table->integer('compliance_violations')->default(0);
            $table->integer('customer_complaints')->default(0);
            $table->integer('regulatory_inquiries')->default(0);
            $table->decimal('compliance_cost', 10, 2)->default(0);
            
            // VoIP-specific metrics
            $table->integer('voip_services_suspended')->default(0);
            $table->integer('numbers_ported_during_collection')->default(0);
            $table->decimal('e911_fees_in_collections', 10, 2)->default(0);
            $table->decimal('regulatory_fees_in_collections', 10, 2)->default(0);
            $table->integer('international_accounts_in_collections')->default(0);
            $table->decimal('equipment_recovery_value', 15, 2)->default(0);
            
            // Predictive analytics
            $table->decimal('predicted_collection_rate', 5, 4)->default(0);
            $table->decimal('predicted_writeoff_amount', 15, 2)->default(0);
            $table->json('risk_factors')->nullable(); // ML-identified risk factors
            $table->json('success_factors')->nullable(); // ML-identified success factors
            $table->decimal('ai_confidence_score', 5, 4)->default(0);
            
            // Seasonal and trend analysis
            $table->decimal('seasonal_adjustment_factor', 6, 4)->default(1.0);
            $table->json('trend_indicators')->nullable(); // trend analysis data
            $table->decimal('month_over_month_change', 6, 4)->default(0);
            $table->decimal('year_over_year_change', 6, 4)->default(0);
            
            // Benchmarking data
            $table->decimal('industry_benchmark_collection_rate', 5, 4)->nullable();
            $table->decimal('industry_benchmark_cost', 10, 4)->nullable();
            $table->decimal('performance_vs_benchmark', 6, 4)->default(0);
            $table->json('benchmark_sources')->nullable();
            
            // Data quality and processing
            $table->integer('records_processed')->default(0);
            $table->integer('data_quality_score')->default(100); // 0-100 scale
            $table->json('data_quality_issues')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->boolean('manual_override')->default(false);
            $table->text('notes')->nullable();
            
            // Aggregation metadata
            $table->json('source_campaigns')->nullable(); // which campaigns contributed
            $table->json('source_date_range')->nullable(); // date range for aggregation
            $table->json('filters_applied')->nullable(); // filters used in analysis
            $table->string('generated_by')->nullable(); // system or user
            $table->json('calculation_parameters')->nullable(); // parameters used
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'analysis_date', 'analysis_type'], 'comp_anal_anal_idx');
            $table->index(['campaign_id', 'analysis_date']);
            $table->index(['client_id', 'analysis_date']);
            $table->index(['analysis_type', 'analysis_date']);
            $table->index(['collection_rate', 'analysis_date']);
            $table->index(['bad_debt_rate', 'analysis_date']);
            
            // Composite indexes for common queries
            $table->index(['company_id', 'analysis_type', 'analysis_date'], 'idx_company_type_date');
            $table->index(['collection_rate', 'cost_per_collection'], 'idx_performance_metrics');
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('dunning_campaigns')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_analytics');
    }
};