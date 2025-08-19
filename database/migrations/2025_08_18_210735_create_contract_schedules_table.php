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
        Schema::create('contract_schedules', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy - CRITICAL: Required for BelongsToCompany trait
            $table->unsignedBigInteger('company_id')->index();
            
            // Core relationships
            $table->unsignedBigInteger('contract_id')->index();
            
            // Schedule identification
            $table->enum('schedule_type', ['A', 'B', 'C', 'D', 'E'])->comment('A=Infrastructure/SLA, B=Pricing, C=Additional Terms, etc.');
            $table->string('schedule_letter', 1)->index(); // A, B, C, etc.
            $table->string('title');
            $table->text('description')->nullable();
            
            // Schedule content and structure
            $table->longText('content'); // Main schedule content/template
            $table->json('variables')->nullable(); // Variable definitions for this schedule
            $table->json('variable_values')->nullable(); // Current variable values
            $table->json('required_fields')->nullable(); // Fields that must be filled
            
            // Infrastructure Schedule (Schedule A) specific fields
            $table->json('supported_asset_types')->nullable(); // Array of supported asset types
            $table->json('service_levels')->nullable(); // Service level definitions
            $table->json('coverage_rules')->nullable(); // Rules for automatic asset assignment
            $table->json('sla_terms')->nullable(); // Service level agreement terms
            $table->json('response_times')->nullable(); // Response time commitments
            $table->json('coverage_hours')->nullable(); // Support hours definition
            $table->json('escalation_procedures')->nullable(); // Escalation workflows
            
            // Pricing Schedule (Schedule B) specific fields  
            $table->json('pricing_structure')->nullable(); // Pricing models and rates
            $table->json('billing_rules')->nullable(); // Billing frequency and rules
            $table->json('rate_tables')->nullable(); // Detailed rate tables
            $table->json('discount_structures')->nullable(); // Volume discounts, etc.
            $table->json('penalty_structures')->nullable(); // SLA penalties
            
            // Asset inclusion/exclusion logic
            $table->json('asset_inclusion_rules')->nullable(); // Rules to auto-include assets
            $table->json('asset_exclusion_rules')->nullable(); // Rules to exclude assets
            $table->json('location_coverage')->nullable(); // Geographic coverage rules
            $table->json('client_tier_requirements')->nullable(); // Client tier restrictions
            
            // Automation and assignment
            $table->boolean('auto_assign_assets')->default(false); // Automatically assign matching assets
            $table->boolean('require_manual_approval')->default(true); // Require approval for assignments
            $table->json('automation_rules')->nullable(); // Automation configuration
            $table->json('assignment_triggers')->nullable(); // What triggers auto-assignment
            
            // Status and workflow
            $table->enum('status', ['draft', 'pending_approval', 'active', 'suspended', 'archived'])->default('draft')->index();
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'changes_requested'])->default('pending');
            $table->text('approval_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            
            // Version control and templates
            $table->string('version', 20)->default('1.0');
            $table->unsignedBigInteger('parent_schedule_id')->nullable(); // For versioning
            $table->unsignedBigInteger('template_id')->nullable(); // Reference to schedule template
            $table->boolean('is_template')->default(false); // Is this a reusable template
            
            // Usage and effectiveness tracking
            $table->integer('asset_count')->default(0); // Current number of covered assets
            $table->integer('usage_count')->default(0); // How many times used as template
            $table->timestamp('last_used_at')->nullable();
            $table->decimal('effectiveness_score', 5, 2)->nullable(); // Performance metric
            
            // Dates and lifecycle
            $table->date('effective_date')->nullable(); // When this schedule becomes effective
            $table->date('expiration_date')->nullable(); // When this schedule expires
            $table->timestamp('last_reviewed_at')->nullable();
            $table->date('next_review_date')->nullable();
            
            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable(); // Flexible additional data
            $table->text('notes')->nullable(); // Internal notes
            
            // Timestamps
            $table->timestamps();
            
            // Soft deletes
            $table->timestamp('archived_at')->nullable()->index();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->onDelete('cascade');
            
            $table->foreign('parent_schedule_id')
                ->references('id')
                ->on('contract_schedules')
                ->onDelete('set null');
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
                
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Indexes for performance
            $table->index(['company_id', 'contract_id']); // Contract schedules by company
            $table->index(['contract_id', 'schedule_type']); // Specific schedule types per contract
            $table->index(['schedule_type', 'status']); // Active schedules by type
            $table->index(['effective_date', 'expiration_date']); // Date range queries
            $table->index(['auto_assign_assets']); // Auto-assignment queries
            $table->index(['is_template']); // Template queries
            $table->index(['next_review_date']); // Review scheduling
            $table->index(['company_id', 'archived_at']); // Soft delete queries
            
            // Unique constraints
            $table->unique(['contract_id', 'schedule_letter'], 'unique_contract_schedule_letter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_schedules');
    }
};