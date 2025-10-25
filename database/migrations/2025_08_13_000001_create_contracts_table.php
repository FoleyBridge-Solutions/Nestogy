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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy - CRITICAL: Required for BelongsToCompany trait
            $table->unsignedBigInteger('company_id')->index();

            // Contract identification
            $table->string('contract_number')->unique();
            $table->string('contract_type'); // e.g., 'service_agreement', 'maintenance', 'support', etc.
            $table->string('title');
            $table->text('description')->nullable();

            // Contract status - Required by ClientPortalController
            $table->string('status')->default('draft')->index();
            // Common statuses: draft, pending, active, suspended, expired, terminated, completed

            // Client relationship - Required by ClientPortalController
            $table->unsignedBigInteger('client_id')->index();

            // Financial fields - Required for contract_value summing in controller
            $table->decimal('contract_value', 10, 2)->default(0.00);
            $table->string('currency_code', 3)->default('USD');
            $table->string('payment_terms')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);

            // Contract dates
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('term_months')->nullable();
            $table->date('signed_date')->nullable();

            // Contract details
            $table->text('terms_and_conditions')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->json('deliverables')->nullable(); // Store array of deliverables
            $table->json('metadata')->nullable(); // Additional flexible data storage

            // Renewal information
            $table->boolean('auto_renew')->default(false);
            $table->unsignedInteger('renewal_notice_days')->nullable();
            $table->date('renewal_date')->nullable();
            $table->string('renewal_type')->nullable();

            // References to other entities
            $table->unsignedBigInteger('quote_id')->nullable()->index();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            // Document management
            $table->string('document_path')->nullable(); // Path to contract PDF/document
            $table->string('template_used')->nullable(); // Template name if generated from template

            // Workflow and approval
            $table->boolean('requires_approval')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Performance and SLA
            $table->json('sla_terms')->nullable(); // Service level agreement details
            $table->json('performance_metrics')->nullable(); // KPIs and metrics

            // Timestamps
            $table->timestamps();
            $table->enum('billing_model', ['fixed', 'per_asset', 'per_contact', 'tiered', 'hybrid'])->default('fixed');
            $table->json('pricing_structure')->nullable();
            $table->json('asset_billing_rules')->nullable();
            $table->json('supported_asset_types')->nullable();
            $table->decimal('default_per_asset_rate', 10, 2)->nullable();
            $table->json('contact_billing_rules')->nullable();
            $table->json('contact_access_tiers')->nullable();
            $table->string('signature_status')->default('pending')->index();
            $table->boolean('is_programmable')->default(false)->index();
            $table->unsignedBigInteger('contract_template_id')->nullable()->index();
            $table->index(['billing_model'], 'contracts_billing_model_idx');
            $table->index(['is_programmable', 'status'], 'contracts_programmable_status_idx');
            $table->index(['auto_calculate_billing'], 'contracts_auto_calculate_idx');
            $table->index(['next_billing_date'], 'contracts_next_billing_idx');
            $table->index(['contract_template_id'], 'contracts_template_id_idx');
            $table->index('template_id');
            $table->decimal('default_per_contact_rate', 10, 2)->nullable();
            $table->json('calculation_formulas')->nullable();
            $table->json('auto_assignment_rules')->nullable();
            $table->json('billing_triggers')->nullable();
            $table->json('workflow_automation')->nullable();
            $table->json('notification_triggers')->nullable();
            $table->integer('total_assigned_assets')->default(0);
            $table->integer('total_assigned_contacts')->default(0);
            $table->decimal('monthly_usage_charges', 10, 2)->default(0.00);
            $table->decimal('asset_billing_total', 10, 2)->default(0.00);
            $table->decimal('contact_billing_total', 10, 2)->default(0.00);
            $table->timestamp('last_billing_calculation')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->boolean('auto_calculate_billing')->default(true);
            $table->boolean('auto_generate_invoices')->default(false);
            $table->json('automation_settings')->nullable();
            $table->boolean('requires_manual_review')->default(false);
            $table->json('calculation_cache')->nullable();
            $table->timestamp('cache_expires_at')->nullable();
            $table->string('template_version', 20)->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->json('custom_clauses')->nullable();
            $table->string('dispute_resolution')->nullable();
            $table->string('governing_law')->nullable();
            $table->longText('content')->nullable();
            $table->json('variables')->nullable();

            // Soft deletes - Using archived_at as seen in ClientPortalController
            $table->timestamp('archived_at')->nullable()->index();

            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->foreign('quote_id')
                ->references('id')
                ->on('quotes')
                ->onDelete('set null');

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Composite indexes for performance
            $table->index(['company_id', 'status']); // For filtering active contracts by company
            $table->index(['company_id', 'client_id']); // For client-specific contract queries
            $table->index(['company_id', 'contract_type']); // For filtering by type
            $table->index(['start_date', 'end_date']); // For date range queries
            $table->index(['company_id', 'archived_at']); // For soft delete queries

            // Unique constraint for contract number within a company
            $table->unique(['company_id', 'contract_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
