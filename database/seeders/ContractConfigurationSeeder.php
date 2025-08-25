<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Domains\Contract\Services\ContractConfigurationRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Contract Configuration Seeder
 * 
 * Seeds default contract configurations for companies
 */
class ContractConfigurationSeeder extends Seeder
{
    protected ContractConfigurationRegistry $configRegistry;

    public function __construct(ContractConfigurationRegistry $configRegistry)
    {
        $this->configRegistry = $configRegistry;
    }

    /**
     * Run the database seeds
     */
    public function run(): void
    {
        $this->command->info('Seeding contract configurations...');

        // Get all companies that don't have contract configuration
        $companies = Company::whereDoesntHave('contractConfigurations')->get();

        if ($companies->isEmpty()) {
            $this->command->info('All companies already have contract configurations.');
            return;
        }

        foreach ($companies as $company) {
            $this->seedCompanyDefaults($company);
            $this->command->info("Seeded contract configuration for company: {$company->name}");
        }

        $this->command->info("Seeded configurations for {$companies->count()} companies.");
    }

    /**
     * Seed default configuration for a company
     */
    public function seedCompanyDefaults(Company $company): void
    {
        $config = [
            'contract_types' => $this->getDefaultContractTypes($company),
            'statuses' => $this->getDefaultStatuses($company),
            'signature_statuses' => $this->getDefaultSignatureStatuses($company),
            'renewal_types' => $this->getDefaultRenewalTypes($company),
            'field_types' => $this->getDefaultFieldTypes($company),
            'validation_rules' => $this->getDefaultValidationRules($company),
            'workflows' => $this->getDefaultWorkflows($company),
            'templates' => $this->getDefaultTemplates($company),
            'business_rules' => $this->getDefaultBusinessRules($company),
            'active_statuses' => ['active', 'signed'],
            'signed_signature_statuses' => ['fully_executed'],
            'editable_statuses' => ['draft', 'pending_review', 'under_negotiation', 'pending_signature'],
            'terminable_statuses' => ['active', 'signed', 'suspended'],
            'non_renewable_types' => ['none'],
            'defaults' => $this->getDefaultValues($company),
            'notifications' => $this->getDefaultNotifications($company),
            'integrations' => $this->getDefaultIntegrations($company),
        ];

        // Store configuration
        DB::table('contract_configurations')->insert([
            'company_id' => $company->id,
            'configuration' => json_encode($config),
            'is_active' => true,
            'version' => '1.0',
            'description' => 'Default contract configuration',
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // For now, skip the detailed seeding of individual tables
        // This can be added later once the UI admin interfaces are built
        // $this->seedNavigationItems($company);
        // $this->seedFormSections($company);
        // $this->seedViewConfigurations($company);
        // $this->seedDashboardWidgets($company);
        
        $this->command->info("Basic configuration seeded for company: {$company->name}");
    }

    /**
     * Get default contract types based on company industry
     */
    protected function getDefaultContractTypes(Company $company): array
    {
        $baseTypes = [
            'service_agreement' => [
                'name' => 'Service Agreement',
                'description' => 'Standard service agreement for ongoing services',
                'icon' => 'fas fa-handshake',
                'color' => 'primary',
                'billing_model' => 'asset_based',
                'workflow' => 'standard',
                'requires_signature' => true,
                'auto_renewal' => true,
            ],
            'maintenance_contract' => [
                'name' => 'Maintenance Contract',
                'description' => 'Equipment and system maintenance agreement',
                'icon' => 'fas fa-tools',
                'color' => 'warning',
                'billing_model' => 'asset_based',
                'workflow' => 'standard',
                'requires_signature' => true,
                'auto_renewal' => true,
            ],
            'professional_services' => [
                'name' => 'Professional Services',
                'description' => 'Project-based professional services contract',
                'icon' => 'fas fa-user-tie',
                'color' => 'info',
                'billing_model' => 'usage_based',
                'workflow' => 'approval_workflow',
                'requires_signature' => true,
                'auto_renewal' => false,
            ],
            'equipment_lease' => [
                'name' => 'Equipment Lease',
                'description' => 'Hardware leasing agreement',
                'icon' => 'fas fa-laptop',
                'color' => 'success',
                'billing_model' => 'asset_based',
                'workflow' => 'standard',
                'requires_signature' => true,
                'auto_renewal' => false,
            ],
            'sla_contract' => [
                'name' => 'Service Level Agreement',
                'description' => 'Detailed SLA with performance guarantees',
                'icon' => 'fas fa-chart-line',
                'color' => 'danger',
                'billing_model' => 'tiered_asset',
                'workflow' => 'approval_workflow',
                'requires_signature' => true,
                'auto_renewal' => true,
            ],
        ];

        // Add industry-specific types based on company profile
        $industry = $company->industry ?? 'general';
        
        switch ($industry) {
            case 'telecommunications':
            case 'voip':
                $baseTypes['voip_services'] = [
                    'name' => 'VoIP Services',
                    'description' => 'Voice over IP communication services',
                    'icon' => 'fas fa-phone',
                    'color' => 'purple',
                    'billing_model' => 'usage_based',
                    'workflow' => 'standard',
                    'requires_signature' => true,
                    'auto_renewal' => true,
                ];
                break;
                
            case 'compliance':
            case 'security':
                $baseTypes['compliance_services'] = [
                    'name' => 'Compliance Services',
                    'description' => 'Regulatory compliance and audit services',
                    'icon' => 'fas fa-shield-alt',
                    'color' => 'dark',
                    'billing_model' => 'custom_formula',
                    'workflow' => 'approval_workflow',
                    'requires_signature' => true,
                    'auto_renewal' => true,
                ];
                break;
        }

        return $baseTypes;
    }

    /**
     * Get default statuses
     */
    protected function getDefaultStatuses(Company $company): array
    {
        return [
            'draft' => [
                'name' => 'Draft',
                'description' => 'Contract is being prepared',
                'color' => 'secondary',
                'icon' => 'fas fa-edit',
                'is_initial' => true,
                'is_final' => false,
                'transitions' => ['pending_review', 'cancelled'],
                'permissions' => ['edit-contract', 'submit-contract'],
                'auto_transitions' => [],
                'notifications' => ['draft_reminder'],
            ],
            'pending_review' => [
                'name' => 'Pending Review',
                'description' => 'Contract awaiting management review',
                'color' => 'warning',
                'icon' => 'fas fa-clock',
                'is_initial' => false,
                'is_final' => false,
                'transitions' => ['under_negotiation', 'approved', 'draft'],
                'permissions' => ['review-contract'],
                'auto_transitions' => [
                    'timeout' => ['days' => 7, 'to_status' => 'draft', 'notify' => true]
                ],
                'notifications' => ['review_request', 'review_reminder'],
            ],
            'under_negotiation' => [
                'name' => 'Under Negotiation',
                'description' => 'Contract terms being negotiated',
                'color' => 'info',
                'icon' => 'fas fa-handshake',
                'is_initial' => false,
                'is_final' => false,
                'transitions' => ['pending_signature', 'draft', 'cancelled'],
                'permissions' => ['negotiate-contract'],
                'auto_transitions' => [],
                'notifications' => ['negotiation_update'],
            ],
            'approved' => [
                'name' => 'Approved',
                'description' => 'Contract approved and ready for signature',
                'color' => 'success',
                'icon' => 'fas fa-check',
                'is_initial' => false,
                'is_final' => false,
                'transitions' => ['pending_signature', 'signed'],
                'permissions' => ['approve-contract'],
                'auto_transitions' => [
                    'immediate' => ['to_status' => 'pending_signature', 'condition' => 'requires_signature']
                ],
                'notifications' => ['approval_notification'],
            ],
            'pending_signature' => [
                'name' => 'Pending Signature',
                'description' => 'Contract ready for client signature',
                'color' => 'primary',
                'icon' => 'fas fa-signature',
                'is_initial' => false,
                'is_final' => false,
                'transitions' => ['signed', 'under_negotiation'],
                'permissions' => ['send-for-signature'],
                'auto_transitions' => [
                    'timeout' => ['days' => 30, 'to_status' => 'expired', 'notify' => true]
                ],
                'notifications' => ['signature_request', 'signature_reminder'],
            ],
            'signed' => [
                'name' => 'Signed',
                'description' => 'Contract has been signed by all parties',
                'color' => 'success',
                'icon' => 'fas fa-check-double',
                'is_initial' => false,
                'is_final' => false,
                'transitions' => ['active', 'cancelled'],
                'permissions' => ['activate-contract'],
                'auto_transitions' => [
                    'immediate' => ['to_status' => 'active', 'condition' => 'auto_activate']
                ],
                'notifications' => ['signature_complete'],
            ],
            'active' => [
                'name' => 'Active',
                'description' => 'Contract is currently active and in effect',
                'color' => 'success',
                'icon' => 'fas fa-play-circle',
                'is_initial' => false,
                'is_final' => false,
                'transitions' => ['suspended', 'terminated', 'expired'],
                'permissions' => ['manage-contract'],
                'auto_transitions' => [
                    'expiration' => ['condition' => 'end_date_passed', 'to_status' => 'expired']
                ],
                'notifications' => ['activation_notice', 'renewal_reminder'],
            ],
            'suspended' => [
                'name' => 'Suspended',
                'description' => 'Contract is temporarily suspended',
                'color' => 'warning',
                'icon' => 'fas fa-pause-circle',
                'is_initial' => false,
                'is_final' => false,
                'transitions' => ['active', 'terminated'],
                'permissions' => ['suspend-contract', 'reactivate-contract'],
                'auto_transitions' => [],
                'notifications' => ['suspension_notice'],
            ],
            'terminated' => [
                'name' => 'Terminated',
                'description' => 'Contract has been terminated',
                'color' => 'danger',
                'icon' => 'fas fa-stop-circle',
                'is_initial' => false,
                'is_final' => true,
                'transitions' => [],
                'permissions' => ['terminate-contract'],
                'auto_transitions' => [],
                'notifications' => ['termination_notice'],
            ],
            'expired' => [
                'name' => 'Expired',
                'description' => 'Contract has reached its end date',
                'color' => 'danger',
                'icon' => 'fas fa-calendar-times',
                'is_initial' => false,
                'is_final' => true,
                'transitions' => ['renewed'],
                'permissions' => ['renew-contract'],
                'auto_transitions' => [],
                'notifications' => ['expiration_notice'],
            ],
            'cancelled' => [
                'name' => 'Cancelled',
                'description' => 'Contract has been cancelled',
                'color' => 'dark',
                'icon' => 'fas fa-ban',
                'is_initial' => false,
                'is_final' => true,
                'transitions' => [],
                'permissions' => ['cancel-contract'],
                'auto_transitions' => [],
                'notifications' => ['cancellation_notice'],
            ],
        ];
    }

    /**
     * Get default signature statuses
     */
    protected function getDefaultSignatureStatuses(Company $company): array
    {
        return [
            'not_required' => [
                'name' => 'Not Required',
                'description' => 'Signature not required for this contract',
                'color' => 'secondary',
                'icon' => 'fas fa-minus-circle',
            ],
            'pending' => [
                'name' => 'Pending',
                'description' => 'Awaiting signatures',
                'color' => 'warning',
                'icon' => 'fas fa-clock',
            ],
            'client_signed' => [
                'name' => 'Client Signed',
                'description' => 'Client has signed, awaiting company signature',
                'color' => 'info',
                'icon' => 'fas fa-signature',
            ],
            'company_signed' => [
                'name' => 'Company Signed',
                'description' => 'Company has signed, awaiting client signature',
                'color' => 'primary',
                'icon' => 'fas fa-signature',
            ],
            'fully_executed' => [
                'name' => 'Fully Executed',
                'description' => 'All parties have signed the contract',
                'color' => 'success',
                'icon' => 'fas fa-check-double',
            ],
            'declined' => [
                'name' => 'Declined',
                'description' => 'Signature was declined',
                'color' => 'danger',
                'icon' => 'fas fa-times-circle',
            ],
            'expired' => [
                'name' => 'Expired',
                'description' => 'Signature request has expired',
                'color' => 'danger',
                'icon' => 'fas fa-calendar-times',
            ],
        ];
    }

    /**
     * Get default renewal types
     */
    protected function getDefaultRenewalTypes(Company $company): array
    {
        return [
            'none' => [
                'name' => 'No Renewal',
                'description' => 'Contract does not renew',
                'icon' => 'fas fa-stop',
                'auto_renew' => false,
                'notice_days' => null,
            ],
            'manual' => [
                'name' => 'Manual Renewal',
                'description' => 'Requires manual renewal process',
                'icon' => 'fas fa-hand-paper',
                'auto_renew' => false,
                'notice_days' => 30,
            ],
            'automatic' => [
                'name' => 'Automatic Renewal',
                'description' => 'Automatically renews unless cancelled',
                'icon' => 'fas fa-sync-alt',
                'auto_renew' => true,
                'notice_days' => 60,
            ],
            'negotiated' => [
                'name' => 'Renewal by Negotiation',
                'description' => 'Renewal terms require negotiation',
                'icon' => 'fas fa-handshake',
                'auto_renew' => false,
                'notice_days' => 90,
            ],
        ];
    }

    /**
     * Get default field types
     */
    protected function getDefaultFieldTypes(Company $company): array
    {
        return [
            // Basic field types
            'text' => ['name' => 'Text Input', 'component' => 'text'],
            'textarea' => ['name' => 'Textarea', 'component' => 'textarea'],
            'number' => ['name' => 'Number', 'component' => 'number'],
            'currency' => ['name' => 'Currency', 'component' => 'currency'],
            'date' => ['name' => 'Date', 'component' => 'date'],
            'datetime' => ['name' => 'Date & Time', 'component' => 'datetime'],
            'select' => ['name' => 'Select Dropdown', 'component' => 'select'],
            'multiselect' => ['name' => 'Multi-Select', 'component' => 'multiselect'],
            'checkbox' => ['name' => 'Checkbox', 'component' => 'checkbox'],
            'radio' => ['name' => 'Radio Buttons', 'component' => 'radio'],
            'file' => ['name' => 'File Upload', 'component' => 'file'],
            'email' => ['name' => 'Email', 'component' => 'email'],
            'percentage' => ['name' => 'Percentage', 'component' => 'percentage'],
            'json' => ['name' => 'JSON Data', 'component' => 'json'],
            
            // Advanced field types
            'client_selector' => ['name' => 'Client Selector', 'component' => 'client-selector'],
            'user_selector' => ['name' => 'User Selector', 'component' => 'user-selector'],
            'asset_selector' => ['name' => 'Asset Selector', 'component' => 'asset-selector'],
            'contact_selector' => ['name' => 'Contact Selector', 'component' => 'contact-selector'],
            'service_selector' => ['name' => 'Service Selector', 'component' => 'service-selector'],
            'location_selector' => ['name' => 'Location Selector', 'component' => 'location-selector'],
            'billing_schedule' => ['name' => 'Billing Schedule', 'component' => 'billing-schedule'],
            'sla_terms' => ['name' => 'SLA Terms', 'component' => 'sla-terms'],
            'pricing_matrix' => ['name' => 'Pricing Matrix', 'component' => 'pricing-matrix'],
            'conditional_logic' => ['name' => 'Conditional Logic', 'component' => 'conditional-logic'],
        ];
    }

    /**
     * Get default validation rules
     */
    protected function getDefaultValidationRules(Company $company): array
    {
        return [
            'contract_value_minimum' => [
                'rule' => 'min',
                'value' => 100,
                'message' => 'Contract value must be at least $100',
                'applies_to' => ['service_agreement', 'maintenance_contract'],
            ],
            'contract_term_maximum' => [
                'rule' => 'max',
                'value' => 60,
                'field' => 'term_months',
                'message' => 'Contract term cannot exceed 60 months',
                'applies_to' => ['equipment_lease'],
            ],
            'required_client_approval' => [
                'rule' => 'required_if',
                'condition' => 'contract_value,>=,10000',
                'field' => 'client_approval_required',
                'message' => 'Client approval required for contracts over $10,000',
            ],
        ];
    }

    /**
     * Get default workflows
     */
    protected function getDefaultWorkflows(Company $company): array
    {
        return [
            'standard' => [
                'name' => 'Standard Workflow',
                'description' => 'Standard contract approval workflow',
                'steps' => [
                    ['status' => 'draft', 'approvers' => [], 'auto_advance' => false],
                    ['status' => 'pending_review', 'approvers' => ['manager'], 'auto_advance' => false],
                    ['status' => 'approved', 'approvers' => [], 'auto_advance' => true],
                    ['status' => 'pending_signature', 'approvers' => [], 'auto_advance' => false],
                    ['status' => 'signed', 'approvers' => [], 'auto_advance' => true],
                    ['status' => 'active', 'approvers' => [], 'auto_advance' => false],
                ],
            ],
            'approval_workflow' => [
                'name' => 'Approval Workflow',
                'description' => 'Multi-level approval workflow',
                'steps' => [
                    ['status' => 'draft', 'approvers' => [], 'auto_advance' => false],
                    ['status' => 'pending_review', 'approvers' => ['manager'], 'auto_advance' => false],
                    ['status' => 'under_negotiation', 'approvers' => ['director'], 'auto_advance' => false],
                    ['status' => 'approved', 'approvers' => [], 'auto_advance' => true],
                    ['status' => 'pending_signature', 'approvers' => [], 'auto_advance' => false],
                    ['status' => 'signed', 'approvers' => [], 'auto_advance' => true],
                    ['status' => 'active', 'approvers' => [], 'auto_advance' => false],
                ],
            ],
        ];
    }

    /**
     * Get default templates
     */
    protected function getDefaultTemplates(Company $company): array
    {
        return [
            'standard_service' => [
                'name' => 'Standard Service Agreement',
                'description' => 'Template for standard service agreements',
                'contract_type' => 'service_agreement',
                'content_template' => 'templates.contracts.standard_service',
                'required_fields' => ['client_id', 'service_description', 'contract_value'],
                'default_term_months' => 12,
                'auto_renewal' => true,
            ],
            'equipment_lease' => [
                'name' => 'Equipment Lease Agreement',
                'description' => 'Template for equipment leasing contracts',
                'contract_type' => 'equipment_lease',
                'content_template' => 'templates.contracts.equipment_lease',
                'required_fields' => ['client_id', 'equipment_list', 'lease_amount'],
                'default_term_months' => 36,
                'auto_renewal' => false,
            ],
        ];
    }

    /**
     * Get default business rules
     */
    protected function getDefaultBusinessRules(Company $company): array
    {
        return [
            'auto_activate_on_signature' => [
                'name' => 'Auto-activate on signature',
                'description' => 'Automatically activate contracts when fully signed',
                'condition' => 'signature_status = fully_executed',
                'action' => 'update_status',
                'action_params' => ['status' => 'active'],
                'enabled' => true,
            ],
            'expire_unsigned_contracts' => [
                'name' => 'Expire unsigned contracts',
                'description' => 'Mark contracts as expired after 30 days without signature',
                'condition' => 'status = pending_signature AND created_at < 30 days ago',
                'action' => 'update_status',
                'action_params' => ['status' => 'expired'],
                'enabled' => true,
                'schedule' => 'daily',
            ],
            'renewal_notifications' => [
                'name' => 'Renewal notifications',
                'description' => 'Send renewal notifications before contract expiration',
                'condition' => 'end_date BETWEEN now() AND 60 days from now',
                'action' => 'send_notification',
                'action_params' => ['template' => 'renewal_reminder'],
                'enabled' => true,
                'schedule' => 'weekly',
            ],
        ];
    }

    /**
     * Get default values
     */
    protected function getDefaultValues(Company $company): array
    {
        return [
            'default_signed_status' => 'signed',
            'default_active_status' => 'active',
            'default_terminated_status' => 'terminated',
            'default_suspended_status' => 'suspended',
            'default_expired_status' => 'expired',
            'default_signature_status' => 'pending',
            'default_signed_signature_status' => 'fully_executed',
            'default_currency' => $company->currency ?? 'USD',
            'default_term_months' => 12,
            'default_renewal_type' => 'manual',
            'default_billing_frequency' => 'monthly',
            'auto_generate_contract_numbers' => true,
            'contract_number_prefix' => strtoupper(substr($company->name, 0, 3)),
            'require_client_approval' => false,
            'enable_digital_signatures' => true,
            'auto_activate_on_signature' => true,
        ];
    }

    /**
     * Get default notifications
     */
    protected function getDefaultNotifications(Company $company): array
    {
        return [
            'enabled' => true,
            'channels' => ['mail', 'database'],
            'templates' => [
                'contract_created' => 'notifications.contract.created',
                'contract_signed' => 'notifications.contract.signed',
                'contract_activated' => 'notifications.contract.activated',
                'contract_expired' => 'notifications.contract.expired',
                'renewal_reminder' => 'notifications.contract.renewal_reminder',
            ],
            'recipients' => [
                'contract_manager' => true,
                'account_manager' => true,
                'client' => true,
                'billing_team' => false,
            ],
        ];
    }

    /**
     * Get default integrations
     */
    protected function getDefaultIntegrations(Company $company): array
    {
        return [
            'signature_provider' => [
                'enabled' => false,
                'provider' => 'docusign',
                'config' => [],
            ],
            'billing_system' => [
                'enabled' => true,
                'auto_create_invoices' => true,
                'config' => [],
            ],
            'crm_integration' => [
                'enabled' => false,
                'provider' => null,
                'config' => [],
            ],
            'audit_logging' => [
                'enabled' => true,
                'log_all_changes' => true,
                'retention_days' => 365,
            ],
        ];
    }

    /**
     * Seed navigation items
     */
    protected function seedNavigationItems(Company $company): void
    {
        $navigationItems = [
            [
                'company_id' => $company->id,
                'label' => 'Contracts Overview',
                'slug' => 'overview',
                'route' => 'contracts.dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'parent_slug' => null,
                'sort_order' => 1,
                'is_active' => true,
                'config' => json_encode(['type' => 'page', 'description' => 'Main contracts dashboard']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'label' => 'All Contracts',
                'slug' => 'all-contracts',
                'route' => 'contracts.index',
                'icon' => 'fas fa-file-contract',
                'parent_slug' => null,
                'sort_order' => 2,
                'is_active' => true,
                'config' => json_encode(['type' => 'list', 'description' => 'View all contracts']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Add navigation items for each contract type
        $contractTypes = $this->getDefaultContractTypes($company);
        $order = 10;

        foreach ($contractTypes as $slug => $config) {
            $navigationItems[] = [
                'company_id' => $company->id,
                'label' => $config['name'],
                'slug' => $slug,
                'route' => "contracts.{$slug}.index",
                'icon' => $config['icon'],
                'parent_slug' => null,
                'sort_order' => $order++,
                'is_active' => true,
                'config' => json_encode(['type' => 'list', 'description' => $config['description']]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('contract_navigation_items')->insert($navigationItems);
    }

    /**
     * Seed form configurations
     */
    protected function seedFormConfigurations(Company $company): void
    {
        $contractTypes = $this->getDefaultContractTypes($company);

        foreach ($contractTypes as $slug => $config) {
            $formConfig = [
                'field_groups' => [
                    'basic_info' => [
                        'title' => 'Basic Information',
                        'description' => 'Essential contract details',
                        'sort_order' => 1,
                        'collapsible' => false,
                        'fields' => [
                            'title' => ['type' => 'text', 'label' => 'Contract Title', 'required' => true],
                            'description' => ['type' => 'textarea', 'label' => 'Description', 'required' => false],
                            'client_id' => ['type' => 'client_selector', 'label' => 'Client', 'required' => true],
                            'contract_value' => ['type' => 'currency', 'label' => 'Contract Value', 'required' => true],
                        ],
                    ],
                    'terms' => [
                        'title' => 'Contract Terms',
                        'description' => 'Contract duration and renewal settings',
                        'sort_order' => 2,
                        'collapsible' => true,
                        'fields' => [
                            'start_date' => ['type' => 'date', 'label' => 'Start Date', 'required' => true],
                            'end_date' => ['type' => 'date', 'label' => 'End Date', 'required' => false],
                            'term_months' => ['type' => 'number', 'label' => 'Term (Months)', 'required' => false],
                            'renewal_type' => ['type' => 'select', 'label' => 'Renewal Type', 'required' => true],
                        ],
                    ],
                ],
                'validation_rules' => [
                    'title' => ['required', 'string', 'max:255'],
                    'client_id' => ['required', 'exists:clients,id'],
                    'contract_value' => ['required', 'numeric', 'min:0'],
                    'start_date' => ['required', 'date'],
                    'end_date' => ['nullable', 'date', 'after:start_date'],
                ],
                'conditional_logic' => [
                    'end_date' => [
                        'show_if' => ['term_months' => ''],
                        'required_if' => ['term_months' => ''],
                    ],
                ],
            ];

            DB::table('contract_form_configurations')->insert([
                'company_id' => $company->id,
                'contract_type' => $slug,
                'name' => $config['name'] . ' Form',
                'description' => 'Default form for ' . $config['name'],
                'configuration' => json_encode($formConfig),
                'is_active' => true,
                'version' => '1.0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Seed view configurations
     */
    protected function seedViewConfigurations(Company $company): void
    {
        $contractTypes = $this->getDefaultContractTypes($company);

        foreach ($contractTypes as $slug => $config) {
            $viewConfig = [
                'layout' => 'tabbed',
                'sections' => [
                    'overview' => [
                        'title' => 'Overview',
                        'icon' => 'fas fa-info-circle',
                        'layout' => 'grid',
                        'fields' => [
                            'contract_number' => ['label' => 'Contract Number', 'type' => 'text'],
                            'title' => ['label' => 'Title', 'type' => 'text'],
                            'status' => ['label' => 'Status', 'type' => 'status'],
                            'client_name' => ['label' => 'Client', 'type' => 'client'],
                            'contract_value' => ['label' => 'Value', 'type' => 'currency'],
                            'start_date' => ['label' => 'Start Date', 'type' => 'date'],
                            'end_date' => ['label' => 'End Date', 'type' => 'date'],
                        ],
                    ],
                    'details' => [
                        'title' => 'Details',
                        'icon' => 'fas fa-list',
                        'layout' => 'table',
                        'fields' => [
                            'description' => ['label' => 'Description', 'type' => 'textarea'],
                            'terms_and_conditions' => ['label' => 'Terms & Conditions', 'type' => 'textarea'],
                            'renewal_type' => ['label' => 'Renewal Type', 'type' => 'text'],
                            'signature_status' => ['label' => 'Signature Status', 'type' => 'status'],
                        ],
                    ],
                    'timeline' => [
                        'title' => 'Timeline',
                        'icon' => 'fas fa-clock',
                        'layout' => 'custom',
                        'custom_component' => 'contract-timeline',
                    ],
                ],
                'sidebar' => [
                    'enabled' => true,
                    'widgets' => [
                        'quick_stats' => [
                            'title' => 'Quick Stats',
                            'type' => 'stats',
                            'fields' => ['days_remaining', 'monthly_value', 'total_invoiced'],
                        ],
                        'recent_activity' => [
                            'title' => 'Recent Activity',
                            'type' => 'activity_feed',
                            'limit' => 5,
                        ],
                    ],
                ],
            ];

            DB::table('contract_view_configurations')->insert([
                'company_id' => $company->id,
                'contract_type' => $slug,
                'name' => $config['name'] . ' View',
                'description' => 'Default view for ' . $config['name'],
                'configuration' => json_encode($viewConfig),
                'is_active' => true,
                'version' => '1.0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Seed dashboard widgets
     */
    protected function seedDashboardWidgets(Company $company): void
    {
        $widgets = [
            [
                'company_id' => $company->id,
                'name' => 'Contract Status Overview',
                'type' => 'chart',
                'configuration' => json_encode([
                    'chart_type' => 'doughnut',
                    'data_source' => 'contract_statuses',
                    'title' => 'Contracts by Status',
                ]),
                'grid_position' => json_encode(['row' => 1, 'column' => 1, 'width' => 2, 'height' => 1]),
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'name' => 'Monthly Revenue',
                'type' => 'stats',
                'configuration' => json_encode([
                    'metric' => 'monthly_recurring_revenue',
                    'title' => 'Monthly Recurring Revenue',
                    'format' => 'currency',
                    'show_trend' => true,
                ]),
                'grid_position' => json_encode(['row' => 1, 'column' => 3, 'width' => 1, 'height' => 1]),
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'name' => 'Expiring Contracts',
                'type' => 'table',
                'configuration' => json_encode([
                    'data_source' => 'expiring_contracts',
                    'title' => 'Contracts Expiring Soon',
                    'columns' => ['client_name', 'contract_type', 'end_date', 'value'],
                    'limit' => 10,
                ]),
                'grid_position' => json_encode(['row' => 2, 'column' => 1, 'width' => 3, 'height' => 1]),
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'name' => 'Recent Activity',
                'type' => 'timeline',
                'configuration' => json_encode([
                    'data_source' => 'contract_activity',
                    'title' => 'Recent Contract Activity',
                    'limit' => 20,
                    'show_filters' => true,
                ]),
                'grid_position' => json_encode(['row' => 2, 'column' => 4, 'width' => 2, 'height' => 2]),
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('contract_dashboard_widgets')->insert($widgets);
    }

    /**
     * Seed configuration for specific company
     */
    public function seedForCompany(int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        $this->seedCompanyDefaults($company);
    }

    /**
     * Reset configuration for company
     */
    public function resetForCompany(int $companyId): void
    {
        $company = Company::findOrFail($companyId);

        // Delete existing configuration
        DB::table('contract_configurations')->where('company_id', $companyId)->delete();
        DB::table('contract_navigation_items')->where('company_id', $companyId)->delete();
        DB::table('contract_form_configurations')->where('company_id', $companyId)->delete();
        DB::table('contract_view_configurations')->where('company_id', $companyId)->delete();
        DB::table('contract_dashboard_widgets')->where('company_id', $companyId)->delete();

        // Reseed with defaults
        $this->seedCompanyDefaults($company);
    }
}