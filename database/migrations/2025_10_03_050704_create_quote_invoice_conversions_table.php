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
        Schema::create('quote_invoice_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('quote_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('conversion_type')->nullable();
            $table->string('status')->default('active');
            $table->string('conversion_settings')->nullable();
            $table->string('pricing_adjustments')->nullable();
            $table->string('tax_calculations')->nullable();
            $table->string('milestone_schedule')->nullable();
            $table->string('recurring_schedule')->nullable();
            $table->string('requires_service_activation')->nullable();
            $table->string('service_activation_data')->nullable();
            $table->string('activation_status')->default('active');
            $table->timestamp('service_activated_at')->nullable();
            $table->string('voip_service_mapping')->nullable();
            $table->string('equipment_allocation')->nullable();
            $table->string('porting_requirements')->nullable();
            $table->string('compliance_mappings')->nullable();
            $table->string('original_quote_value')->nullable();
            $table->string('converted_value')->nullable();
            $table->decimal('adjustment_amount', 15, 2)->default(0);
            $table->string('adjustment_reason')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('exchange_rate')->nullable();
            $table->timestamp('rate_locked_at')->nullable();
            $table->string('revenue_schedule')->nullable();
            $table->string('deferred_revenue')->nullable();
            $table->string('recognized_revenue')->nullable();
            $table->string('conversion_workflow')->nullable();
            $table->string('approval_chain')->nullable();
            $table->string('completed_steps')->nullable();
            $table->string('current_step')->nullable();
            $table->decimal('total_steps', 15, 2)->default(0);
            $table->string('error_log')->nullable();
            $table->string('retry_count')->nullable();
            $table->string('max_retries')->nullable();
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->string('integration_data')->nullable();
            $table->string('automated_conversion')->nullable();
            $table->string('automation_rules')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->timestamp('conversion_started_at')->nullable();
            $table->timestamp('conversion_completed_at')->nullable();
            $table->string('processing_duration')->nullable();
            $table->string('performance_metrics')->nullable();
            $table->string('audit_trail')->nullable();
            $table->string('compliance_checks')->nullable();
            $table->string('regulatory_approved')->nullable();
            $table->text('compliance_notes')->nullable();
            $table->string('initiated_by')->nullable();
            $table->string('completed_by')->nullable();
            $table->string('approved_by')->nullable();
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
        Schema::dropIfExists('quote_invoice_conversions');
    }
};
