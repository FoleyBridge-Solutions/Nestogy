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
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->string('name')->nullable(); // User-defined name
            $table->string('type')->default('invoice_auto_pay'); // invoice_auto_pay, recurring_payment, scheduled_payment
            $table->string('frequency')->nullable(); // For recurring payments: monthly, quarterly, annually
            $table->boolean('is_active')->default(true);
            $table->boolean('is_paused')->default(false);
            $table->timestamp('paused_until')->nullable();
            
            // Auto-pay Configuration
            $table->string('trigger_type')->default('invoice_due'); // invoice_due, invoice_sent, fixed_schedule
            $table->integer('trigger_days_offset')->default(0); // Days before/after trigger
            $table->string('trigger_time')->default('09:00'); // Time of day to process
            $table->json('trigger_conditions')->nullable(); // Additional trigger conditions
            
            // Payment Rules
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Don't auto-pay below this amount
            $table->decimal('maximum_amount', 12, 2)->nullable(); // Don't auto-pay above this amount
            $table->json('invoice_types')->nullable(); // Which invoice types to auto-pay
            $table->json('excluded_invoice_types')->nullable(); // Which invoice types to exclude
            $table->boolean('partial_payment_allowed')->default(false);
            $table->decimal('partial_payment_percentage', 5, 2)->nullable();
            $table->decimal('partial_payment_fixed_amount', 10, 2)->nullable();
            
            // Retry Configuration
            $table->boolean('retry_on_failure')->default(true);
            $table->integer('max_retry_attempts')->default(3);
            $table->json('retry_schedule')->nullable(); // Days between retry attempts
            $table->string('retry_escalation')->nullable(); // What to do after max retries
            
            // Notification Settings
            $table->boolean('send_success_notifications')->default(true);
            $table->boolean('send_failure_notifications')->default(true);
            $table->boolean('send_retry_notifications')->default(false);
            $table->boolean('send_pause_notifications')->default(true);
            $table->json('notification_methods')->nullable(); // email, sms, portal
            $table->json('notification_recipients')->nullable(); // Who gets notified
            
            // Processing Schedule
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('next_processing_date')->nullable();
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamp('last_successful_payment_at')->nullable();
            $table->timestamp('last_failed_payment_at')->nullable();
            
            // Statistics and Tracking
            $table->integer('total_payments_processed')->default(0);
            $table->integer('successful_payments_count')->default(0);
            $table->integer('failed_payments_count')->default(0);
            $table->decimal('total_amount_processed', 15, 2)->default(0.00);
            $table->decimal('total_fees_paid', 12, 2)->default(0.00);
            $table->integer('consecutive_failures')->default(0);
            $table->json('failure_reasons')->nullable(); // Track common failure reasons
            
            // Security and Risk Management
            $table->boolean('requires_confirmation')->default(false); // Require manual confirmation
            $table->decimal('daily_limit', 12, 2)->nullable();
            $table->decimal('monthly_limit', 15, 2)->nullable();
            $table->json('risk_rules')->nullable(); // Custom risk management rules
            $table->json('velocity_checks')->nullable(); // Transaction velocity monitoring
            
            // Client Controls
            $table->boolean('client_can_pause')->default(true);
            $table->boolean('client_can_modify')->default(true);
            $table->boolean('client_can_cancel')->default(true);
            $table->boolean('requires_client_approval')->default(false);
            $table->json('client_notifications')->nullable(); // Client notification preferences
            
            // Business Rules
            $table->json('business_rules')->nullable(); // Custom business logic
            $table->json('exception_handling')->nullable(); // How to handle exceptions
            $table->string('currency_code', 3)->default('USD');
            $table->json('allowed_currencies')->nullable(); // Multi-currency support
            $table->boolean('currency_conversion_allowed')->default(false);
            
            // Integration and Webhooks
            $table->json('webhook_urls')->nullable(); // External webhooks to call
            $table->json('integration_settings')->nullable(); // Third-party integrations
            $table->string('external_reference_id')->nullable(); // Reference in external systems
            
            // Metadata and Customization
            $table->json('metadata')->nullable(); // Additional configuration
            $table->json('custom_fields')->nullable(); // Client-specific custom fields
            $table->text('notes')->nullable(); // Internal notes
            $table->text('client_notes')->nullable(); // Notes visible to client
            
            // Lifecycle Management
            $table->string('status')->default('active'); // active, paused, suspended, cancelled, expired
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('client_id');
            $table->index('payment_method_id');
            $table->index('type');
            $table->index('frequency');
            $table->index('is_active');
            $table->index('is_paused');
            $table->index('trigger_type');
            $table->index('status');
            $table->index('next_processing_date');
            $table->index('last_processed_at');
            $table->index('start_date');
            $table->index('end_date');
            $table->index(['company_id', 'client_id']);
            $table->index(['client_id', 'is_active']);
            $table->index(['client_id', 'type']);
            $table->index(['is_active', 'next_processing_date']);
            $table->index(['status', 'next_processing_date']);
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('cancelled_by');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
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