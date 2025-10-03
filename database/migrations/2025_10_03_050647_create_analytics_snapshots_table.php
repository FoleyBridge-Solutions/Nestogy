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
            $table->unsignedBigInteger('company_id');
            $table->string('snapshot_type')->nullable();
            $table->timestamp('snapshot_date')->nullable();
            $table->string('period_start')->nullable();
            $table->string('period_end')->nullable();
            $table->string('data_category')->nullable();
            $table->string('metrics_data')->nullable();
            $table->string('kpi_data')->nullable();
            $table->string('trend_data')->nullable();
            $table->string('breakdown_data')->nullable();
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->string('recurring_revenue')->nullable();
            $table->string('one_time_revenue')->nullable();
            $table->boolean('active_clients')->default(false);
            $table->string('new_clients')->nullable();
            $table->string('churned_clients')->nullable();
            $table->string('average_deal_size')->nullable();
            $table->string('customer_lifetime_value')->nullable();
            $table->string('customer_acquisition_cost')->nullable();
            $table->string('gross_profit_margin')->nullable();
            $table->string('net_profit_margin')->nullable();
            $table->string('invoices_sent')->nullable();
            $table->string('invoices_paid')->nullable();
            $table->string('collection_efficiency')->nullable();
            $table->string('outstanding_receivables')->nullable();
            $table->string('quotes_sent')->nullable();
            $table->string('quotes_accepted')->nullable();
            $table->string('quote_conversion_rate')->nullable();
            $table->string('voip_metrics')->nullable();
            $table->string('tax_metrics')->nullable();
            $table->string('contract_metrics')->nullable();
            $table->string('calculation_status')->default('active');
            $table->text('calculation_notes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->string('calculation_duration_ms')->nullable();
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
        Schema::dropIfExists('analytics_snapshots');
    }
};
