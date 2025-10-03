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
            $table->unsignedBigInteger('company_id');
            $table->string('kpi_name');
            $table->string('kpi_category')->nullable();
            $table->string('calculation_period')->nullable();
            $table->timestamp('calculation_date')->nullable();
            $table->string('period_start')->nullable();
            $table->string('period_end')->nullable();
            $table->string('kpi_value')->nullable();
            $table->string('target_value')->nullable();
            $table->string('previous_period_value')->nullable();
            $table->string('year_over_year_value')->nullable();
            $table->string('performance_status')->default('active');
            $table->string('trend_direction')->nullable();
            $table->string('trend_percentage')->nullable();
            $table->string('unit_type')->nullable();
            $table->string('display_format')->nullable();
            $table->string('calculation_components')->nullable();
            $table->string('drill_down_data')->nullable();
            $table->string('benchmarks')->nullable();
            $table->string('alerts_triggered')->nullable();
            $table->text('calculation_notes')->nullable();
            $table->boolean('is_outlier')->default(false);
            $table->string('confidence_score')->nullable();
            $table->string('data_completeness')->nullable();
            $table->string('data_sources')->nullable();
            $table->string('calculation_time_ms')->nullable();
            $table->string('calculation_method')->nullable();
            $table->string('status')->default('active');
            $table->string('error_details')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->string('metadata')->nullable();
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
        Schema::dropIfExists('kpi_calculations');
    }
};
