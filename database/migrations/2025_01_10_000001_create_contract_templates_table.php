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
            
            // Company and identification
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Template categorization
            $table->enum('template_type', [
                'service_agreement',
                'equipment_lease',
                'installation_contract',
                'maintenance_agreement',
                'sla_contract',
                'international_service',
                'master_service',
                'data_processing',
                'professional_services',
                'support_contract'
            ]);
            
            $table->string('category', 100)->nullable();
            $table->json('tags')->nullable();
            
            // Template status and versioning
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->string('version', 20)->default('1.0');
            $table->unsignedBigInteger('parent_template_id')->nullable();
            $table->boolean('is_default')->default(false);
            
            // Template content
            $table->longText('template_content');
            $table->json('variable_fields')->nullable(); // Defines replaceable fields
            $table->json('default_values')->nullable(); // Default values for variables
            $table->json('required_fields')->nullable(); // Required fields that must be filled
            
            // VoIP-specific template configuration
            $table->json('voip_service_types')->nullable(); // Applicable VoIP services
            $table->json('default_sla_terms')->nullable(); // Default SLA terms
            $table->json('default_pricing_structure')->nullable();
            $table->json('compliance_templates')->nullable(); // Compliance clause templates
            
            // Legal and regulatory
            $table->json('jurisdictions')->nullable(); // Applicable jurisdictions
            $table->json('regulatory_requirements')->nullable();
            $table->text('legal_disclaimers')->nullable();
            
            // Template customization options
            $table->json('customization_options')->nullable();
            $table->json('conditional_clauses')->nullable(); // Clauses that appear based on conditions
            $table->json('pricing_models')->nullable(); // Supported pricing models
            
            // Usage and analytics
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->decimal('success_rate', 5, 2)->nullable(); // Percentage of successful contracts
            
            // Approval and review
            $table->boolean('requires_approval')->default(false);
            $table->json('approval_workflow')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->date('next_review_date')->nullable();
            
            // Metadata and settings
            $table->json('metadata')->nullable();
            $table->json('rendering_options')->nullable(); // PDF generation options
            $table->json('signature_settings')->nullable(); // Digital signature configuration
            
            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            
            // Standard Laravel timestamps
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'template_type']);
            $table->index(['company_id', 'is_default']);
            $table->index('parent_template_id');
            $table->index('last_used_at');
            
            // Unique constraints
            $table->unique(['company_id', 'slug']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_template_id')->references('id')->on('contract_templates')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
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