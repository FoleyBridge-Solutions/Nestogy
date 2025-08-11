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
        Schema::create('client_recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'bi_weekly', 'monthly', 'bi_monthly', 'quarterly', 'semi_annually', 'annually'])->default('monthly');
            $table->integer('frequency_count')->default(1); // Every X periods
            $table->json('frequency_days')->nullable(); // Specific days for weekly/monthly
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_invoice_date');
            $table->date('last_invoice_date')->nullable();
            $table->integer('max_invoices')->nullable(); // Maximum number of invoices to generate
            $table->integer('invoices_generated')->default(0);
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled', 'expired'])->default('active');
            $table->boolean('auto_send')->default(true);
            $table->integer('send_days_before')->default(0); // Days before due date to send
            $table->json('line_items'); // Array of line items with description, quantity, rate
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->integer('payment_terms')->default(30); // Net days
            $table->text('payment_instructions')->nullable();
            $table->json('accepted_payment_methods')->nullable();
            $table->string('payment_processor')->nullable();
            $table->boolean('late_fees_enabled')->default(false);
            $table->decimal('late_fee_percentage', 5, 2)->default(0);
            $table->decimal('late_fee_fixed_amount', 10, 2)->default(0);
            $table->integer('late_fee_grace_days')->default(0);
            $table->json('email_recipients')->nullable(); // Additional email recipients
            $table->string('email_template')->nullable();
            $table->text('invoice_notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('custom_fields')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->json('generated_invoices')->nullable(); // Track generated invoice IDs
            $table->decimal('total_revenue_generated', 15, 2)->default(0);
            $table->integer('successful_payments')->default(0);
            $table->integer('failed_payments')->default(0);
            $table->decimal('success_rate_percentage', 5, 2)->nullable();
            $table->timestamp('last_success')->nullable();
            $table->timestamp('last_failure')->nullable();
            $table->json('failure_reasons')->nullable();
            $table->json('payment_history')->nullable();
            $table->boolean('proration_enabled')->default(false);
            $table->enum('proration_method', ['daily', 'monthly'])->nullable();
            $table->json('pricing_tiers')->nullable(); // Volume-based pricing
            $table->boolean('quantity_tracking')->default(false);
            $table->json('usage_metrics')->nullable(); // For usage-based billing
            $table->string('billing_cycle_anchor')->nullable(); // Specific day of month
            $table->json('invoice_customization')->nullable(); // Logo, colors, etc.
            $table->string('pdf_template')->nullable();
            $table->json('integration_settings')->nullable(); // Accounting software sync
            $table->string('external_id')->nullable(); // External system reference
            $table->timestamp('last_sync')->nullable();
            $table->json('sync_errors')->nullable();
            $table->json('notification_settings')->nullable();
            $table->json('webhook_urls')->nullable();
            $table->json('alerts')->nullable();
            $table->text('processing_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['status', 'company_id']);
            $table->index('invoice_number');
            $table->index('frequency');
            $table->index('next_invoice_date');
            $table->index(['status', 'next_invoice_date']);
            $table->index('requires_approval');
            $table->index('approval_status');
            $table->index(['auto_send', 'next_invoice_date']);
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_recurring_invoices');
    }
};