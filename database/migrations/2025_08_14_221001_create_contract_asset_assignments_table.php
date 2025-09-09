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
        Schema::create('contract_asset_assignments', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy - CRITICAL: Required for BelongsToCompany trait
            $table->unsignedBigInteger('company_id')->index();
            
            // Core relationships
            $table->unsignedBigInteger('contract_id')->index();
            $table->unsignedBigInteger('asset_id')->index();
            
            // Service assignment configuration
            $table->json('assigned_services')->nullable(); // Array of service IDs/names assigned to this asset
            $table->json('service_pricing')->nullable(); // Custom pricing for services on this specific asset
            $table->decimal('billing_rate', 10, 2)->default(0.00); // Base rate for this asset
            $table->enum('billing_frequency', ['monthly', 'quarterly', 'annually', 'one_time'])->default('monthly');
            
            // Asset-specific service configuration
            $table->json('service_configuration')->nullable(); // Asset-specific service settings
            $table->json('monitoring_settings')->nullable(); // Monitoring configuration for this asset
            $table->json('maintenance_schedule')->nullable(); // Maintenance schedule for this asset
            $table->json('backup_configuration')->nullable(); // Backup settings if applicable
            
            // Pricing and billing details
            $table->decimal('base_monthly_rate', 10, 2)->default(0.00);
            $table->decimal('additional_service_charges', 10, 2)->default(0.00);
            $table->json('pricing_modifiers')->nullable(); // Discounts, surcharges, etc.
            $table->json('billing_rules')->nullable(); // Specific billing rules for this asset
            
            // Status and lifecycle management
            $table->enum('status', ['active', 'suspended', 'terminated', 'pending'])->default('active')->index();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('last_billed_at')->nullable();
            $table->date('next_billing_date')->nullable();
            
            // Automation and assignment tracking
            $table->boolean('auto_assigned')->default(false);
            $table->json('assignment_rules')->nullable(); // Rules that triggered auto-assignment
            $table->json('automation_triggers')->nullable(); // Triggers for automated actions
            $table->timestamp('last_service_update')->nullable();
            
            // Usage and performance tracking
            $table->json('usage_metrics')->nullable(); // Track usage for billing calculations
            $table->decimal('current_month_charges', 10, 2)->default(0.00);
            $table->json('billing_history')->nullable(); // Historical billing information
            
            // Service level and compliance
            $table->json('sla_requirements')->nullable(); // SLA requirements for this asset
            $table->json('compliance_settings')->nullable(); // Compliance requirements
            $table->json('security_requirements')->nullable(); // Security settings for this asset
            
            // Notes and metadata
            $table->text('assignment_notes')->nullable();
            $table->json('metadata')->nullable(); // Additional flexible data storage
            
            // User tracking
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->onDelete('cascade');
            
            $table->foreign('asset_id')
                ->references('id')
                ->on('assets')
                ->onDelete('cascade');
            
            $table->foreign('assigned_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Indexes for performance
            $table->index(['company_id', 'contract_id']); // Contract assignments by company
            $table->index(['company_id', 'asset_id']); // Asset assignments by company
            $table->index(['contract_id', 'status']); // Active assignments per contract
            $table->index(['asset_id', 'status']); // Asset assignment status
            $table->index(['next_billing_date']); // Billing schedule queries
            $table->index(['billing_frequency', 'status']); // Billing frequency analysis
            $table->index(['start_date', 'end_date']); // Date range queries
            $table->index(['auto_assigned']); // Auto-assignment tracking
            
            // Unique constraints
            $table->unique(['contract_id', 'asset_id'], 'unique_contract_asset_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_asset_assignments');
    }
};