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
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('snapshot_type'); // 'daily', 'weekly', 'monthly', 'quarterly', 'annual'
            $table->date('snapshot_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('data_category'); // 'revenue', 'customers', 'operations', 'tax', 'contracts'
            $table->json('metrics_data'); // All calculated metrics for the period
            $table->json('kpi_data'); // Key performance indicators
            $table->json('trend_data')->nullable(); // Trend analysis and comparisons
            $table->json('breakdown_data')->nullable(); // Detailed breakdowns by service, client, etc.
            $table->decimal('total_revenue', 15, 2)->nullable();
            $table->decimal('recurring_revenue', 15, 2)->nullable();
            $table->decimal('one_time_revenue', 15, 2)->nullable();
            $table->integer('active_clients')->nullable();
            $table->integer('new_clients')->nullable();
            $table->integer('churned_clients')->nullable();
            $table->decimal('average_deal_size', 15, 2)->nullable();
            $table->decimal('customer_lifetime_value', 15, 2)->nullable();
            $table->decimal('customer_acquisition_cost', 15, 2)->nullable();
            $table->decimal('gross_profit_margin', 8, 4)->nullable(); // As percentage
            $table->decimal('net_profit_margin', 8, 4)->nullable(); // As percentage
            $table->integer('invoices_sent')->nullable();
            $table->integer('invoices_paid')->nullable();
            $table->decimal('collection_efficiency', 8, 4)->nullable(); // As percentage
            $table->decimal('outstanding_receivables', 15, 2)->nullable();
            $table->integer('quotes_sent')->nullable();
            $table->integer('quotes_accepted')->nullable();
            $table->decimal('quote_conversion_rate', 8, 4)->nullable(); // As percentage
            $table->json('voip_metrics')->nullable(); // VoIP-specific analytics
            $table->json('tax_metrics')->nullable(); // Tax-related analytics
            $table->json('contract_metrics')->nullable(); // Contract performance data
            $table->string('calculation_status')->default('completed'); // 'pending', 'processing', 'completed', 'error'
            $table->text('calculation_notes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->bigInteger('calculation_duration_ms')->nullable(); // Performance tracking
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'snapshot_type', 'snapshot_date', 'data_category'], 'analytics_snapshot_unique');
            $table->index(['company_id', 'data_category', 'snapshot_date']);
            $table->index(['snapshot_type', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_snapshots');
    }
};