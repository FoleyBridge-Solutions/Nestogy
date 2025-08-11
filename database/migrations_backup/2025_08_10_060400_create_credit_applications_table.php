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
        Schema::create('credit_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('applied_by');
            $table->unsignedBigInteger('reversed_by')->nullable();
            
            // Application identification
            $table->string('application_number', 50);
            $table->string('reference', 100)->nullable();
            
            // Application type and method
            $table->enum('application_type', [
                'automatic', 'manual', 'partial', 'full',
                'oldest_first', 'specific_invoice', 'future_invoices'
            ])->default('manual');
            
            $table->enum('application_method', [
                'direct_application', 'account_credit', 'prepayment',
                'future_billing_credit', 'proration_adjustment'
            ])->default('direct_application');
            
            // Status tracking
            $table->enum('status', [
                'pending', 'applied', 'partially_applied', 
                'reversed', 'expired', 'failed'
            ])->default('pending');
            
            // Financial details
            $table->decimal('applied_amount', 15, 2);
            $table->decimal('remaining_amount', 15, 2)->default(0.00);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1.000000);
            
            // Tax application details
            $table->decimal('tax_applied_amount', 15, 2)->default(0.00);
            $table->json('tax_application_breakdown')->nullable();
            $table->boolean('applies_to_tax')->default(true);
            
            // Invoice application details
            $table->decimal('invoice_balance_before', 15, 2)->nullable();
            $table->decimal('invoice_balance_after', 15, 2)->nullable();
            $table->boolean('invoice_fully_credited')->default(false);
            $table->decimal('invoice_credit_percentage', 8, 4)->default(0.0000);
            
            // Line item application (for partial applications)
            $table->json('line_item_applications')->nullable(); // Array of invoice_item_id => amount
            $table->boolean('applies_to_specific_items')->default(false);
            
            // Timing and dates
            $table->date('application_date');
            $table->datetime('applied_at')->nullable();
            $table->datetime('reversed_at')->nullable();
            $table->date('effective_date')->nullable(); // For future billing credits
            
            // Reversal information
            $table->boolean('is_reversed')->default(false);
            $table->text('reversal_reason')->nullable();
            $table->decimal('reversal_amount', 15, 2)->nullable();
            $table->json('reversal_details')->nullable();
            
            // Automatic application rules
            $table->json('application_rules')->nullable(); // Rules for automatic application
            $table->boolean('auto_apply_to_future')->default(false);
            $table->integer('priority')->default(100); // Priority for automatic application
            
            // GL and accounting integration
            $table->string('debit_gl_account', 50)->nullable();
            $table->string('credit_gl_account', 50)->nullable();
            $table->json('gl_entries')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->datetime('gl_posted_at')->nullable();
            
            // Revenue recognition impact
            $table->boolean('affects_revenue_recognition')->default(true);
            $table->json('revenue_impact')->nullable();
            $table->decimal('revenue_adjustment', 15, 2)->default(0.00);
            
            // Proration and period adjustments
            $table->boolean('is_prorated')->default(false);
            $table->json('proration_details')->nullable();
            $table->date('service_period_start')->nullable();
            $table->date('service_period_end')->nullable();
            
            // Customer notification
            $table->boolean('customer_notified')->default(false);
            $table->datetime('notification_sent_at')->nullable();
            $table->json('notification_details')->nullable();
            
            // Workflow and approval
            $table->boolean('requires_approval')->default(false);
            $table->boolean('approved')->default(true);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Integration and external systems
            $table->string('external_id', 100)->nullable();
            $table->json('external_references')->nullable();
            $table->string('source_system', 50)->default('manual');
            
            // Error handling and retry logic
            $table->integer('retry_count')->default(0);
            $table->datetime('next_retry_at')->nullable();
            $table->json('error_log')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Metadata and notes
            $table->text('application_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['credit_note_id', 'status']);
            $table->index(['invoice_id', 'status']);
            $table->index(['application_date', 'company_id']);
            $table->index(['application_type', 'application_method']);
            $table->index(['auto_apply_to_future', 'priority']);
            $table->index(['is_reversed', 'reversed_at']);
            $table->index(['requires_approval', 'approved']);
            $table->index(['gl_posted', 'gl_posted_at']);
            $table->index(['application_number', 'company_id']);
            $table->index(['external_id']);
            $table->index(['next_retry_at', 'retry_count']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('applied_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_applications');
    }
};