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
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('usage_pool_id')->nullable();
            $table->unsignedBigInteger('usage_bucket_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('cdr_id')->nullable();
            $table->unsignedBigInteger('external_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('usage_type')->nullable();
            $table->string('service_type')->nullable();
            $table->string('usage_category')->nullable();
            $table->string('billing_category')->nullable();
            $table->string('quantity')->nullable();
            $table->string('unit_type')->nullable();
            $table->string('duration_seconds')->nullable();
            $table->string('line_count')->nullable();
            $table->string('data_volume_mb')->nullable();
            $table->string('origination_number')->nullable();
            $table->string('destination_number')->nullable();
            $table->string('origination_country')->nullable();
            $table->string('destination_country')->nullable();
            $table->string('origination_state')->nullable();
            $table->string('destination_state')->nullable();
            $table->string('route_type')->nullable();
            $table->string('carrier_name');
            $table->string('usage_start_time')->nullable();
            $table->string('usage_end_time')->nullable();
            $table->string('time_zone')->nullable();
            $table->boolean('is_peak_time')->default(false);
            $table->boolean('is_weekend')->default(false);
            $table->string('unit_rate')->nullable();
            $table->string('base_cost')->nullable();
            $table->decimal('markup_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('currency_code')->nullable();
            $table->string('call_quality')->nullable();
            $table->string('completion_status')->default('active');
            $table->string('status_reason')->default('active');
            $table->string('quality_score')->nullable();
            $table->string('processing_status')->default('active');
            $table->boolean('is_billable')->default(false);
            $table->boolean('is_validated')->default(false);
            $table->boolean('is_disputed')->default(false);
            $table->boolean('is_fraud_flagged')->default(false);
            $table->text('validation_notes')->nullable();
            $table->boolean('is_pooled_usage')->default(false);
            $table->string('allocated_from_pool')->nullable();
            $table->decimal('overage_amount', 15, 2)->default(0);
            $table->timestamp('usage_date')->nullable();
            $table->string('usage_hour')->nullable();
            $table->string('billing_period')->nullable();
            $table->boolean('is_aggregated')->default(false);
            $table->string('protocol')->nullable();
            $table->string('codec')->nullable();
            $table->string('technical_metadata')->nullable();
            $table->timestamp('custom_attributes')->nullable();
            $table->string('cdr_source')->nullable();
            $table->timestamp('cdr_received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_version')->nullable();
            $table->string('raw_cdr_data')->nullable();
            $table->string('regulatory_classification')->nullable();
            $table->string('requires_audit')->nullable();
            $table->text('compliance_notes')->nullable();
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
        Schema::dropIfExists('usage_records');
    }
};
