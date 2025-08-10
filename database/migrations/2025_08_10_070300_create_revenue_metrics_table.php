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
        Schema::create('revenue_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index(); // Null for company-wide metrics
            $table->string('metric_type'); // 'mrr', 'arr', 'ltv', 'churn', 'arpu', 'expansion', 'contraction'
            $table->string('period_type'); // 'daily', 'weekly', 'monthly', 'quarterly', 'annual'
            $table->date('metric_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('metric_value', 15, 2); // Primary metric value
            $table->decimal('previous_value', 15, 2)->nullable(); // For comparison/growth calculations
            $table->decimal('growth_amount', 15, 2)->nullable(); // Absolute growth
            $table->decimal('growth_percentage', 8, 4)->nullable(); // Percentage growth
            $table->string('service_category')->nullable(); // 'voip', 'equipment', 'professional_services', etc.
            $table->string('revenue_type')->nullable(); // 'recurring', 'one_time', 'usage_based', 'overage'
            $table->json('breakdown_data')->nullable(); // Detailed breakdowns
            $table->json('calculation_details')->nullable(); // How the metric was calculated
            $table->integer('customer_count')->nullable(); // Number of customers contributing
            $table->decimal('average_per_customer', 15, 2)->nullable(); // Average value per customer
            $table->string('currency_code')->default('USD');
            $table->json('metadata')->nullable(); // Additional context data
            $table->boolean('is_projected')->default(false); // True for forecasted values
            $table->decimal('confidence_score', 5, 4)->nullable(); // Forecast confidence (0-1)
            $table->string('calculation_method')->nullable(); // Algorithm used for calculation
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->unique(['company_id', 'client_id', 'metric_type', 'metric_date'], 'revenue_metrics_unique');
            $table->index(['metric_type', 'metric_date', 'company_id']);
            $table->index(['period_start', 'period_end', 'metric_type']);
            $table->index(['service_category', 'revenue_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_metrics');
    }
};