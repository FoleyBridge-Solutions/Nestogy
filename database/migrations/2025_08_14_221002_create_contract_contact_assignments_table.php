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
        Schema::create('contract_contact_assignments', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy - CRITICAL: Required for BelongsToCompany trait
            $table->unsignedBigInteger('company_id')->index();
            
            // Core relationships
            $table->unsignedBigInteger('contract_id')->index();
            $table->unsignedBigInteger('contact_id')->index();
            
            // Access level and tier configuration
            $table->enum('access_level', ['basic', 'standard', 'premium', 'admin', 'custom'])->default('basic');
            $table->string('access_tier_name')->nullable(); // Custom tier name
            $table->json('assigned_permissions')->nullable(); // Portal permissions for this contact
            $table->json('service_entitlements')->nullable(); // What services this contact can access
            
            // Portal and ticket access configuration
            $table->boolean('has_portal_access')->default(true);
            $table->boolean('can_create_tickets')->default(true);
            $table->boolean('can_view_all_tickets')->default(false); // Or just their own
            $table->boolean('can_view_assets')->default(false);
            $table->boolean('can_view_invoices')->default(false);
            $table->boolean('can_download_files')->default(false);
            
            // Usage limits and restrictions
            $table->integer('max_tickets_per_month')->default(-1); // -1 = unlimited
            $table->integer('max_support_hours_per_month')->default(-1);
            $table->json('allowed_ticket_types')->nullable(); // Which ticket types they can create
            $table->json('restricted_features')->nullable(); // Features they cannot access
            
            // Billing configuration
            $table->decimal('billing_rate', 10, 2)->default(0.00); // Rate for this contact/seat
            $table->enum('billing_frequency', ['monthly', 'quarterly', 'annually', 'per_ticket'])->default('monthly');
            $table->decimal('per_ticket_rate', 10, 2)->default(0.00); // If billing per ticket
            $table->json('pricing_modifiers')->nullable(); // Discounts, surcharges based on usage
            
            // Status and lifecycle management
            $table->enum('status', ['active', 'suspended', 'terminated', 'pending'])->default('active')->index();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('last_billed_at')->nullable();
            $table->date('next_billing_date')->nullable();
            
            // Usage tracking
            $table->integer('current_month_tickets')->default(0);
            $table->decimal('current_month_support_hours', 8, 2)->default(0.00);
            $table->decimal('current_month_charges', 10, 2)->default(0.00);
            $table->date('last_access_date')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('total_tickets_created')->default(0);
            
            // Automation and assignment tracking
            $table->boolean('auto_assigned')->default(false);
            $table->json('assignment_criteria')->nullable(); // What criteria triggered assignment
            $table->json('automation_rules')->nullable(); // Automated rules for this contact
            $table->boolean('auto_upgrade_tier')->default(false); // Auto-upgrade based on usage
            
            // Service level configuration
            $table->json('sla_entitlements')->nullable(); // SLA levels this contact is entitled to
            $table->enum('priority_level', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->json('escalation_rules')->nullable(); // Custom escalation for this contact
            $table->json('notification_preferences')->nullable(); // How they want to be notified
            
            // Communication and collaboration
            $table->boolean('can_collaborate_with_team')->default(true);
            $table->json('collaboration_settings')->nullable(); // Who they can collaborate with
            $table->boolean('receives_service_updates')->default(true);
            $table->boolean('receives_maintenance_notifications')->default(true);
            
            // Security and compliance
            $table->json('security_requirements')->nullable(); // Special security requirements
            $table->json('compliance_settings')->nullable(); // Compliance requirements for this contact
            $table->json('data_access_restrictions')->nullable(); // What data they can/cannot see
            $table->boolean('requires_mfa')->default(false);
            
            // Billing history and metrics
            $table->json('usage_history')->nullable(); // Historical usage data
            $table->json('billing_history')->nullable(); // Historical billing information
            $table->decimal('lifetime_value', 10, 2)->default(0.00); // Total value from this contact
            $table->decimal('average_monthly_usage', 8, 2)->default(0.00); // Average usage metrics
            
            // Notes and metadata
            $table->text('assignment_notes')->nullable();
            $table->json('metadata')->nullable(); // Additional flexible data storage
            $table->json('custom_fields')->nullable(); // Client-specific custom fields
            
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
            
            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
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
            $table->index(['company_id', 'contact_id']); // Contact assignments by company
            $table->index(['contract_id', 'status']); // Active assignments per contract
            $table->index(['contact_id', 'status']); // Contact assignment status
            $table->index(['access_level']); // Access level filtering
            $table->index(['next_billing_date']); // Billing schedule queries
            $table->index(['billing_frequency', 'status']); // Billing frequency analysis
            $table->index(['start_date', 'end_date']); // Date range queries
            $table->index(['auto_assigned']); // Auto-assignment tracking
            $table->index(['last_access_date']); // Activity tracking
            $table->index(['priority_level']); // Priority level queries
            
            // Unique constraints
            $table->unique(['contract_id', 'contact_id'], 'unique_contract_contact_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_contact_assignments');
    }
};