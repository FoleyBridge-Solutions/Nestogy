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
        Schema::table('contracts', function (Blueprint $table) {
            // Billing model configuration - Enhanced for programmable contracts
            $table->enum('billing_model', ['fixed', 'per_asset', 'per_contact', 'tiered', 'hybrid'])->default('fixed')->after('contract_value');
            $table->json('pricing_structure')->nullable()->after('billing_model'); // Complex pricing rules and tiers
            
            // Asset-based billing configuration
            $table->json('asset_billing_rules')->nullable()->after('pricing_structure'); // Per device type pricing
            $table->json('supported_asset_types')->nullable()->after('asset_billing_rules'); // ['workstation', 'server', 'network_device']
            $table->decimal('default_per_asset_rate', 10, 2)->nullable()->after('supported_asset_types');
            
            // Contact-based billing configuration  
            $table->json('contact_billing_rules')->nullable()->after('default_per_asset_rate'); // Per seat pricing
            $table->json('contact_access_tiers')->nullable()->after('contact_billing_rules'); // Different access levels = different pricing
            $table->decimal('default_per_contact_rate', 10, 2)->nullable()->after('contact_access_tiers');
            
            // Advanced automation features
            $table->json('calculation_formulas')->nullable()->after('default_per_contact_rate'); // Dynamic pricing calculations
            $table->json('auto_assignment_rules')->nullable()->after('calculation_formulas'); // Auto-assign services to assets/contacts
            $table->json('billing_triggers')->nullable()->after('auto_assignment_rules'); // When to generate invoices
            $table->json('workflow_automation')->nullable()->after('billing_triggers'); // Workflow state machine definitions
            $table->json('notification_triggers')->nullable()->after('workflow_automation'); // When to send notifications
            
            // Usage tracking and analytics
            $table->integer('total_assigned_assets')->default(0)->after('notification_triggers');
            $table->integer('total_assigned_contacts')->default(0)->after('total_assigned_assets');
            $table->decimal('monthly_usage_charges', 10, 2)->default(0.00)->after('total_assigned_contacts');
            $table->decimal('asset_billing_total', 10, 2)->default(0.00)->after('monthly_usage_charges');
            $table->decimal('contact_billing_total', 10, 2)->default(0.00)->after('asset_billing_total');
            $table->timestamp('last_billing_calculation')->nullable()->after('contact_billing_total');
            $table->date('next_billing_date')->nullable()->after('last_billing_calculation');
            
            // Automation status and control
            $table->boolean('is_programmable')->default(false)->after('next_billing_date')->index();
            $table->boolean('auto_calculate_billing')->default(true)->after('is_programmable');
            $table->boolean('auto_generate_invoices')->default(false)->after('auto_calculate_billing');
            $table->json('automation_settings')->nullable()->after('auto_generate_invoices'); // Automation configuration
            
            // Performance and optimization
            $table->boolean('requires_manual_review')->default(false)->after('automation_settings');
            $table->json('calculation_cache')->nullable()->after('requires_manual_review'); // Cache complex calculations
            $table->timestamp('cache_expires_at')->nullable()->after('calculation_cache');
            
            // Contract template relationship
            $table->unsignedBigInteger('contract_template_id')->nullable()->after('cache_expires_at')->index();
            $table->string('template_version', 20)->nullable()->after('contract_template_id'); // Track template version used
            
            // Add foreign key constraint for contract template
            $table->foreign('contract_template_id')
                ->references('id')
                ->on('contract_templates')
                ->onDelete('set null');
            
            // Add indexes for performance
            $table->index(['billing_model'], 'contracts_billing_model_idx');
            $table->index(['is_programmable', 'status'], 'contracts_programmable_status_idx');
            $table->index(['auto_calculate_billing'], 'contracts_auto_calculate_idx');
            $table->index(['next_billing_date'], 'contracts_next_billing_idx');
            $table->index(['contract_template_id'], 'contracts_template_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['contract_template_id']);
            
            // Drop indexes
            $table->dropIndex('contracts_billing_model_idx');
            $table->dropIndex('contracts_programmable_status_idx');
            $table->dropIndex('contracts_auto_calculate_idx');
            $table->dropIndex('contracts_next_billing_idx');
            $table->dropIndex('contracts_template_id_idx');
            
            // Drop columns
            $table->dropColumn([
                'billing_model',
                'pricing_structure',
                'asset_billing_rules',
                'supported_asset_types',
                'default_per_asset_rate',
                'contact_billing_rules',
                'contact_access_tiers',
                'default_per_contact_rate',
                'calculation_formulas',
                'auto_assignment_rules',
                'billing_triggers',
                'workflow_automation',
                'notification_triggers',
                'total_assigned_assets',
                'total_assigned_contacts',
                'monthly_usage_charges',
                'asset_billing_total',
                'contact_billing_total',
                'last_billing_calculation',
                'next_billing_date',
                'is_programmable',
                'auto_calculate_billing',
                'auto_generate_invoices',
                'automation_settings',
                'requires_manual_review',
                'calculation_cache',
                'cache_expires_at',
                'contract_template_id',
                'template_version',
            ]);
        });
    }
};