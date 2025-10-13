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
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy - CRITICAL: Required for BelongsToCompany trait
            $table->unsignedBigInteger('company_id')->index();

            // Basic template information
            $table->string('name');
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->string('template_type'); // service_agreement, maintenance, support, etc.
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->string('version', 20)->default('1.0');
            $table->unsignedBigInteger('parent_template_id')->nullable()->index();
            $table->boolean('is_default')->default(false);

            // Template content and variables
            $table->longText('template_content');
            $table->json('variable_fields')->nullable(); // Define variables with types and descriptions
            $table->json('default_values')->nullable(); // Default values for variables
            $table->json('required_fields')->nullable(); // Which fields are required

            // Billing model configuration
            $table->enum('billing_model', ['fixed', 'per_asset', 'per_contact', 'tiered', 'hybrid'])->default('fixed');
            $table->json('pricing_structure')->nullable(); // Complex pricing rules and tiers

            // Asset-based billing configuration
            $table->json('asset_billing_rules')->nullable(); // Per device type pricing
            $table->json('supported_asset_types')->nullable(); // ['workstation', 'server', 'network_device']
            $table->json('asset_service_matrix')->nullable(); // Which services apply to which asset types
            $table->decimal('default_per_asset_rate', 10, 2)->nullable();

            // Contact-based billing configuration
            $table->json('contact_billing_rules')->nullable(); // Per seat pricing
            $table->json('contact_access_tiers')->nullable(); // Different access levels = different pricing
            $table->decimal('default_per_contact_rate', 10, 2)->nullable();

            // MSP-specific features
            $table->json('voip_service_types')->nullable(); // VoIP-specific service configurations
            $table->json('default_sla_terms')->nullable(); // Service level agreement defaults
            $table->json('default_pricing_structure')->nullable(); // Default pricing models

            // Compliance and legal
            $table->json('compliance_templates')->nullable(); // Compliance requirements
            $table->json('jurisdictions')->nullable(); // Legal jurisdictions
            $table->json('regulatory_requirements')->nullable(); // Regulatory compliance needs
            $table->text('legal_disclaimers')->nullable();

            // Advanced automation features
            $table->json('calculation_formulas')->nullable(); // Dynamic pricing calculations
            $table->json('auto_assignment_rules')->nullable(); // Auto-assign services to assets/contacts
            $table->json('billing_triggers')->nullable(); // When to generate invoices
            $table->json('workflow_automation')->nullable(); // Workflow state machine definitions
            $table->json('notification_triggers')->nullable(); // When to send notifications
            $table->json('integration_hooks')->nullable(); // External system connections

            // Customization options
            $table->json('customization_options')->nullable(); // Available customizations
            $table->json('conditional_clauses')->nullable(); // Conditional contract terms
            $table->json('pricing_models')->nullable(); // Different pricing approaches

            // Usage and performance tracking
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->decimal('success_rate', 5, 2)->nullable(); // Contract success rate

            // Approval workflow
            $table->boolean('requires_approval')->default(false);
            $table->json('approval_workflow')->nullable(); // Approval chain definition
            $table->timestamp('last_reviewed_at')->nullable();
            $table->date('next_review_date')->nullable();

            // Metadata and rendering
            $table->json('metadata')->nullable(); // Additional flexible data storage
            $table->json('rendering_options')->nullable(); // PDF/document rendering options
            $table->json('signature_settings')->nullable(); // Digital signature configuration

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            // Timestamps
            $table->timestamps();

            // Soft deletes using archived_at (consistent with other models)
            $table->timestamp('archived_at')->nullable()->index();

            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('parent_template_id')
                ->references('id')
                ->on('contract_templates')
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

            // Composite indexes for performance
            $table->index(['company_id', 'status']); // Active templates by company
            $table->index(['company_id', 'template_type']); // Templates by type
            $table->index(['company_id', 'billing_model']); // Templates by billing model
            $table->index(['company_id', 'archived_at']); // Soft delete queries
            $table->index(['template_type', 'status']); // Global template searches
            $table->index(['next_review_date']); // Templates needing review

            // Unique constraints
            $table->unique(['company_id', 'slug']); // Unique slug within company
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_templates');
    }
};
