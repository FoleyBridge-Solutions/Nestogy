<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Recurring Contract Escalations Table
 * 
 * Tracks contract price escalations and automatic price increases
 * over time based on predefined terms and schedules.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recurring_contract_escalations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('recurring_id');
            $table->unsignedBigInteger('client_id');
            
            // Escalation details
            $table->datetime('escalation_date'); // When the escalation occurred
            $table->datetime('effective_date'); // When the new pricing takes effect
            $table->string('escalation_type', 50)->default('percentage'); // percentage, fixed_amount, tier_change
            
            // Amount changes
            $table->decimal('old_amount', 12, 2);
            $table->decimal('new_amount', 12, 2);
            $table->decimal('escalation_amount', 12, 2); // Actual increase amount
            $table->decimal('escalation_percentage', 5, 2)->nullable(); // Percentage increase
            
            // Service-specific escalations
            $table->string('service_type', 50)->nullable(); // Which service was escalated
            $table->json('service_changes')->nullable(); // Detailed service changes
            $table->json('old_pricing_model')->nullable(); // Pricing before escalation
            $table->json('new_pricing_model')->nullable(); // Pricing after escalation
            
            // Escalation schedule
            $table->string('schedule_type', 50)->default('fixed'); // fixed, cpi_based, market_rate
            $table->datetime('next_escalation_date')->nullable();
            $table->integer('escalation_interval_months')->nullable(); // How often to escalate
            $table->decimal('max_annual_increase', 5, 2)->nullable(); // Cap on annual increases
            
            // Economic indicators (for CPI-based escalations)
            $table->decimal('cpi_index', 8, 4)->nullable(); // Consumer Price Index
            $table->string('cpi_period', 20)->nullable(); // Period for CPI calculation
            $table->decimal('market_rate', 8, 4)->nullable(); // Market-based rate
            $table->string('market_rate_source', 100)->nullable(); // Source of market rate
            
            // Approval and notification
            $table->unsignedBigInteger('approved_by')->nullable(); // User who approved
            $table->datetime('approved_at')->nullable();
            $table->boolean('client_notified')->default(false);
            $table->datetime('client_notified_at')->nullable();
            $table->integer('notification_days_advance')->default(30); // Days notice given
            
            // Processing status
            $table->enum('status', ['scheduled', 'pending_approval', 'approved', 'applied', 'disputed', 'cancelled'])
                  ->default('applied');
            $table->datetime('applied_at')->nullable();
            $table->text('notes')->nullable();
            
            // Contract terms
            $table->string('contract_reference', 100)->nullable(); // Contract clause reference
            $table->text('escalation_clause')->nullable(); // Specific contract language
            $table->json('terms_and_conditions')->nullable(); // T&C snapshot
            
            // Dispute handling
            $table->boolean('disputed')->default(false);
            $table->text('dispute_reason')->nullable();
            $table->datetime('dispute_date')->nullable();
            $table->enum('dispute_status', ['pending', 'resolved', 'upheld', 'reversed'])->nullable();
            
            // Financial impact
            $table->decimal('annual_impact', 12, 2)->nullable(); // Expected annual revenue impact
            $table->decimal('cumulative_impact', 12, 2)->default(0); // Total impact over time
            $table->json('impact_analysis')->nullable(); // Detailed impact breakdown
            
            // External references
            $table->string('external_reference', 100)->nullable();
            $table->json('integration_data')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('recurring_id')->references('id')->on('recurring')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['recurring_id', 'escalation_date'], 'escalation_recurring_date_idx');
            $table->index(['client_id', 'effective_date'], 'escalation_client_effective_idx');
            $table->index(['status', 'next_escalation_date'], 'escalation_status_next_idx');
            $table->index(['escalation_date', 'escalation_amount'], 'escalation_date_amount_idx');
            $table->index('service_type', 'escalation_service_type_idx');
            $table->index(['disputed', 'dispute_status'], 'escalation_dispute_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_contract_escalations');
    }
};