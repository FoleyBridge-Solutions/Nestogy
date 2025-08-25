<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\{
    ContractFormConfiguration,
    ContractViewConfiguration,
    ContractNavigationItem,
    ContractDashboardWidget
};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContractConfigurationRegistry
{
    protected array $configCache = [];
    protected int $cacheTimeout = 3600; // 1 hour
    protected int $companyId;

    /**
     * Constructor
     */
    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Get complete company configuration
     */
    public function getCompanyConfiguration(int $companyId): array
    {
        $cacheKey = "contract_config_{$companyId}";
        
        if (isset($this->configCache[$cacheKey])) {
            return $this->configCache[$cacheKey];
        }

        $config = Cache::remember($cacheKey, $this->cacheTimeout, function () use ($companyId) {
            return [
                'contract_types' => $this->getContractTypesForCompany($companyId),
                'statuses' => $this->getStatuses($companyId),
                'signature_statuses' => $this->getSignatureStatuses($companyId),
                'renewal_types' => $this->getRenewalTypes($companyId),
                'field_types' => $this->getFieldTypes($companyId),
                'validation_rules' => $this->getValidationRules($companyId),
                'workflows' => $this->getWorkflows($companyId),
                'templates' => $this->getTemplates($companyId),
                'business_rules' => $this->getBusinessRules($companyId),
                'active_statuses' => $this->getActiveStatuses($companyId),
                'signed_signature_statuses' => $this->getSignedSignatureStatuses($companyId),
                'editable_statuses' => $this->getEditableStatuses($companyId),
                'terminable_statuses' => $this->getTerminableStatuses($companyId),
                'non_renewable_types' => $this->getNonRenewableTypes($companyId),
                'defaults' => $this->getDefaults($companyId),
            ];
        });

        $this->configCache[$cacheKey] = $config;
        return $config;
    }

    /**
     * Get contract types configuration
     */
    protected function getContractTypesForCompany(int $companyId): array
    {
        // Get from navigation items or fallback to defaults
        $navigationTypes = ContractNavigationItem::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->pluck('label', 'slug')
            ->toArray();

        if (!empty($navigationTypes)) {
            return $navigationTypes;
        }

        // Fallback to default types
        return [
            'service_agreement' => 'Service Agreement',
            'maintenance_contract' => 'Maintenance Contract',
            'equipment_lease' => 'Equipment Lease',
            'professional_services' => 'Professional Services',
            'support_contract' => 'Support Contract',
            'sla_contract' => 'Service Level Agreement',
        ];
    }

    /**
     * Get statuses configuration
     */
    protected function getStatuses(int $companyId): array
    {
        // Get from company-specific configuration table if it exists
        // For now, return defaults that can be overridden per company
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['statuses'] ?? [
            'draft' => [
                'label' => 'Draft',
                'color' => 'secondary',
                'icon' => 'fas fa-edit',
                'description' => 'Contract is being prepared',
                'transitions' => ['pending_review', 'cancelled'],
            ],
            'pending_review' => [
                'label' => 'Pending Review',
                'color' => 'warning',
                'icon' => 'fas fa-clock',
                'description' => 'Contract is awaiting review',
                'transitions' => ['under_negotiation', 'approved', 'draft'],
            ],
            'under_negotiation' => [
                'label' => 'Under Negotiation',
                'color' => 'info',
                'icon' => 'fas fa-handshake',
                'description' => 'Contract terms are being negotiated',
                'transitions' => ['pending_signature', 'draft', 'cancelled'],
            ],
            'pending_signature' => [
                'label' => 'Pending Signature',
                'color' => 'primary',
                'icon' => 'fas fa-signature',
                'description' => 'Contract is ready for signature',
                'transitions' => ['signed', 'under_negotiation'],
            ],
            'signed' => [
                'label' => 'Signed',
                'color' => 'success',
                'icon' => 'fas fa-check-circle',
                'description' => 'Contract has been signed',
                'transitions' => ['active', 'cancelled'],
            ],
            'active' => [
                'label' => 'Active',
                'color' => 'success',
                'icon' => 'fas fa-play-circle',
                'description' => 'Contract is currently active',
                'transitions' => ['suspended', 'terminated', 'expired'],
            ],
            'suspended' => [
                'label' => 'Suspended',
                'color' => 'warning',
                'icon' => 'fas fa-pause-circle',
                'description' => 'Contract is temporarily suspended',
                'transitions' => ['active', 'terminated'],
            ],
            'terminated' => [
                'label' => 'Terminated',
                'color' => 'danger',
                'icon' => 'fas fa-stop-circle',
                'description' => 'Contract has been terminated',
                'transitions' => [],
            ],
            'expired' => [
                'label' => 'Expired',
                'color' => 'danger',
                'icon' => 'fas fa-calendar-times',
                'description' => 'Contract has expired',
                'transitions' => ['renewed'],
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'color' => 'dark',
                'icon' => 'fas fa-ban',
                'description' => 'Contract has been cancelled',
                'transitions' => [],
            ],
        ];
    }

    /**
     * Get signature statuses configuration
     */
    protected function getSignatureStatuses(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['signature_statuses'] ?? [
            'not_required' => [
                'label' => 'Not Required',
                'color' => 'secondary',
                'icon' => 'fas fa-minus-circle',
            ],
            'pending' => [
                'label' => 'Pending',
                'color' => 'warning',
                'icon' => 'fas fa-clock',
            ],
            'client_signed' => [
                'label' => 'Client Signed',
                'color' => 'info',
                'icon' => 'fas fa-signature',
            ],
            'company_signed' => [
                'label' => 'Company Signed',
                'color' => 'primary',
                'icon' => 'fas fa-signature',
            ],
            'fully_executed' => [
                'label' => 'Fully Executed',
                'color' => 'success',
                'icon' => 'fas fa-check-double',
            ],
            'declined' => [
                'label' => 'Declined',
                'color' => 'danger',
                'icon' => 'fas fa-times-circle',
            ],
            'expired' => [
                'label' => 'Expired',
                'color' => 'danger',
                'icon' => 'fas fa-calendar-times',
            ],
        ];
    }

    /**
     * Get renewal types configuration
     */
    protected function getRenewalTypes(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['renewal_types'] ?? [
            'none' => [
                'label' => 'No Renewal',
                'description' => 'Contract does not renew automatically',
                'icon' => 'fas fa-stop',
            ],
            'manual' => [
                'label' => 'Manual Renewal',
                'description' => 'Contract requires manual renewal process',
                'icon' => 'fas fa-hand-paper',
            ],
            'automatic' => [
                'label' => 'Automatic Renewal',
                'description' => 'Contract renews automatically',
                'icon' => 'fas fa-sync-alt',
            ],
            'negotiated' => [
                'label' => 'Renewal by Negotiation',
                'description' => 'Contract renewal requires negotiation',
                'icon' => 'fas fa-handshake',
            ],
        ];
    }

    /**
     * Get field types configuration
     */
    protected function getFieldTypes(int $companyId): array
    {
        return [
            'text' => [
                'label' => 'Text Input',
                'component' => 'text',
                'validation_types' => ['required', 'min', 'max', 'regex'],
            ],
            'textarea' => [
                'label' => 'Textarea',
                'component' => 'textarea',
                'validation_types' => ['required', 'min', 'max'],
            ],
            'number' => [
                'label' => 'Number',
                'component' => 'number',
                'validation_types' => ['required', 'min', 'max', 'integer', 'numeric'],
            ],
            'currency' => [
                'label' => 'Currency',
                'component' => 'currency',
                'validation_types' => ['required', 'min', 'numeric'],
            ],
            'date' => [
                'label' => 'Date',
                'component' => 'date',
                'validation_types' => ['required', 'date', 'after', 'before'],
            ],
            'datetime' => [
                'label' => 'Date & Time',
                'component' => 'datetime',
                'validation_types' => ['required', 'date', 'after', 'before'],
            ],
            'select' => [
                'label' => 'Select Dropdown',
                'component' => 'select',
                'validation_types' => ['required', 'in'],
            ],
            'multiselect' => [
                'label' => 'Multi-Select',
                'component' => 'multiselect',
                'validation_types' => ['required', 'array'],
            ],
            'checkbox' => [
                'label' => 'Checkbox',
                'component' => 'checkbox',
                'validation_types' => ['boolean', 'accepted'],
            ],
            'radio' => [
                'label' => 'Radio Buttons',
                'component' => 'radio',
                'validation_types' => ['required', 'in'],
            ],
            'file' => [
                'label' => 'File Upload',
                'component' => 'file',
                'validation_types' => ['required', 'file', 'mimes', 'max'],
            ],
            'email' => [
                'label' => 'Email',
                'component' => 'email',
                'validation_types' => ['required', 'email'],
            ],
            'percentage' => [
                'label' => 'Percentage',
                'component' => 'percentage',
                'validation_types' => ['required', 'numeric', 'min', 'max'],
            ],
            'json' => [
                'label' => 'JSON Data',
                'component' => 'json',
                'validation_types' => ['json'],
            ],
            'client_selector' => [
                'label' => 'Client Selector',
                'component' => 'client-selector',
                'validation_types' => ['required', 'exists:clients,id'],
            ],
            'user_selector' => [
                'label' => 'User Selector',
                'component' => 'user-selector',
                'validation_types' => ['required', 'exists:users,id'],
            ],
            'asset_selector' => [
                'label' => 'Asset Selector',
                'component' => 'asset-selector',
                'validation_types' => ['required', 'exists:assets,id'],
            ],
        ];
    }

    /**
     * Get validation rules
     */
    protected function getValidationRules(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['validation_rules'] ?? [];
    }

    /**
     * Get workflows configuration
     */
    protected function getWorkflows(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['workflows'] ?? [];
    }

    /**
     * Get templates configuration
     */
    protected function getTemplates(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['templates'] ?? [];
    }

    /**
     * Get business rules
     */
    protected function getBusinessRules(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['business_rules'] ?? [];
    }

    /**
     * Get statuses considered "active"
     */
    protected function getActiveStatuses(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['active_statuses'] ?? ['active', 'signed'];
    }

    /**
     * Get signature statuses considered "signed"
     */
    protected function getSignedSignatureStatuses(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['signed_signature_statuses'] ?? ['fully_executed'];
    }

    /**
     * Get statuses that allow editing
     */
    protected function getEditableStatuses(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['editable_statuses'] ?? [
            'draft', 'pending_review', 'under_negotiation', 'pending_signature', 'active'
        ];
    }

    /**
     * Get statuses that allow termination
     */
    protected function getTerminableStatuses(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['terminable_statuses'] ?? ['active', 'signed', 'suspended'];
    }

    /**
     * Get renewal types that don't allow renewal
     */
    protected function getNonRenewableTypes(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['non_renewable_types'] ?? ['none'];
    }

    /**
     * Get default values
     */
    protected function getDefaults(int $companyId): array
    {
        $config = $this->getCompanySpecificConfig($companyId);
        
        return $config['defaults'] ?? [
            'default_signed_status' => 'signed',
            'default_active_status' => 'active',
            'default_terminated_status' => 'terminated',
            'default_suspended_status' => 'suspended',
            'default_expired_status' => 'expired',
            'default_signature_status' => 'pending',
            'default_signed_signature_status' => 'fully_executed',
        ];
    }

    /**
     * Get company-specific configuration
     */
    protected function getCompanySpecificConfig(int $companyId): array
    {
        // This would typically come from a configuration table
        // For now, check if there's any stored configuration
        
        try {
            $result = DB::table('contract_configurations')
                ->where('company_id', $companyId)
                ->first();
                
            if ($result && $result->configuration) {
                return json_decode($result->configuration, true);
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet, return empty array
        }
        
        return [];
    }

    /**
     * Update company configuration
     */
    public function updateCompanyConfiguration(int $companyId, array $config): void
    {
        try {
            DB::table('contract_configurations')->updateOrInsert(
                ['company_id' => $companyId],
                [
                    'configuration' => json_encode($config),
                    'updated_at' => now(),
                ]
            );
            
            // Clear cache
            $cacheKey = "contract_config_{$companyId}";
            Cache::forget($cacheKey);
            unset($this->configCache[$cacheKey]);
            
        } catch (\Exception $e) {
            // Handle gracefully if table doesn't exist
            \Log::warning("Could not update contract configuration: " . $e->getMessage());
        }
    }

    /**
     * Clear configuration cache
     */
    public function clearCache(?int $companyId = null): void
    {
        if ($companyId) {
            $cacheKey = "contract_config_{$companyId}";
            Cache::forget($cacheKey);
            unset($this->configCache[$cacheKey]);
        } else {
            // Clear all cached configurations
            foreach (array_keys($this->configCache) as $key) {
                if (str_starts_with($key, 'contract_config_')) {
                    Cache::forget($key);
                    unset($this->configCache[$key]);
                }
            }
        }
    }

    /**
     * Get form configuration for contract type
     */
    public function getFormConfiguration(int $companyId, string $contractType): array
    {
        $formConfig = ContractFormConfiguration::where('company_id', $companyId)
            ->where('contract_type', $contractType)
            ->where('is_active', true)
            ->first();

        return $formConfig ? json_decode($formConfig->configuration, true) : [];
    }

    /**
     * Get view configuration for contract type
     */
    public function getViewConfiguration(int $companyId, string $contractType): array
    {
        $viewConfig = ContractViewConfiguration::where('company_id', $companyId)
            ->where('contract_type', $contractType)
            ->where('is_active', true)
            ->first();

        return $viewConfig ? json_decode($viewConfig->configuration, true) : [];
    }

    /**
     * Get navigation configuration
     */
    public function getNavigationConfiguration(int $companyId): array
    {
        return ContractNavigationItem::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get dashboard widget configurations
     */
    public function getDashboardWidgetConfigurations(int $companyId): array
    {
        return ContractDashboardWidget::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Validate configuration
     */
    public function validateConfiguration(array $config): array
    {
        $errors = [];

        // Validate contract types
        if (!isset($config['contract_types']) || empty($config['contract_types'])) {
            $errors[] = 'At least one contract type must be defined';
        }

        // Validate statuses
        if (!isset($config['statuses']) || empty($config['statuses'])) {
            $errors[] = 'At least one status must be defined';
        }

        // Check for required default status
        if (isset($config['statuses'])) {
            $statusKeys = array_keys($config['statuses']);
            
            foreach (['draft', 'active'] as $requiredStatus) {
                if (!in_array($requiredStatus, $statusKeys)) {
                    $errors[] = "Required status '{$requiredStatus}' is missing";
                }
            }
        }

        // Validate status transitions
        if (isset($config['statuses'])) {
            foreach ($config['statuses'] as $status => $statusConfig) {
                if (isset($statusConfig['transitions'])) {
                    foreach ($statusConfig['transitions'] as $transition) {
                        if (!isset($config['statuses'][$transition])) {
                            $errors[] = "Status '{$status}' has invalid transition to '{$transition}'";
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get configuration schema
     */
    public function getConfigurationSchema(): array
    {
        return [
            'contract_types' => [
                'type' => 'object',
                'description' => 'Available contract types',
                'additionalProperties' => [
                    'type' => 'string',
                    'description' => 'Display name for the contract type'
                ]
            ],
            'statuses' => [
                'type' => 'object',
                'description' => 'Available contract statuses',
                'additionalProperties' => [
                    'type' => 'object',
                    'properties' => [
                        'label' => ['type' => 'string'],
                        'color' => ['type' => 'string'],
                        'icon' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'transitions' => [
                            'type' => 'array',
                            'items' => ['type' => 'string']
                        ]
                    ]
                ]
            ],
            'signature_statuses' => [
                'type' => 'object',
                'description' => 'Available signature statuses'
            ],
            'renewal_types' => [
                'type' => 'object',
                'description' => 'Available renewal types'
            ],
            'field_types' => [
                'type' => 'object',
                'description' => 'Available field types for forms'
            ],
            'workflows' => [
                'type' => 'object',
                'description' => 'Workflow definitions'
            ],
            'business_rules' => [
                'type' => 'object',
                'description' => 'Business rule definitions'
            ]
        ];
    }

    /**
     * Get template statuses configuration
     */
    public function getTemplateStatuses(): array
    {
        $config = $this->getCompanySpecificConfig($this->companyId);
        
        return $config['template_statuses'] ?? [
            'draft' => 'Draft',
            'active' => 'Active',
            'archived' => 'Archived',
            'pending_approval' => 'Pending Approval',
        ];
    }

    /**
     * Get template categories configuration
     */
    public function getTemplateCategories(): array
    {
        $config = $this->getCompanySpecificConfig($this->companyId);
        
        return $config['template_categories'] ?? [
            'msp' => 'Managed Service Provider',
            'voip' => 'VoIP Carrier',
            'var' => 'IT Value-Added Reseller',
            'compliance' => 'Compliance & Legal',
            'general' => 'General Purpose',
        ];
    }

    /**
     * Get billing models configuration
     */
    public function getBillingModels(): array
    {
        $config = $this->getCompanySpecificConfig($this->companyId);
        
        return $config['billing_models'] ?? [
            'fixed' => 'Fixed Price',
            'per_asset' => 'Per Asset/Device',
            'per_user' => 'Per User/Seat',
            'per_contact' => 'Per Contact',
            'hourly' => 'Hourly Rate',
            'tiered' => 'Tiered Pricing',
            'hybrid' => 'Hybrid Model',
            'consumption' => 'Consumption-Based',
            'project' => 'Project-Based',
        ];
    }

    /**
     * Get contract types configuration (public wrapper for existing protected method)
     */
    public function getContractTypes(): array
    {
        return $this->getContractTypesForCompany($this->companyId);
    }

    /**
     * Get contract statuses configuration (public wrapper for existing protected method)
     */
    public function getContractStatuses(): array
    {
        return $this->getStatuses($this->companyId);
    }

    /**
     * Get contract signature statuses (simple key-value array)
     */
    public function getContractSignatureStatuses(): array
    {
        $config = $this->getCompanySpecificConfig($this->companyId);
        
        return $config['contract_signature_statuses'] ?? [
            'pending' => 'Pending',
            'signed' => 'Signed',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
        ];
    }

    /**
     * Get a specific contract type by ID
     */
    public function getContractType(string $id): ?array
    {
        $contractTypes = $this->getContractTypes();
        
        foreach ($contractTypes as $type) {
            if ($type['id'] === $id) {
                return $type;
            }
        }
        
        return null;
    }

    /**
     * Create a new contract type
     */
    public function createContractType(array $data): array
    {
        $contractTypes = $this->getContractTypes();
        
        // Generate unique ID
        $id = strtolower(str_replace([' ', '-', '.'], '_', $data['name'])) . '_' . time();
        
        $contractType = array_merge([
            'id' => $id,
            'name' => $data['name'],
            'category' => $data['category'] ?? 'custom',
            'description' => $data['description'] ?? '',
            'icon' => $data['icon'] ?? 'fas fa-file-contract',
            'color' => $data['color'] ?? 'primary',
            'default_billing_model' => $data['default_billing_model'] ?? null,
            'default_term_length' => $data['default_term_length'] ?? 12,
            'requires_signature' => $data['requires_signature'] ?? true,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
            'allows_amendments' => $data['allows_amendments'] ?? true,
            'supports_milestones' => $data['supports_milestones'] ?? false,
            'auto_renew' => $data['auto_renew'] ?? false,
            'requires_approval' => $data['requires_approval'] ?? false,
            'workflow_stages' => $data['workflow_stages'] ?? [],
            'notification_settings' => $data['notification_settings'] ?? [],
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ], $data);

        $contractTypes[] = $contractType;
        
        $this->saveContractTypes($contractTypes);
        
        return $contractType;
    }

    /**
     * Update an existing contract type
     */
    public function updateContractType(string $id, array $data): ?array
    {
        $contractTypes = $this->getContractTypes();
        $updated = false;
        
        foreach ($contractTypes as &$type) {
            if ($type['id'] === $id) {
                $type = array_merge($type, $data, [
                    'updated_at' => now()->toISOString()
                ]);
                $updated = true;
                break;
            }
        }
        
        if (!$updated) {
            return null;
        }
        
        $this->saveContractTypes($contractTypes);
        
        return $this->getContractType($id);
    }

    /**
     * Delete a contract type
     */
    public function deleteContractType(string $id): bool
    {
        $contractTypes = $this->getContractTypes();
        $initialCount = count($contractTypes);
        
        $contractTypes = array_filter($contractTypes, fn($type) => $type['id'] !== $id);
        
        if (count($contractTypes) === $initialCount) {
            return false; // Type not found
        }
        
        $this->saveContractTypes($contractTypes);
        
        return true;
    }

    /**
     * Save contract types to configuration
     */
    protected function saveContractTypes(array $contractTypes): void
    {
        $config = $this->getCompanySpecificConfig($this->companyId);
        $config['contract_types'] = $contractTypes;
        
        // For now, we'll cache this. In a real implementation, 
        // you'd save to database or configuration file
        $this->companyConfigs[$this->companyId] = $config;
        
        // Here you would typically save to the database
        // ContractConfiguration::updateOrCreate(['company_id' => $this->companyId], ['config' => $config]);
    }
}