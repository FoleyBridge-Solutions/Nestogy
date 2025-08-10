<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration for Usage Records Table
 * 
 * Tracks individual usage transactions for VoIP services including calls, data, messages, and features.
 * Designed for high-volume CDR ingestion with optimized indexes for real-time processing.
 */
class CreateUsageRecordsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('usage_pool_id')->nullable()->index();
            $table->unsignedBigInteger('usage_bucket_id')->nullable()->index();

            // Usage Identification
            $table->string('transaction_id', 100)->unique()->comment('Unique transaction identifier');
            $table->string('cdr_id', 100)->nullable()->index()->comment('Call Detail Record ID from carrier');
            $table->string('external_id', 100)->nullable()->index()->comment('External system reference');
            $table->string('batch_id', 50)->nullable()->index()->comment('CDR batch processing identifier');

            // Usage Type and Classification
            $table->string('usage_type', 50)->index()->comment('voice, data, sms, mms, feature, equipment, api');
            $table->string('service_type', 50)->index()->comment('local, long_distance, international, hosted_pbx, sip_trunking');
            $table->string('usage_category', 50)->nullable()->index()->comment('Additional categorization');
            $table->string('billing_category', 50)->nullable()->index()->comment('Billing classification');

            // Usage Metrics
            $table->decimal('quantity', 15, 4)->default(0)->comment('Usage quantity (minutes, MB, messages, etc.)');
            $table->string('unit_type', 20)->comment('minute, mb, gb, message, call, line, api_call');
            $table->decimal('duration_seconds', 10, 2)->nullable()->comment('Call duration in seconds');
            $table->integer('line_count')->default(1)->comment('Number of lines/channels used');
            $table->decimal('data_volume_mb', 12, 4)->nullable()->comment('Data volume in megabytes');

            // Geographic and Routing Information
            $table->string('origination_number', 20)->nullable()->index();
            $table->string('destination_number', 20)->nullable()->index();
            $table->string('origination_country', 3)->nullable()->index();
            $table->string('destination_country', 3)->nullable()->index();
            $table->string('origination_state', 10)->nullable();
            $table->string('destination_state', 10)->nullable();
            $table->string('route_type', 50)->nullable()->comment('Route classification');
            $table->string('carrier_name', 100)->nullable();

            // Timing Information
            $table->timestamp('usage_start_time')->index()->comment('When usage began');
            $table->timestamp('usage_end_time')->nullable()->comment('When usage ended');
            $table->string('time_zone', 50)->default('UTC');
            $table->boolean('is_peak_time')->default(false)->index();
            $table->boolean('is_weekend')->default(false);

            // Pricing and Billing
            $table->decimal('unit_rate', 10, 6)->nullable()->comment('Rate per unit');
            $table->decimal('base_cost', 10, 4)->default(0)->comment('Base cost before taxes');
            $table->decimal('markup_amount', 10, 4)->default(0)->comment('Markup applied');
            $table->decimal('discount_amount', 10, 4)->default(0)->comment('Discount applied');
            $table->decimal('tax_amount', 10, 4)->default(0)->comment('Tax calculated');
            $table->decimal('total_cost', 10, 4)->default(0)->comment('Total billable amount');
            $table->string('currency_code', 3)->default('USD');

            // Quality and Status
            $table->string('call_quality', 20)->nullable()->comment('HD, Standard, Poor');
            $table->string('completion_status', 20)->default('completed')->comment('completed, failed, partial');
            $table->text('status_reason')->nullable()->comment('Additional status information');
            $table->integer('quality_score')->nullable()->comment('1-100 quality rating');

            // Processing and Validation
            $table->string('processing_status', 20)->default('pending')->index()->comment('pending, processed, billed, disputed');
            $table->boolean('is_billable')->default(true)->index();
            $table->boolean('is_validated')->default(false)->index();
            $table->boolean('is_disputed')->default(false)->index();
            $table->boolean('is_fraud_flagged')->default(false)->index();
            $table->text('validation_notes')->nullable();

            // Usage Pool and Allocation
            $table->boolean('is_pooled_usage')->default(false)->index();
            $table->decimal('allocated_from_pool', 15, 4)->nullable()->comment('Amount allocated from usage pool');
            $table->decimal('overage_amount', 15, 4)->nullable()->comment('Amount exceeding allowances');

            // Aggregation and Reporting
            $table->date('usage_date')->index()->comment('Date for aggregation purposes');
            $table->tinyInteger('usage_hour')->nullable()->comment('Hour of day (0-23)');
            $table->string('billing_period', 20)->nullable()->index()->comment('Billing cycle identifier');
            $table->boolean('is_aggregated')->default(false)->index();

            // Technical Details
            $table->string('protocol', 20)->nullable()->comment('SIP, H.323, WebRTC, etc.');
            $table->string('codec', 20)->nullable()->comment('G.711, G.729, G.722, etc.');
            $table->json('technical_metadata')->nullable()->comment('Additional technical data');
            $table->json('custom_attributes')->nullable()->comment('Client-specific attributes');

            // CDR Source Information
            $table->string('cdr_source', 50)->nullable()->comment('Source system/carrier');
            $table->timestamp('cdr_received_at')->nullable()->comment('When CDR was received');
            $table->timestamp('processed_at')->nullable()->comment('When record was processed');
            $table->string('processing_version', 10)->nullable()->comment('Processing system version');

            // Audit and Compliance
            $table->json('raw_cdr_data')->nullable()->comment('Original CDR data for compliance');
            $table->string('regulatory_classification', 50)->nullable();
            $table->boolean('requires_audit')->default(false);
            $table->text('compliance_notes')->nullable();

            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Foreign Key Constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('usage_pool_id')->references('id')->on('usage_pools')->onDelete('set null');
            $table->foreign('usage_bucket_id')->references('id')->on('usage_buckets')->onDelete('set null');

            // Optimized Indexes for High-Volume Processing
            $table->index(['company_id', 'client_id', 'usage_date'], 'usage_client_date_idx');
            $table->index(['company_id', 'usage_type', 'service_type', 'usage_date'], 'usage_type_service_date_idx');
            $table->index(['company_id', 'processing_status', 'created_at'], 'usage_processing_status_idx');
            $table->index(['company_id', 'billing_period', 'is_billable'], 'usage_billing_period_idx');
            $table->index(['cdr_id', 'cdr_source'], 'usage_cdr_lookup_idx');
            $table->index(['origination_number', 'destination_number', 'usage_start_time'], 'usage_call_lookup_idx');
            $table->index(['usage_start_time', 'usage_type', 'is_billable'], 'usage_time_billing_idx');
            
            // Partitioning Support (for high-volume environments)
            $table->index(['usage_date', 'company_id'], 'usage_partition_idx');
        });

        // Add table comment
        DB::statement("ALTER TABLE usage_records COMMENT = 'Individual usage transaction records for VoIP services with CDR processing support'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
}