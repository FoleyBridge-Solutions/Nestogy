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
        Schema::create('cash_flow_projections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('projection_type'); // 'weekly', 'monthly', 'quarterly', 'annual', 'custom'
            $table->string('projection_model'); // 'linear', 'seasonal', 'ml_based', 'manual'
            $table->date('projection_date'); // Date this projection was made
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('projected_inflow', 15, 2); // Expected cash inflows
            $table->decimal('projected_outflow', 15, 2); // Expected cash outflows
            $table->decimal('net_cash_flow', 15, 2); // Net projected cash flow
            $table->decimal('opening_balance', 15, 2); // Starting cash position
            $table->decimal('closing_balance', 15, 2); // Projected ending cash position
            $table->json('inflow_breakdown')->nullable(); // Detailed inflow sources
            $table->json('outflow_breakdown')->nullable(); // Detailed outflow categories
            $table->json('assumptions')->nullable(); // Key assumptions used in projection
            $table->json('risk_factors')->nullable(); // Identified risks and probabilities
            $table->decimal('confidence_interval_low', 15, 2)->nullable(); // Lower bound
            $table->decimal('confidence_interval_high', 15, 2)->nullable(); // Upper bound
            $table->decimal('confidence_percentage', 5, 2)->nullable(); // Confidence level
            $table->decimal('actual_inflow', 15, 2)->nullable(); // Actual results (for accuracy tracking)
            $table->decimal('actual_outflow', 15, 2)->nullable();
            $table->decimal('actual_net_flow', 15, 2)->nullable();
            $table->decimal('variance_percentage', 8, 4)->nullable(); // Actual vs projected variance
            $table->string('accuracy_rating')->nullable(); // 'excellent', 'good', 'fair', 'poor'
            $table->json('seasonal_adjustments')->nullable(); // Seasonal factors applied
            $table->json('recurring_items')->nullable(); // Known recurring revenue/expenses
            $table->json('one_time_items')->nullable(); // Expected one-time items
            $table->json('contract_renewals')->nullable(); // Expected contract renewals
            $table->json('new_business')->nullable(); // Projected new business
            $table->json('churn_projections')->nullable(); // Expected customer churn impact
            $table->boolean('is_locked')->default(false); // Prevents modification after actuals
            $table->string('status')->default('draft'); // 'draft', 'approved', 'published', 'archived'
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['company_id', 'projection_type', 'period_start'], 'cashflow_company_proj_period_idx');
            $table->index(['period_start', 'period_end', 'status'], 'peri_peri_stat_idx');
            $table->index(['projection_model', 'confidence_percentage'], 'cashflow_model_confidence_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flow_projections');
    }
};