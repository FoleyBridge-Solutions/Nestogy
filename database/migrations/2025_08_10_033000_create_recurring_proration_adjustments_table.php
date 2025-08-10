<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Recurring Proration Adjustments Table
 * 
 * Tracks mid-cycle service changes and their proration calculations
 * for accurate billing when services are added, removed, or modified.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recurring_proration_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('recurring_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            
            // Adjustment details
            $table->string('adjustment_type', 50); // addition, removal, modification, credit, debit
            $table->string('service_type', 50)->nullable(); // What service was adjusted
            $table->text('description');
            $table->text('reason')->nullable(); // Why the adjustment was made
            
            // Date ranges
            $table->date('effective_date'); // When the change took effect
            $table->date('period_start'); // Billing period start
            $table->date('period_end'); // Billing period end
            $table->integer('total_days'); // Total days in period
            $table->integer('prorated_days'); // Days to be charged/credited
            
            // Amount calculations
            $table->decimal('original_amount', 12, 2)->default(0); // Original service amount
            $table->decimal('prorated_amount', 12, 2); // Calculated proration amount
            $table->decimal('adjustment_amount', 12, 2); // Final adjustment (positive or negative)
            $table->decimal('daily_rate', 8, 4)->default(0); // Amount per day
            
            // Proration method used
            $table->enum('proration_method', ['daily', 'monthly', 'none'])->default('daily');
            $table->decimal('proration_percentage', 5, 2)->default(0); // Percentage of period
            
            // Service configuration snapshot
            $table->json('service_config_before')->nullable(); // Configuration before change
            $table->json('service_config_after')->nullable(); // Configuration after change
            $table->json('pricing_snapshot')->nullable(); // Pricing at time of adjustment
            
            // Processing status
            $table->enum('status', ['pending', 'applied', 'invoiced', 'reversed', 'cancelled'])
                  ->default('pending');
            $table->datetime('applied_at')->nullable();
            $table->datetime('invoiced_at')->nullable();
            
            // Approval workflow
            $table->unsignedBigInteger('requested_by')->nullable(); // User who requested
            $table->unsignedBigInteger('approved_by')->nullable(); // User who approved
            $table->datetime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Tax implications
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->json('tax_breakdown')->nullable();
            
            // References and tracking
            $table->string('reference_number', 50)->nullable(); // Internal tracking number
            $table->string('external_reference', 100)->nullable(); // External system reference
            $table->unsignedBigInteger('parent_adjustment_id')->nullable(); // For reversals
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('recurring_id')->references('id')->on('recurring')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_adjustment_id')->references('id')->on('recurring_proration_adjustments')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['recurring_id', 'effective_date'], 'proration_recurring_date_idx');
            $table->index(['client_id', 'status'], 'proration_client_status_idx');
            $table->index(['invoice_id'], 'proration_invoice_idx');
            $table->index(['status', 'applied_at'], 'proration_status_applied_idx');
            $table->index(['adjustment_type', 'effective_date'], 'proration_type_date_idx');
            $table->index('reference_number', 'proration_reference_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_proration_adjustments');
    }
};