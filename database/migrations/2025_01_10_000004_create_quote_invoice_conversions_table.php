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
            
            // Company reference
            $table->unsignedBigInteger('company_id')->index();
            
            // Source and target references
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            
            // Conversion configuration
            $table->enum('conversion_type', [
                'direct_invoice',           // Quote → Invoice directly
                'contract_with_invoice',    // Quote → Contract → Invoice
                'milestone_invoicing',      // Quote → Contract → Multiple Invoices
                'recurring_setup',          // Quote → Contract → Recurring Invoices
                'hybrid_conversion'         // Mixed approach
            ]);
            
            $table->enum('status', [
                'pending',
                'processing',
                'contract_generated',
                'contract_signed',
                'invoice_generated',
                'recurring_setup',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending');
            
            // Conversion settings and preferences
            $table->json('conversion_settings')->nullable(); // User preferences for conversion
            $table->json('pricing_adjustments')->nullable(); // Any pricing changes during conversion
            $table->json('tax_calculations')->nullable(); // Preserved tax calculations
            $table->json('milestone_schedule')->nullable(); // For milestone-based invoicing
            $table->json('recurring_schedule')->nullable(); // For recurring invoice setup
            
            // Service activation and provisioning
            $table->boolean('requires_service_activation')->default(false);
            $table->json('service_activation_data')->nullable();
            $table->enum('activation_status', [
                'not_required',
                'pending',
                'in_progress',
                'completed',
                'failed'
            ])->default('not_required');
            $table->timestamp('service_activated_at')->nullable();
            
            // VoIP-specific conversion data
            $table->json('voip_service_mapping')->nullable(); // Maps quote services to invoice/contract services
            $table->json('equipment_allocation')->nullable(); // Equipment assignment details
            $table->json('porting_requirements')->nullable(); // Number porting information
            $table->json('compliance_mappings')->nullable(); // Regulatory compliance mappings
            
            // Financial tracking
            $table->decimal('original_quote_value', 15, 2);
            $table->decimal('converted_value', 15, 2)->nullable();
            $table->decimal('adjustment_amount', 15, 2)->default(0);
            $table->text('adjustment_reason')->nullable();
            
            // Currency and exchange rates
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 10, 6)->nullable(); // If currency conversion needed
            $table->timestamp('rate_locked_at')->nullable();
            
            // Revenue recognition
            $table->json('revenue_schedule')->nullable(); // Revenue recognition schedule
            $table->decimal('deferred_revenue', 15, 2)->default(0);
            $table->decimal('recognized_revenue', 15, 2)->default(0);
            
            // Workflow and approval tracking
            $table->json('conversion_workflow')->nullable(); // Steps in the conversion process
            $table->json('approval_chain')->nullable(); // Required approvals
            $table->json('completed_steps')->nullable(); // Completed workflow steps
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->unsignedTinyInteger('total_steps')->default(1);
            
            // Error handling and retry logic
            $table->json('error_log')->nullable(); // Conversion errors
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(3);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            
            // Integration and automation
            $table->json('integration_data')->nullable(); // Data for external systems
            $table->boolean('automated_conversion')->default(false);
            $table->json('automation_rules')->nullable();
            $table->string('batch_id', 50)->nullable(); // For bulk conversions
            
            // Performance and analytics
            $table->timestamp('conversion_started_at')->nullable();
            $table->timestamp('conversion_completed_at')->nullable();
            $table->unsignedInteger('processing_duration')->nullable(); // In seconds
            $table->json('performance_metrics')->nullable();
            
            // Audit and compliance
            $table->json('audit_trail')->nullable();
            $table->json('compliance_checks')->nullable();
            $table->boolean('regulatory_approved')->default(true);
            $table->text('compliance_notes')->nullable();
            
            // User tracking
            $table->unsignedBigInteger('initiated_by');
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            
            // Standard Laravel timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['quote_id', 'conversion_type']);
            $table->index(['conversion_started_at', 'status']);
            $table->index('activation_status');
            $table->index('batch_id');
            $table->index(['currency_code', 'rate_locked_at']);
            $table->index(['retry_count', 'next_retry_at']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('set null');
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
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