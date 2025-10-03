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
        Schema::create('auto_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('frequency')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_paused')->default(false);
            $table->string('paused_until')->nullable();
            $table->string('trigger_type')->nullable();
            $table->string('trigger_days_offset')->nullable();
            $table->string('trigger_time')->nullable();
            $table->string('trigger_conditions')->nullable();
            $table->decimal('minimum_amount', 15, 2)->default(0);
            $table->decimal('maximum_amount', 15, 2)->default(0);
            $table->string('invoice_types')->nullable();
            $table->string('excluded_invoice_types')->nullable();
            $table->string('partial_payment_allowed')->nullable();
            $table->string('partial_payment_percentage')->nullable();
            $table->decimal('partial_payment_fixed_amount', 15, 2)->default(0);
            $table->string('retry_on_failure')->nullable();
            $table->timestamp('max_retry_attempts')->nullable();
            $table->string('retry_schedule')->nullable();
            $table->string('retry_escalation')->nullable();
            $table->string('send_success_notifications')->nullable();
            $table->string('send_failure_notifications')->nullable();
            $table->string('send_retry_notifications')->nullable();
            $table->string('send_pause_notifications')->nullable();
            $table->string('notification_methods')->nullable();
            $table->string('notification_recipients')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('next_processing_date')->nullable();
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamp('last_successful_payment_at')->nullable();
            $table->timestamp('last_failed_payment_at')->nullable();
            $table->decimal('total_payments_processed', 15, 2)->default(0);
            $table->string('successful_payments_count')->nullable();
            $table->string('failed_payments_count')->nullable();
            $table->decimal('total_amount_processed', 15, 2)->default(0);
            $table->decimal('total_fees_paid', 15, 2)->default(0);
            $table->string('consecutive_failures')->nullable();
            $table->string('failure_reasons')->nullable();
            $table->string('requires_confirmation')->nullable();
            $table->string('daily_limit')->nullable();
            $table->string('monthly_limit')->nullable();
            $table->string('risk_rules')->nullable();
            $table->string('velocity_checks')->nullable();
            $table->string('client_can_pause')->nullable();
            $table->string('client_can_modify')->nullable();
            $table->string('client_can_cancel')->nullable();
            $table->string('requires_client_approval')->nullable();
            $table->string('client_notifications')->nullable();
            $table->string('business_rules')->nullable();
            $table->string('exception_handling')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('allowed_currencies')->nullable();
            $table->string('currency_conversion_allowed')->nullable();
            $table->string('webhook_urls')->nullable();
            $table->string('integration_settings')->nullable();
            $table->unsignedBigInteger('external_reference_id')->nullable();
            $table->string('metadata')->nullable();
            $table->string('custom_fields')->nullable();
            $table->text('notes')->nullable();
            $table->text('client_notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_by')->nullable();
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
        Schema::dropIfExists('auto_payments');
    }
};
