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
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('metric_type')->nullable();
            $table->string('period_type')->nullable();
            $table->timestamp('metric_date')->nullable();
            $table->string('period_start')->nullable();
            $table->string('period_end')->nullable();
            $table->string('metric_value')->nullable();
            $table->string('previous_value')->nullable();
            $table->decimal('growth_amount', 15, 2)->default(0);
            $table->string('growth_percentage')->nullable();
            $table->string('service_category')->nullable();
            $table->string('revenue_type')->nullable();
            $table->string('breakdown_data')->nullable();
            $table->string('calculation_details')->nullable();
            $table->string('customer_count')->nullable();
            $table->string('average_per_customer')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('metadata')->nullable();
            $table->boolean('is_projected')->default(false);
            $table->string('confidence_score')->nullable();
            $table->string('calculation_method')->nullable();
            $table->timestamp('calculated_at')->nullable();
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
        Schema::dropIfExists('revenue_metrics');
    }
};
