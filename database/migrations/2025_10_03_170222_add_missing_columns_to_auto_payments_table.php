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
        Schema::table('auto_payments', function (Blueprint $table) {
            // Drop 'name' and 'status' if they exist - we'll add more specific fields
            if (Schema::hasColumn('auto_payments', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('auto_payments', 'status')) {
                $table->dropColumn('status');
            }
            
            // Foreign keys
            $table->foreignId('client_id')->after('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->after('client_id')->constrained()->onDelete('set null');
            
            // Basic info
            $table->string('name')->nullable()->after('payment_method_id');
            $table->enum('type', ['invoice_auto_pay', 'recurring_payment', 'scheduled_payment'])->default('invoice_auto_pay')->after('name');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'annually', 'semi_annually'])->nullable()->after('type');
            
            // Status flags
            $table->boolean('is_active')->default(true)->after('frequency');
            $table->boolean('is_paused')->default(false)->after('is_active');
            $table->timestamp('paused_until')->nullable()->after('is_paused');
            
            // Trigger settings
            $table->enum('trigger_type', ['invoice_due', 'invoice_sent', 'days_after_invoice', 'fixed_schedule'])->default('invoice_due')->after('paused_until');
            $table->integer('trigger_days_offset')->default(0)->after('trigger_type');
            $table->time('trigger_time')->nullable()->after('trigger_days_offset');
            $table->json('trigger_conditions')->nullable()->after('trigger_time');
            
            // Amount limits
            $table->decimal('minimum_amount', 15, 2)->nullable()->after('trigger_conditions');
            $table->decimal('maximum_amount', 15, 2)->nullable()->after('minimum_amount');
            
            // Invoice filtering
            $table->json('invoice_types')->nullable()->after('maximum_amount');
            $table->json('excluded_invoice_types')->nullable()->after('invoice_types');
            
            // Partial payments
            $table->boolean('partial_payment_allowed')->default(false)->after('excluded_invoice_types');
            $table->decimal('partial_payment_percentage', 5, 2)->nullable()->after('partial_payment_allowed');
            $table->decimal('partial_payment_fixed_amount', 15, 2)->nullable()->after('partial_payment_percentage');
            
            // Retry logic
            $table->boolean('retry_on_failure')->default(true)->after('partial_payment_fixed_amount');
            $table->integer('max_retry_attempts')->default(3)->after('retry_on_failure');
            $table->json('retry_schedule')->nullable()->after('max_retry_attempts');
            $table->json('retry_escalation')->nullable()->after('retry_schedule');
            
            // Notifications
            $table->boolean('send_success_notifications')->default(true)->after('retry_escalation');
            $table->boolean('send_failure_notifications')->default(true)->after('send_success_notifications');
            $table->boolean('send_retry_notifications')->default(false)->after('send_failure_notifications');
            $table->boolean('send_pause_notifications')->default(true)->after('send_retry_notifications');
            $table->json('notification_methods')->nullable()->after('send_pause_notifications');
            $table->json('notification_recipients')->nullable()->after('notification_methods');
            
            // Schedule
            $table->date('start_date')->nullable()->after('notification_recipients');
            $table->date('end_date')->nullable()->after('start_date');
            $table->timestamp('next_processing_date')->nullable()->after('end_date');
            $table->timestamp('last_processed_at')->nullable()->after('next_processing_date');
            $table->timestamp('last_successful_payment_at')->nullable()->after('last_processed_at');
            $table->timestamp('last_failed_payment_at')->nullable()->after('last_successful_payment_at');
            
            // Statistics
            $table->integer('total_payments_processed')->default(0)->after('last_failed_payment_at');
            $table->integer('successful_payments_count')->default(0)->after('total_payments_processed');
            $table->integer('failed_payments_count')->default(0)->after('successful_payments_count');
            $table->decimal('total_amount_processed', 15, 2)->default(0)->after('failed_payments_count');
            $table->decimal('total_fees_paid', 15, 2)->default(0)->after('total_amount_processed');
            $table->integer('consecutive_failures')->default(0)->after('total_fees_paid');
            $table->json('failure_reasons')->nullable()->after('consecutive_failures');
            
            // Approval and limits
            $table->boolean('requires_confirmation')->default(false)->after('failure_reasons');
            $table->decimal('daily_limit', 15, 2)->nullable()->after('requires_confirmation');
            $table->decimal('monthly_limit', 15, 2)->nullable()->after('daily_limit');
            
            // Risk management
            $table->json('risk_rules')->nullable()->after('monthly_limit');
            $table->json('velocity_checks')->nullable()->after('risk_rules');
            
            // Client permissions
            $table->boolean('client_can_pause')->default(true)->after('velocity_checks');
            $table->boolean('client_can_modify')->default(false)->after('client_can_pause');
            $table->boolean('client_can_cancel')->default(true)->after('client_can_modify');
            $table->boolean('requires_client_approval')->default(false)->after('client_can_cancel');
            
            // Additional settings
            $table->string('status')->default('active')->after('requires_client_approval');
            $table->string('currency_code')->default('USD')->after('status');
            $table->boolean('currency_conversion_allowed')->default(false)->after('currency_code');
            $table->timestamp('activated_at')->nullable()->after('currency_conversion_allowed');
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['client_id', 'is_active']);
            $table->index(['next_processing_date']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_payments', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['payment_method_id']);
            
            $table->dropColumn([
                'client_id', 'payment_method_id', 'type', 'frequency',
                'is_active', 'is_paused', 'paused_until', 'trigger_type',
                'trigger_days_offset', 'trigger_time', 'trigger_conditions',
                'minimum_amount', 'maximum_amount', 'invoice_types',
                'excluded_invoice_types', 'partial_payment_allowed',
                'partial_payment_percentage', 'partial_payment_fixed_amount',
                'retry_on_failure', 'max_retry_attempts', 'retry_schedule',
                'retry_escalation', 'send_success_notifications',
                'send_failure_notifications', 'send_retry_notifications',
                'send_pause_notifications', 'notification_methods',
                'notification_recipients', 'start_date', 'end_date',
                'next_processing_date', 'last_processed_at',
                'last_successful_payment_at', 'last_failed_payment_at',
                'total_payments_processed', 'successful_payments_count',
                'failed_payments_count', 'total_amount_processed',
                'total_fees_paid', 'consecutive_failures', 'failure_reasons',
                'requires_confirmation', 'daily_limit', 'monthly_limit',
                'risk_rules', 'velocity_checks', 'client_can_pause',
                'client_can_modify', 'client_can_cancel',
                'requires_client_approval', 'currency_code',
                'currency_conversion_allowed', 'activated_at'
            ]);
            
            $table->string('name')->after('company_id');
            $table->string('status')->default('active')->after('name');
        });
    }
};
