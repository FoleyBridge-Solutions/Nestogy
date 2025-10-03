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
            $table->unsignedBigInteger('company_id');
            $table->string('projection_type')->nullable();
            $table->string('projection_model')->nullable();
            $table->timestamp('projection_date')->nullable();
            $table->string('period_start')->nullable();
            $table->string('period_end')->nullable();
            $table->string('projected_inflow')->nullable();
            $table->string('projected_outflow')->nullable();
            $table->string('net_cash_flow')->nullable();
            $table->string('opening_balance')->nullable();
            $table->string('closing_balance')->nullable();
            $table->string('inflow_breakdown')->nullable();
            $table->string('outflow_breakdown')->nullable();
            $table->string('assumptions')->nullable();
            $table->string('risk_factors')->nullable();
            $table->string('confidence_interval_low')->nullable();
            $table->string('confidence_interval_high')->nullable();
            $table->string('confidence_percentage')->nullable();
            $table->string('actual_inflow')->nullable();
            $table->string('actual_outflow')->nullable();
            $table->string('actual_net_flow')->nullable();
            $table->string('variance_percentage')->nullable();
            $table->string('accuracy_rating')->nullable();
            $table->string('seasonal_adjustments')->nullable();
            $table->string('recurring_items')->nullable();
            $table->string('one_time_items')->nullable();
            $table->string('contract_renewals')->nullable();
            $table->string('new_business')->nullable();
            $table->string('churn_projections')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->string('metadata')->nullable();
            $table->string('created_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
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
        Schema::dropIfExists('cash_flow_projections');
    }
};
