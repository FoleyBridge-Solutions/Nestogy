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
        Schema::create('kpi_calculations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('kpi_name'); // 'revenue_growth', 'customer_churn', 'ltv_cac_ratio', etc.
            $table->string('kpi_category'); // 'financial', 'customer', 'operational', 'growth', 'profitability'
            $table->string('calculation_period'); // 'daily', 'weekly', 'monthly', 'quarterly', 'annual'
            $table->date('calculation_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('kpi_value', 15, 6); // Support for percentages and ratios
            $table->decimal('target_value', 15, 6)->nullable(); // Target/goal for this KPI
            $table->decimal('previous_period_value', 15, 6)->nullable(); // For trend analysis
            $table->decimal('year_over_year_value', 15, 6)->nullable(); // YoY comparison
            $table->string('performance_status')->nullable(); // 'excellent', 'good', 'warning', 'critical'
            $table->string('trend_direction')->nullable(); // 'up', 'down', 'stable'
            $table->decimal('trend_percentage', 8, 4)->nullable(); // Percentage change from previous period
            $table->string('unit_type')->default('number'); // 'number', 'percentage', 'currency', 'ratio'
            $table->string('display_format')->nullable(); // How to display the KPI (decimal places, etc.)
            $table->json('calculation_components'); // What data points were used
            $table->json('drill_down_data')->nullable(); // Supporting data for detailed analysis
            $table->json('benchmarks')->nullable(); // Industry or historical benchmarks
            $table->json('alerts_triggered')->nullable(); // Any alerts this calculation triggered
            $table->text('calculation_notes')->nullable(); // Explanatory notes
            $table->boolean('is_outlier')->default(false); // Statistical outlier detection
            $table->decimal('confidence_score', 5, 4)->nullable(); // Data quality/confidence score
            $table->string('data_completeness')->nullable(); // 'complete', 'partial', 'estimated'
            $table->json('data_sources')->nullable(); // What tables/sources contributed to calculation
            $table->bigInteger('calculation_time_ms')->nullable(); // Performance tracking
            $table->string('calculation_method')->nullable(); // Algorithm or formula used
            $table->string('status')->default('completed'); // 'pending', 'processing', 'completed', 'error'
            $table->text('error_details')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'kpi_name', 'calculation_date'], 'kpi_calculations_unique');
            $table->index(['kpi_category', 'calculation_date', 'company_id'], 'kpi__calc_comp_idx');
            $table->index(['performance_status', 'kpi_category']);
            $table->index(['calculation_period', 'period_start', 'period_end'], 'calc_peri_peri_idx');
            $table->index(['trend_direction', 'trend_percentage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_calculations');
    }
};