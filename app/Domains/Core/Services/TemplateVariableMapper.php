<?php

namespace App\Domains\Core\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSchedule;
use App\Domains\Contract\Models\ContractTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TemplateVariableMapper Service
 *
 * Transforms wizard configuration data into template-specific clause variables.
 * Replaces static "Section A/B/C" references with dynamic content based on
 * actual wizard selections across all 33 contract template types.
 */
class TemplateVariableMapper
{
    /**
     * Template category mapping for content generation
     */
    const TEMPLATE_CATEGORIES = [
        // MSP Templates (11 types)
        'managed_services' => 'msp',
        'cybersecurity_services' => 'msp',
        'backup_dr' => 'msp',
        'cloud_migration' => 'msp',
        'm365_management' => 'msp',
        'break_fix' => 'msp',
        'enterprise_managed' => 'msp',
        'mdr_services' => 'msp',
        'support_contract' => 'msp',
        'maintenance_agreement' => 'msp',
        'sla_contract' => 'msp',

        // VoIP Templates (8 types)
        'hosted_pbx' => 'voip',
        'sip_trunking' => 'voip',
        'unified_communications' => 'voip',
        'international_calling' => 'voip',
        'contact_center' => 'voip',
        'e911_services' => 'voip',
        'number_porting' => 'voip',
        'service_agreement' => 'voip', // When used for VoIP

        // VAR Templates (6 types)
        'hardware_procurement' => 'var',
        'software_licensing' => 'var',
        'vendor_partner' => 'var',
        'solution_integration' => 'var',
        'equipment_lease' => 'var',
        'installation_contract' => 'var',

        // Compliance Templates (4 types)
        'business_associate' => 'compliance',
        'professional_services' => 'compliance', // When used for audits
        'data_processing' => 'compliance',
        'master_service' => 'compliance', // When used for compliance

        // General Templates (4 types)
        'consumption_based' => 'general',
        'international_service' => 'general',
    ];

    /**
     * Excluded asset statuses (non-active assets)
     * Centralized exclusion list for consistency across asset filtering operations
     */
    protected const EXCLUDED_ASSET_STATUSES = [
        'Archived',
        'Lost/Stolen',
        'Broken - Not Repairable',
        'Broken - Pending Repair',
        'Out for Repair',
        'Unknown',
    ];

    /**
     * Asset type display names for service descriptions
     */
    const ASSET_TYPE_NAMES = [
        'hypervisor_node' => 'Hypervisor Nodes',
        'workstation' => 'Workstations',
        'server' => 'Servers',
        'network_device' => 'Network Devices',
        'mobile_device' => 'Mobile Devices',
        'printer' => 'Printers & Peripherals',
        'storage' => 'Storage Systems',
        'security_device' => 'Security Devices',
    ];

    /**
     * Service tier configurations
     */
    const SERVICE_TIERS = [
        'bronze' => [
            'name' => 'Bronze',
            'response_time' => 8,
            'resolution_time' => 48,
            'uptime' => 99.0,
            'coverage' => '8x5',
            'benefits' => [
                'Business hours support (8x5)',
                'Remote assistance included',
                'Email & phone support',
            ],
        ],
        'silver' => [
            'name' => 'Silver',
            'response_time' => 4,
            'resolution_time' => 24,
            'uptime' => 99.5,
            'coverage' => '12x5',
            'benefits' => [
                'Extended hours support (12x5)',
                'Remote & limited on-site',
                'Priority phone support',
                'Quarterly reviews',
            ],
        ],
        'gold' => [
            'name' => 'Gold',
            'response_time' => 2,
            'resolution_time' => 12,
            'uptime' => 99.9,
            'coverage' => '24x7',
            'benefits' => [
                '24x7 support coverage',
                'On-site support included',
                'Dedicated support contact',
                'Monthly reviews & reporting',
            ],
        ],
        'platinum' => [
            'name' => 'Platinum',
            'response_time' => 1,
            'resolution_time' => 4,
            'uptime' => 99.95,
            'coverage' => '24x7',
            'benefits' => [
                '24x7 priority support',
                'Guaranteed on-site response',
                'Dedicated account manager',
                'Proactive monitoring included',
            ],
        ],
    ];

    /**
     * Generate template-specific variables from contract and schedule data
     */
    public function generateVariables(Contract $contract, ?array $sectionMapping = null): array
    {
        Log::info('🔧 TemplateVariableMapper: Starting variable generation', [
            'contract_id' => $contract->id,
            'template_id' => $contract->template_id,
            'has_metadata' => ! empty($contract->metadata),
            'metadata_count' => $contract->metadata ? count($contract->metadata) : 0,
        ]);

        $baseVariables = $this->generateBaseVariables($contract, $sectionMapping);
        Log::info('📊 Base variables generated', [
            'count' => count($baseVariables),
            'keys' => array_keys($baseVariables),
        ]);

        $templateCategory = $this->getTemplateCategory($contract->template, $contract->contract_type);
        Log::info('📋 Template category determined', [
            'category' => $templateCategory,
            'template_type' => $contract->template?->template_type,
            'contract_type' => $contract->contract_type,
        ]);

        // Generate category-specific variables
        $categoryVariables = match ($templateCategory) {
            'msp' => $this->generateMspVariables($contract),
            'voip' => $this->generateVoipVariables($contract),
            'var' => $this->generateVarVariables($contract),
            'compliance' => $this->generateComplianceVariables($contract),
            'general' => $this->generateGeneralVariables($contract),
            default => []
        };

        Log::info('🏢 Category-specific variables generated', [
            'category' => $templateCategory,
            'count' => count($categoryVariables),
            'keys' => array_keys($categoryVariables),
        ]);

        // Merge in contract-specific variables from metadata
        $contractVariables = $this->extractContractMetadataVariables($contract);
        Log::info('📝 Contract metadata variables extracted', [
            'count' => count($contractVariables),
            'wizard_variables' => array_intersect_key($contractVariables, array_flip([
                'billing_model', 'service_tier', 'payment_terms', 'response_time_hours',
                'voip_enabled', 'hardware_support', 'price_per_user',
            ])),
        ]);

        // Merge variables with priority to non-empty values
        // Start with metadata variables (from wizard)
        $finalVariables = $contractVariables;

        // Add category variables, but don't overwrite non-empty values
        foreach ($categoryVariables as $key => $value) {
            if (! isset($finalVariables[$key]) || empty($finalVariables[$key])) {
                $finalVariables[$key] = $value;
            }
        }

        // Add base variables, but don't overwrite non-empty values
        foreach ($baseVariables as $key => $value) {
            if (! isset($finalVariables[$key]) || empty($finalVariables[$key])) {
                $finalVariables[$key] = $value;
            }
        }

        Log::info('✅ Final variable set assembled', [
            'total_count' => count($finalVariables),
            'base_count' => count($baseVariables),
            'category_count' => count($categoryVariables),
            'metadata_count' => count($contractVariables),
            'final_wizard_vars' => array_intersect_key($finalVariables, array_flip([
                'billing_model', 'service_tier', 'payment_terms', 'response_time_hours',
                'voip_enabled', 'hardware_support', 'price_per_user', 'setup_fee',
            ])),
        ]);

        return $finalVariables;
    }

    /**
     * Get template category for a contract template
     */
    public function getTemplateCategory(?ContractTemplate $template, ?string $contractType = null): string
    {
        if ($template) {
            return self::TEMPLATE_CATEGORIES[$template->template_type] ?? 'general';
        }

        // If no template but we have a contract type, use that to determine category
        if ($contractType) {
            return self::TEMPLATE_CATEGORIES[$contractType] ?? 'general';
        }

        return 'general';
    }

    /**
     * Generate base variables common to all contracts
     */
    protected function generateBaseVariables(Contract $contract, ?array $sectionMapping = null): array
    {

        $variables = [
            // Contract basics
            'contract_title' => $contract->title,
            'contract_type' => $contract->contract_type,
            'effective_date' => $contract->start_date,
            'end_date' => $contract->end_date,
            'currency_code' => $contract->currency_code ?? 'USD',

            // Client information
            'client_name' => $contract->client->name ?? '',
            'client_short_name' => $contract->client->name ?? '', // Use name as short name
            'client_address' => $contract->client ? $contract->client->getFullAddressAttribute() : '',

            // Service provider information (from contract's company)
            'service_provider_name' => $contract->company->name ?? '',
            'service_provider_short_name' => $contract->company->name ?? '', // Use name as short name
            'service_provider_address' => $contract->company ? $contract->company->getFullAddress() : '',

            // Payment method variables (from company settings)
            'accepted_payment_methods' => $this->getAcceptedPaymentMethods($contract->company),
            'accepted_payment_methods_formatted' => $this->getFormattedPaymentMethods($contract->company),
            'accepts_credit_cards' => $this->acceptsCreditCards($contract->company),
            'accepts_ach' => $this->acceptsAch($contract->company),
            'accepts_wire_transfer' => $this->acceptsWireTransfer($contract->company),
            'accepts_checks' => $this->acceptsChecks($contract->company),

            // Legal and contract terms
            'governing_state' => $this->resolveGoverningLaw($contract),
            'governing_law' => $this->resolveGoverningLaw($contract),
            'PROVIDER_STATE' => $contract->company->state ?? 'Texas',
            'provider_state' => $contract->company->state ?? 'Texas',
            'client_state' => $contract->client->state ?? 'Texas',
            'initial_term' => $this->getInitialTermVariable($contract),
            'renewal_term' => $this->getRenewalTermVariable($contract),
            'termination_notice_days' => $contract->custom_clauses['termination']['noticePeriod'] ?? '30 days',
            'arbitration_location' => ($contract->client->city ?? 'Austin').', '.($contract->client->state ?? 'Texas'),

            // Signature block variables
            'client_signatory_name' => $contract->client->primary_contact_name ?? $contract->client->name ?? '',
            'client_signatory_title' => $contract->client->primary_contact_title ?? 'Authorized Representative',
            'service_provider_signatory_name' => $contract->company->owner_name ?? $contract->company->name ?? '',
            'service_provider_signatory_title' => $contract->company->owner_title ?? 'Authorized Representative',
            'signature_date' => now()->format('F j, Y'),
            'client_signature_date' => '',
            'service_provider_signature_date' => '',

        ];

        // Add dynamic section references if section mapping is provided
        if ($sectionMapping) {
            $variables = array_merge($variables, $this->generateSectionReferenceVariables($sectionMapping));
        } else {
            // Fallback to comprehensive static references if no section mapping available
            $variables = array_merge($variables, [
                'definitions_section_ref' => 'Section 1 (Definitions)',
                'services_section_ref' => 'Section 2 (Scope of Support Services)',
                'sla_section_ref' => 'Section 3 (Service Level Agreements)',
                'obligations_section_ref' => 'Section 4 (Client Obligations and Responsibilities)',
                'financial_section_ref' => 'Section 5 (Fees and Payment Terms)',
                'exclusions_section_ref' => 'Section 6 (Exclusions and Limitations)',
                'warranties_section_ref' => 'Section 7 (Warranties and Representations)',
                'confidentiality_section_ref' => 'Section 8 (Confidentiality)',
                'legal_section_ref' => 'Section 9 (Legal Terms and Governing Law)',
                'admin_section_ref' => 'Section 10 (Administrative Provisions)',
            ]);
        }

        // Add template-specific base variables
        if ($contract->template) {
            $variables['template_type'] = $contract->template->template_type;
            $variables['template_category'] = $this->getTemplateCategory($contract->template);
        }

        return $variables;
    }

    /**
     * Generate MSP-specific variables from infrastructure and pricing schedules
     */
    protected function generateMspVariables(Contract $contract): array
    {
        $variables = [];
        $schedules = $contract->schedules;

        // Set default boolean variables to false to prevent undefined variable issues in conditionals
        $variables['has_workstation_support'] = false;
        $variables['has_server_support'] = false;
        $variables['has_network_support'] = false;
        $variables['includes_remote_support'] = false;
        $variables['includes_onsite_support'] = false;
        $variables['auto_assign_assets'] = false;

        // Infrastructure schedule variables (schedule_type = 'A')
        $infraSchedule = $contract->infrastructureSchedules()->effective()->first();
        if ($infraSchedule) {
            // Get data from ContractSchedule columns, not schedule_data
            $supportedAssets = $infraSchedule->supported_asset_types ?? [];
            $variables['supported_asset_types'] = $this->formatAssetTypesList($supportedAssets);
            $variables['supported_asset_count'] = count($supportedAssets);
            $variables['has_workstation_support'] = in_array('workstation', $supportedAssets);
            $variables['has_server_support'] = in_array('server', $supportedAssets);
            $variables['has_network_support'] = in_array('network_device', $supportedAssets);

            // SLA configuration from sla_terms column
            $sla = $infraSchedule->sla_terms ?? [];
            $serviceTier = $sla['serviceTier'] ?? 'bronze';
            $tierConfig = self::SERVICE_TIERS[$serviceTier] ?? self::SERVICE_TIERS['bronze'];

            $variables['service_tier'] = $tierConfig['name'];
            $variables['response_time_hours'] = $tierConfig['response_time'];
            $variables['resolution_time_hours'] = $tierConfig['resolution_time'];
            $variables['uptime_percentage'] = $tierConfig['uptime'];
            $variables['business_hours'] = $tierConfig['coverage'];
            $variables['tier_benefits'] = implode(', ', $tierConfig['benefits']);

            // Coverage rules from coverage_rules column
            $coverage = $infraSchedule->coverage_rules ?? [];
            $variables['includes_remote_support'] = $coverage['includeRemoteSupport'] ?? true;
            $variables['includes_onsite_support'] = $coverage['includeOnsiteSupport'] ?? false;
            $variables['auto_assign_assets'] = $coverage['autoAssignNewAssets'] ?? false;

            // Exclusions from coverage_rules or variable_values
            $variableValues = $infraSchedule->variable_values ?? [];
            $exclusions = $variableValues['exclusions'] ?? [];
            $variables['excluded_asset_types'] = $exclusions['assetTypes'] ?? '';
            $variables['excluded_services'] = $exclusions['services'] ?? '';
        }

        // Pricing schedule variables (schedule_type = 'B')
        $pricingSchedule = $schedules->where('schedule_type', 'B')->first();
        if ($pricingSchedule) {
            // Get data from pricing_structure column
            $pricingData = $pricingSchedule->pricing_structure ?? [];

            $variables['billing_model'] = $pricingData['billingModel'] ?? 'per_asset';
            $variables['monthly_base_rate'] = $pricingData['basePricing']['monthlyBase'] ?? '';
            $variables['setup_fee'] = $pricingData['basePricing']['setupFee'] ?? '';
            $variables['hourly_rate'] = $pricingData['basePricing']['hourlyRate'] ?? '';

            // Payment terms
            $paymentTerms = $pricingData['paymentTerms'] ?? [];
            $variables['billing_frequency'] = $paymentTerms['billingFrequency'] ?? 'monthly';
            $variables['payment_terms'] = $paymentTerms['terms'] ?? 'net_30';
        }

        // Generate individual asset listing variables for Schedule B
        $assetVariables = $this->generateAssetListingVariables($contract);
        $variables = array_merge($variables, $assetVariables);

        return $variables;
    }

    /**
     * Generate individual asset listing variables for Schedule B
     */
    public function generateAssetListingVariables(Contract $contract): array
    {
        $variables = [];

        try {
            // Get client assets that should be included in the contract
            // Using true for Schedule B to show both assigned and type-eligible assets
            $assets = $this->getClientAssetsForContract($contract, true);

            if ($assets->isEmpty()) {
                Log::info('No assets found for contract asset listing', [
                    'contract_id' => $contract->id,
                    'client_id' => $contract->client_id,
                ]);

                // Set empty defaults
                $variables['individual_assets_list'] = '<p><em>No assets assigned to this contract.</em></p>';
                $variables['assets_by_type'] = $this->generateAssetsByTypeHtml([]);
                $variables['asset_count_by_type'] = '<p><em>No assets assigned.</em></p>';
                $variables['total_asset_count'] = 0;
                $variables['supported_assets_table'] = '<p><em>Asset inventory will be populated when assets are assigned.</em></p>';

                return $variables;
            }

            Log::info('Generating asset listing variables', [
                'contract_id' => $contract->id,
                'client_id' => $contract->client_id,
                'asset_count' => $assets->count(),
            ]);

            // Normalize asset types before grouping to prevent case-related splits
            $normalizedAssets = $assets->map(fn ($a) => tap($a, fn ($x) => $x->type = ucfirst(strtolower($x->type))));

            // Group assets by normalized type
            $assetsByType = $normalizedAssets->groupBy('type');

            // Generate formatted variables
            $variables['individual_assets_list'] = $this->generateAssetListHtml($normalizedAssets);
            $variables['assets_by_type'] = $this->generateAssetsByTypeHtml($assetsByType);
            $variables['asset_count_by_type'] = $this->generateAssetCountSummary($assetsByType);
            $variables['total_asset_count'] = $normalizedAssets->count();
            $variables['supported_assets_table'] = $this->generateProfessionalAssetTable($normalizedAssets);

            // Add detailed breakdowns for common asset types (using normalized types)
            $variables['server_count'] = $normalizedAssets->where('type', 'Server')->count();
            $variables['workstation_count'] = $normalizedAssets->filter(fn ($a) => in_array(strtolower($a->type), ['desktop', 'laptop']))->count();
            $variables['network_device_count'] = $normalizedAssets->filter(fn ($a) => in_array(strtolower($a->type), ['switch', 'router', 'firewall', 'access point']))->count();
            $variables['hypervisor_count'] = $normalizedAssets->filter(fn ($a) => strtolower($a->type) === 'hypervisor_node')->count();
            $variables['storage_count'] = $normalizedAssets->filter(fn ($a) => strtolower($a->type) === 'storage')->count();
            $variables['printer_count'] = $normalizedAssets->where('type', 'Printer')->count();

            return $variables;

        } catch (\Exception $e) {
            Log::error('Failed to generate asset listing variables', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return safe defaults on error
            return [
                'individual_assets_list' => '<p><em>Unable to load asset information.</em></p>',
                'assets_by_type' => $this->generateAssetsByTypeHtml([]),
                'asset_count_by_type' => '<p><em>Asset count unavailable.</em></p>',
                'total_asset_count' => 0,
                'supported_assets_table' => '<p><em>Asset inventory temporarily unavailable.</em></p>',
                'server_count' => 0,
                'workstation_count' => 0,
                'network_device_count' => 0,
                'hypervisor_count' => 0,
                'storage_count' => 0,
                'printer_count' => 0,
            ];
        }
    }

    /**
     * Get client assets that should be included in the contract
     *
     * This method selects assets for contract inclusion using a priority system:
     * 1. Direct assignments (supporting_contract_id) and explicit assignments (ContractAssetAssignment)
     * 2. Fallback to Schedule A type-eligible assets (backward compatibility)
     * 3. Optional union mode: merge both assigned and type-eligible assets
     *
     * @param  Contract  $contract  The contract to get assets for
     * @param  bool  $includeTypeEligibleAlongsideAssignments  Whether to union Schedule A type-eligible assets with assigned assets
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getClientAssetsForContract(Contract $contract, bool $includeTypeEligibleAlongsideAssignments = false)
    {
        // Add detailed contract and client information logging
        Log::info('TemplateVariableMapper getClientAssetsForContract started', [
            'contract_id' => $contract->id,
            'client_id' => $contract->client_id,
            'company_id' => $contract->company_id,
            'contract_status' => $contract->status,
            'contract_created_at' => $contract->created_at,
            'contract_updated_at' => $contract->updated_at,
            'include_type_eligible_alongside_assignments' => $includeTypeEligibleAlongsideAssignments,
            'method_mode' => $includeTypeEligibleAlongsideAssignments ? 'union' : 'assignment_or_fallback',
            'debug_context' => 'asset_retrieval_debug',
        ]);

        // Load the Asset model
        $assetModel = app(\App\Models\Asset::class);

        // Collect asset IDs from multiple sources
        $assetIds = collect();

        // Source 1: Use relationship query for direct assignments to avoid double filtering
        Log::debug('Querying direct asset assignments', [
            'contract_id' => $contract->id,
            'relationship_method' => 'supportedAssets',
            'query_sql' => $contract->supportedAssets()->toSql(),
            'query_bindings' => $contract->supportedAssets()->getBindings(),
        ]);

        $directlyAssignedIds = $contract->supportedAssets()->pluck('id');

        if ($directlyAssignedIds->isNotEmpty()) {
            $directAssetsDetails = $contract->supportedAssets()->get(['id', 'name', 'type', 'client_id', 'company_id', 'supporting_contract_id']);
            Log::info('Direct assignment debugging - assets found', [
                'contract_id' => $contract->id,
                'direct_assignment_count' => $directlyAssignedIds->count(),
                'direct_asset_ids' => $directlyAssignedIds->toArray(),
                'asset_details' => $directAssetsDetails->map(function ($asset) {
                    return [
                        'id' => $asset->id,
                        'name' => $asset->name,
                        'type' => $asset->type,
                        'client_id' => $asset->client_id,
                        'company_id' => $asset->company_id,
                        'supporting_contract_id' => $asset->supporting_contract_id,
                    ];
                })->toArray(),
                'relationship_verification' => 'working',
            ]);
        } else {
            Log::warning('Direct assignment debugging - no assets found', [
                'contract_id' => $contract->id,
                'direct_assignment_count' => 0,
                'relationship_query_sql' => $contract->supportedAssets()->toSql(),
                'potential_issues' => [
                    'no_assets_assigned_to_contract',
                    'relationship_not_working',
                    'supporting_contract_id_not_set',
                ],
            ]);
        }

        $assetIds = $assetIds->merge($directlyAssignedIds);

        // Source 2: Explicit assignment through ContractAssetAssignment with client validation
        $explicitQuery = $contract->activeAssetAssignments()
            ->whereHas('asset', function ($query) use ($contract) {
                $query->where('client_id', $contract->client_id);
            });

        Log::debug('Querying explicit asset assignments', [
            'contract_id' => $contract->id,
            'method' => 'activeAssetAssignments with whereHas',
            'query_sql' => $explicitQuery->toSql(),
            'query_bindings' => $explicitQuery->getBindings(),
            'client_validation' => "asset.client_id = {$contract->client_id}",
        ]);

        $explicitlyAssignedIds = $explicitQuery->pluck('asset_id');

        if ($explicitlyAssignedIds->isNotEmpty()) {
            $explicitAssignments = $contract->activeAssetAssignments()
                ->whereHas('asset', function ($query) use ($contract) {
                    $query->where('client_id', $contract->client_id);
                })
                ->with('asset')
                ->get();

            Log::info('Explicit assignment debugging - assignments found', [
                'contract_id' => $contract->id,
                'explicit_assignment_count' => $explicitlyAssignedIds->count(),
                'explicit_asset_ids' => $explicitlyAssignedIds->toArray(),
                'assignment_details' => $explicitAssignments->map(function ($assignment) {
                    return [
                        'assignment_id' => $assignment->id,
                        'asset_id' => $assignment->asset_id,
                        'asset_name' => $assignment->asset ? $assignment->asset->name : 'Asset not found',
                        'asset_client_id' => $assignment->asset ? $assignment->asset->client_id : null,
                        'client_validation_passed' => true,
                    ];
                })->toArray(),
            ]);
        } else {
            // Check if there are any ContractAssetAssignment records without client validation
            $allExplicitCount = $contract->activeAssetAssignments()->count();
            Log::debug('Explicit assignment debugging - no valid assignments', [
                'contract_id' => $contract->id,
                'explicit_assignment_count' => 0,
                'total_contract_asset_assignments' => $allExplicitCount,
                'client_validation_failed' => $allExplicitCount > 0,
                'expected_client_id' => $contract->client_id,
            ]);
        }

        $assetIds = $assetIds->merge($explicitlyAssignedIds);

        // Merge and deduplicate asset IDs
        $mergedIds = $assetIds->unique()->filter();
        Log::info("Total unique assigned assets for contract {$contract->id}: {$mergedIds->count()}");

        // Get fallback type ID set for union mode or fallback
        $fallbackTypeIds = collect();
        $supportedTypes = [];

        // Enhanced fallback logic debugging with detailed source tracking
        $infraSchedule = $contract->infrastructureSchedules()->effective()->first();
        $typeSource = 'none';

        Log::debug('Searching for supported asset types from Schedule A', [
            'contract_id' => $contract->id,
            'infrastructure_schedule_found' => $infraSchedule !== null,
            'infrastructure_schedule_id' => $infraSchedule ? $infraSchedule->id : null,
        ]);

        if ($infraSchedule) {
            // Try infraSchedule->supported_asset_types first
            if (! empty($infraSchedule->supported_asset_types)) {
                $supportedTypes = $infraSchedule->supported_asset_types;
                $typeSource = 'infraSchedule.supported_asset_types';
            }
            // Fallback to schedule_data if supported_asset_types is empty
            elseif (! empty($infraSchedule->schedule_data['supported_asset_types'])) {
                $supportedTypes = $infraSchedule->schedule_data['supported_asset_types'];
                $typeSource = 'infraSchedule.schedule_data.supported_asset_types';
                Log::debug("Falling back to schedule_data supported_asset_types for contract {$contract->id}");
            }
            // Final fallback to payload if schedule_data is also empty (backward compatibility)
            else {
                $payloadTypes = data_get($infraSchedule, 'payload.supported_asset_types');
                if (! empty($payloadTypes)) {
                    $supportedTypes = $payloadTypes;
                    $typeSource = 'infraSchedule.payload.supported_asset_types';
                    Log::debug("Falling back to payload supported_asset_types for contract {$contract->id}");
                }
                // Last resort fallback to contract method
                elseif (! empty($contract->getSupportedAssetTypes())) {
                    $supportedTypes = $contract->getSupportedAssetTypes();
                    $typeSource = 'contract.getSupportedAssetTypes()';
                    Log::debug("Falling back to contract getSupportedAssetTypes() for contract {$contract->id}");
                }
            }
        } else {
            Log::warning('No infrastructure schedule found for fallback', [
                'contract_id' => $contract->id,
                'infrastructure_schedules_query' => $contract->infrastructureSchedules()->toSql(),
                'effective_filter_applied' => true,
            ]);
        }

        Log::info('Supported asset types resolved', [
            'contract_id' => $contract->id,
            'supported_types_source' => $typeSource,
            'supported_types' => $supportedTypes,
            'supported_types_count' => count($supportedTypes),
            'fallback_chain_used' => $typeSource !== 'none',
        ]);

        // Comment 6: Guard against empty types early
        if (empty($supportedTypes)) {
            $fallbackTypeIds = collect();
            Log::debug("No supported asset types found for contract {$contract->id}, skipping type-based fallback");
        } else {
            // Expand category-based types to actual asset types using helper
            Log::debug('Expanding asset type categories', [
                'contract_id' => $contract->id,
                'supported_types_input' => $supportedTypes,
            ]);

            $expanded = $this->expandAssetTypeCategories($supportedTypes);

            Log::info('Asset type expansion completed', [
                'contract_id' => $contract->id,
                'original_types' => $supportedTypes,
                'expanded_types' => $expanded->toArray(),
                'expansion_successful' => ! $expanded->isEmpty(),
            ]);

            // Comment 6: Guard against empty expanded types
            if ($expanded->isEmpty()) {
                $fallbackTypeIds = collect();
                Log::warning('Asset type expansion resulted in empty list', [
                    'contract_id' => $contract->id,
                    'original_supported_types' => $supportedTypes,
                    'expansion_method' => 'expandAssetTypeCategories',
                ]);
            } else {
                // Comment 2: Normalize asset type casing to handle legacy data mismatches
                $upperTypes = $expanded->map('strtoupper')->toArray();

                $typeEligibleQuery = $assetModel->where('company_id', $contract->company_id)
                    ->where('client_id', $contract->client_id)
                    ->whereNotIn('status', self::EXCLUDED_ASSET_STATUSES)
                    ->whereIn(DB::raw('UPPER(type)'), $upperTypes);

                Log::debug('Type-eligible asset query details', [
                    'contract_id' => $contract->id,
                    'query_conditions' => [
                        'company_id' => $contract->company_id,
                        'client_id' => $contract->client_id,
                        'excluded_statuses' => self::EXCLUDED_ASSET_STATUSES,
                        'upper_types' => $upperTypes,
                    ],
                    'raw_sql' => $typeEligibleQuery->toSql(),
                    'bindings' => $typeEligibleQuery->getBindings(),
                ]);

                $fallbackTypeIds = $typeEligibleQuery->pluck('id');

                if ($fallbackTypeIds->isEmpty() && ! empty($supportedTypes)) {
                    // Enhanced debugging for empty results
                    $totalClientAssets = $assetModel->where('company_id', $contract->company_id)
                        ->where('client_id', $contract->client_id)
                        ->count();

                    $clientAssetTypes = $assetModel->where('company_id', $contract->company_id)
                        ->where('client_id', $contract->client_id)
                        ->distinct()
                        ->pluck('type')
                        ->toArray();

                    Log::warning('Type-eligible assets query returned empty results', [
                        'contract_id' => $contract->id,
                        'supported_types' => $supportedTypes,
                        'expanded_types' => $expanded->toArray(),
                        'upper_types' => $upperTypes,
                        'client_debugging' => [
                            'total_client_assets' => $totalClientAssets,
                            'available_asset_types' => $clientAssetTypes,
                            'case_comparison' => array_map('strtoupper', $clientAssetTypes),
                        ],
                        'potential_issues' => [
                            'no_assets_of_supported_types',
                            'case_mismatch',
                            'all_assets_excluded_by_status',
                            'wrong_client_or_company',
                        ],
                    ]);
                } else {
                    Log::info('Type-eligible assets found successfully', [
                        'contract_id' => $contract->id,
                        'type_eligible_count' => $fallbackTypeIds->count(),
                        'supported_types_matched' => $supportedTypes,
                    ]);
                }
            }
        }

        // Determine final asset ID set based on mode with enhanced logging
        $finalAssetIds = collect();
        $selectionMode = '';

        if ($includeTypeEligibleAlongsideAssignments && $mergedIds->isNotEmpty()) {
            // Union mode: merge assigned assets with type-eligible assets
            $finalAssetIds = $mergedIds->merge($fallbackTypeIds)->unique()->filter();
            $selectionMode = 'union';

            Log::info('Asset selection mode: Union', [
                'contract_id' => $contract->id,
                'assigned_asset_count' => $mergedIds->count(),
                'type_eligible_count' => $fallbackTypeIds->count(),
                'final_unique_count' => $finalAssetIds->count(),
                'assigned_ids' => $mergedIds->toArray(),
                'type_eligible_ids' => $fallbackTypeIds->toArray(),
                'final_ids' => $finalAssetIds->toArray(),
                'union_successful' => true,
            ]);
        } elseif ($mergedIds->isNotEmpty()) {
            // Assignment mode: use only assigned assets
            $finalAssetIds = $mergedIds;
            $selectionMode = 'assignment_only';

            Log::info('Asset selection mode: Assignment only', [
                'contract_id' => $contract->id,
                'assigned_asset_count' => $mergedIds->count(),
                'assigned_ids' => $mergedIds->toArray(),
                'type_eligible_available_but_not_used' => $fallbackTypeIds->count(),
                'mode_reason' => 'assigned_assets_found_and_union_not_requested',
            ]);
        } else {
            // Fallback mode: use type-eligible assets (backward compatibility)
            $finalAssetIds = $fallbackTypeIds;
            $selectionMode = 'fallback_to_type_eligible';

            Log::info('Asset selection mode: Fallback to type-eligible', [
                'contract_id' => $contract->id,
                'no_assigned_assets' => true,
                'fallback_asset_count' => $fallbackTypeIds->count(),
                'fallback_ids' => $fallbackTypeIds->toArray(),
                'fallback_reason' => 'no_direct_or_explicit_assignments',
            ]);
        }

        // Add debugging for empty results with detailed failure analysis
        if ($finalAssetIds->isEmpty()) {
            Log::warning('Final asset IDs collection is empty - comprehensive debugging', [
                'contract_id' => $contract->id,
                'selection_mode' => $selectionMode,
                'assignment_details' => [
                    'direct_assignments' => $directlyAssignedIds->count(),
                    'explicit_assignments' => $explicitlyAssignedIds->count(),
                    'merged_assignments' => $mergedIds->count(),
                ],
                'fallback_details' => [
                    'supported_types_found' => ! empty($supportedTypes),
                    'type_source' => $typeSource,
                    'type_eligible_count' => $fallbackTypeIds->count(),
                ],
                'debugging_suggestions' => [
                    'check_asset_assignment_in_contract_service',
                    'verify_supporting_contract_id_field',
                    'check_contractassetassignment_table',
                    'verify_supported_asset_types_in_schedule_a',
                    'check_client_and_company_ids_match',
                ],
            ]);

            return collect();
        }

        // Add comprehensive asset retrieval verification with enhanced logging
        $assetQuery = $assetModel->whereIn('id', $finalAssetIds)
            ->where('company_id', $contract->company_id)
            ->where('client_id', $contract->client_id)
            ->whereNotIn('status', self::EXCLUDED_ASSET_STATUSES)
            ->with(['location', 'vendor'])
            ->orderBy('type')
            ->orderBy('name');

        Log::debug('Final asset retrieval query details', [
            'contract_id' => $contract->id,
            'final_asset_ids' => $finalAssetIds->toArray(),
            'final_asset_count' => $finalAssetIds->count(),
            'query_conditions' => [
                'company_id' => $contract->company_id,
                'client_id' => $contract->client_id,
                'excluded_statuses' => self::EXCLUDED_ASSET_STATUSES,
                'with_relationships' => ['location', 'vendor'],
            ],
            'raw_sql' => $assetQuery->toSql(),
            'bindings' => $assetQuery->getBindings(),
        ]);

        $assets = $assetQuery->get();

        // Add comprehensive asset retrieval verification
        Log::info('Asset retrieval completed with verification', [
            'contract_id' => $contract->id,
            'expected_asset_count' => $finalAssetIds->count(),
            'actual_retrieved_count' => $assets->count(),
            'retrieval_successful' => $assets->count() === $finalAssetIds->count(),
            'selection_mode_used' => $selectionMode,
            'asset_details' => $assets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'type' => $asset->type,
                    'client_id' => $asset->client_id,
                    'company_id' => $asset->company_id,
                    'supporting_contract_id' => $asset->supporting_contract_id,
                    'status' => $asset->status,
                ];
            })->toArray(),
            'asset_types_summary' => $assets->groupBy('type')->map->count()->toArray(),
        ]);

        // Add verification that all retrieved assets belong to the correct client and company
        $clientMismatches = $assets->where('client_id', '!=', $contract->client_id)->count();
        $companyMismatches = $assets->where('company_id', '!=', $contract->company_id)->count();

        if ($clientMismatches > 0 || $companyMismatches > 0) {
            Log::error('Asset retrieval verification failed - client/company mismatch', [
                'contract_id' => $contract->id,
                'client_mismatches' => $clientMismatches,
                'company_mismatches' => $companyMismatches,
                'expected_client_id' => $contract->client_id,
                'expected_company_id' => $contract->company_id,
            ]);
        }

        // Add final method summary
        Log::info('TemplateVariableMapper getClientAssetsForContract completed', [
            'contract_id' => $contract->id,
            'final_asset_count' => $assets->count(),
            'method_successful' => true,
            'execution_summary' => [
                'direct_assignments_found' => $directlyAssignedIds->count(),
                'explicit_assignments_found' => $explicitlyAssignedIds->count(),
                'type_eligible_found' => $fallbackTypeIds->count(),
                'final_selection_mode' => $selectionMode,
                'assets_successfully_retrieved' => $assets->count(),
            ],
        ]);

        return $assets;
    }

    /**
     * Generate HTML formatted asset list
     */
    protected function generateAssetListHtml($assets): string
    {
        if ($assets->isEmpty()) {
            return '<p><em>No assets assigned to this contract.</em></p>';
        }

        $html = '<div class="asset-listing">';
        $html .= '<table border="1" style="width:100%; border-collapse:collapse; margin:20px 0;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color:#f5f5f5;">';
        $html .= '<th style="padding:8px; text-align:left;">Asset Name</th>';
        $html .= '<th style="padding:8px; text-align:left;">Type</th>';
        $html .= '<th style="padding:8px; text-align:left;">Make/Model</th>';
        $html .= '<th style="padding:8px; text-align:left;">IP Address</th>';
        $html .= '<th style="padding:8px; text-align:left;">Status</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($assets as $asset) {
            $html .= '<tr>';
            $html .= '<td style="padding:8px;">'.htmlspecialchars($asset->name ?? 'N/A').'</td>';
            $html .= '<td style="padding:8px;">'.htmlspecialchars(ucwords(str_replace('_', ' ', $asset->type ?? 'Unknown'))).'</td>';
            $html .= '<td style="padding:8px;">'.htmlspecialchars(trim(($asset->make ?? '').' '.($asset->model ?? '')) ?: 'N/A').'</td>';
            $html .= '<td style="padding:8px;">'.htmlspecialchars($asset->ip ?? 'N/A').'</td>';
            $html .= '<td style="padding:8px;">'.htmlspecialchars(ucfirst($asset->status ?? 'Unknown')).'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate assets grouped by type HTML
     */
    protected function generateAssetsByTypeHtml($assetsByType): string
    {
        if (empty($assetsByType)) {
            return '<p><em>No assets assigned to this contract.</em></p>';
        }

        $html = '<div class="assets-by-type">';

        foreach ($assetsByType as $type => $assets) {
            $typeDisplay = $this->ASSET_TYPE_NAMES[$type] ?? ucwords(str_replace('_', ' ', $type));
            $html .= '<h4>'.$typeDisplay.' ('.$assets->count().')</h4>';
            $html .= '<ul>';

            foreach ($assets as $asset) {
                $html .= '<li>'.htmlspecialchars($asset->name);
                if ($asset->ip) {
                    $html .= ' ('.htmlspecialchars($asset->ip).')';
                }
                if ($asset->make && $asset->model) {
                    $html .= ' - '.htmlspecialchars($asset->make.' '.$asset->model);
                }
                $html .= '</li>';
            }

            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate asset count summary
     */
    protected function generateAssetCountSummary($assetsByType): string
    {
        if (empty($assetsByType)) {
            return '<p><em>No assets assigned to this contract.</em></p>';
        }

        $html = '<div class="asset-count-summary">';
        $html .= '<ul>';

        $totalCount = 0;
        foreach ($assetsByType as $type => $assets) {
            $typeDisplay = $this->ASSET_TYPE_NAMES[$type] ?? ucwords(str_replace('_', ' ', $type));
            $count = $assets->count();
            $totalCount += $count;

            $html .= '<li><strong>'.$typeDisplay.':</strong> '.$count.' '.($count === 1 ? 'asset' : 'assets').'</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate professional asset table for contracts
     */
    protected function generateProfessionalAssetTable($assets): string
    {
        if ($assets->isEmpty()) {
            return '<p><em>Asset inventory will be populated when assets are assigned to this contract.</em></p>';
        }

        $html = '<div class="professional-asset-table">';
        $html .= '<p>The following specific assets are covered under this managed service agreement:</p>';

        // Normalize asset types before grouping to prevent case-related splits
        $normalizedAssets = $assets->map(fn ($a) => tap($a, fn ($x) => $x->type = ucfirst(strtolower($x->type))));

        // Group by normalized type for better organization
        $assetsByType = $normalizedAssets->groupBy('type');

        foreach ($assetsByType as $type => $typeAssets) {
            $typeDisplay = $this->ASSET_TYPE_NAMES[$type] ?? ucwords(str_replace('_', ' ', $type));

            $html .= '<h4>'.$typeDisplay.' ('.$typeAssets->count().' assets)</h4>';
            $html .= '<table border="1" style="width:100%; border-collapse:collapse; margin:10px 0 20px 0; font-size:11px;">';
            $html .= '<thead>';
            $html .= '<tr style="background-color:#e9e9e9;">';
            $html .= '<th style="padding:6px; text-align:left; font-weight:bold;">Asset Name</th>';
            $html .= '<th style="padding:6px; text-align:left; font-weight:bold;">Manufacturer</th>';
            $html .= '<th style="padding:6px; text-align:left; font-weight:bold;">Model</th>';
            $html .= '<th style="padding:6px; text-align:left; font-weight:bold;">IP Address</th>';
            $html .= '<th style="padding:6px; text-align:left; font-weight:bold;">Status</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($typeAssets as $asset) {
                $html .= '<tr>';
                $html .= '<td style="padding:6px;">'.htmlspecialchars($asset->name ?? 'Unnamed Asset').'</td>';
                $html .= '<td style="padding:6px;">'.htmlspecialchars($asset->make ?? 'N/A').'</td>';
                $html .= '<td style="padding:6px;">'.htmlspecialchars($asset->model ?? 'N/A').'</td>';
                $html .= '<td style="padding:6px;">'.htmlspecialchars($asset->ip ?? 'N/A').'</td>';
                $html .= '<td style="padding:6px;">'.htmlspecialchars(ucfirst($asset->status ?? 'Unknown')).'</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        $html .= '<p><em>All assets listed above are subject to the service levels, response times, and terms specified in this agreement.</em></p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate VoIP-specific variables from telecom schedule
     */
    protected function generateVoipVariables(Contract $contract): array
    {
        $variables = [];
        $schedules = $contract->schedules;

        // Set default boolean variables to false for VoIP conditionals
        $variables['fcc_compliant'] = false;
        $variables['karis_law'] = false;
        $variables['ray_baums'] = false;
        $variables['encryption_enabled'] = false;
        $variables['fraud_protection'] = false;
        $variables['call_recording'] = false;

        $telecomSchedule = $schedules->where('schedule_type', 'telecom')->first();
        if ($telecomSchedule) {
            $telecomData = $telecomSchedule->schedule_data;

            // Channel configuration
            $variables['channel_count'] = $telecomData['channelCount'] ?? 10;
            $variables['calling_plan'] = $telecomData['callingPlan'] ?? 'local_long_distance';
            $variables['international_calling'] = $telecomData['internationalCalling'] ?? 'additional';
            $variables['emergency_services'] = $telecomData['emergencyServices'] ?? 'enabled';
            $variables['protocol'] = $telecomData['protocol'] ?? 'sip';

            // QoS metrics
            $qos = $telecomData['qos'] ?? [];
            $variables['mos_score'] = $qos['meanOpinionScore'] ?? '4.2';
            $variables['jitter_ms'] = $qos['jitterMs'] ?? 30;
            $variables['packet_loss_percent'] = $qos['packetLossPercent'] ?? 0.1;
            $variables['telecom_uptime_percent'] = $qos['uptimePercent'] ?? '99.9';
            $variables['max_outage_duration'] = $qos['maxOutageDuration'] ?? '4 hours';
            $variables['latency_ms'] = $qos['latencyMs'] ?? 80;
            $variables['telecom_response_time'] = $qos['responseTimeHours'] ?? 1;
            $variables['telecom_resolution_time'] = $qos['resolutionTimeHours'] ?? 8;
            $variables['support_coverage'] = $qos['supportCoverage'] ?? '24x7';

            // Carrier information
            $carrier = $telecomData['carrier'] ?? [];
            $variables['primary_carrier'] = $carrier['primary'] ?? '';
            $variables['backup_carrier'] = $carrier['backup'] ?? '';

            // Compliance features
            $compliance = $telecomData['compliance'] ?? [];
            $variables['fcc_compliant'] = $compliance['fccCompliant'] ?? true;
            $variables['karis_law'] = $compliance['karisLaw'] ?? true;
            $variables['ray_baums'] = $compliance['rayBaums'] ?? true;

            // Security features
            $security = $telecomData['security'] ?? [];
            $variables['encryption_enabled'] = $security['encryption'] ?? true;
            $variables['fraud_protection'] = $security['fraudProtection'] ?? true;
            $variables['call_recording'] = $security['callRecording'] ?? false;
        }

        return $variables;
    }

    /**
     * Generate VAR-specific variables from hardware schedule
     */
    protected function generateVarVariables(Contract $contract): array
    {
        $variables = [];
        $schedules = $contract->schedules;

        // Set default boolean variables to false for VAR conditionals
        $variables['includes_installation'] = false;
        $variables['includes_rack_stack'] = false;
        $variables['includes_cabling'] = false;
        $variables['includes_configuration'] = false;
        $variables['includes_project_management'] = false;
        $variables['onsite_warranty_support'] = false;
        $variables['advanced_replacement'] = false;

        $hardwareSchedule = $schedules->where('schedule_type', 'hardware')->first();
        if ($hardwareSchedule) {
            $hardwareData = $hardwareSchedule->schedule_data;

            // Product categories
            $selectedCategories = $hardwareData['selectedCategories'] ?? [];
            $variables['hardware_categories'] = implode(', ', $selectedCategories);
            $variables['procurement_model'] = $hardwareData['procurementModel'] ?? 'direct_resale';
            $variables['lead_time_days'] = $hardwareData['leadTimeDays'] ?? 5;
            $variables['lead_time_type'] = $hardwareData['leadTimeType'] ?? 'business_days';

            // Installation services
            $services = $hardwareData['services'] ?? [];
            $variables['includes_installation'] = $services['basicInstallation'] ?? false;
            $variables['includes_rack_stack'] = $services['rackAndStack'] ?? false;
            $variables['includes_cabling'] = $services['cabling'] ?? false;
            $variables['includes_configuration'] = $services['basicConfiguration'] ?? false;
            $variables['includes_project_management'] = $services['projectManagement'] ?? false;

            // SLA terms
            $sla = $hardwareData['sla'] ?? [];
            $variables['installation_timeline'] = $sla['installationTimeline'] ?? 'Within 5 business days';
            $variables['configuration_timeline'] = $sla['configurationTimeline'] ?? 'Within 2 business days';
            $variables['hardware_support_response'] = $sla['supportResponse'] ?? '4_hours';

            // Warranty
            $warranty = $hardwareData['warranty'] ?? [];
            $variables['hardware_warranty_period'] = $warranty['hardwarePeriod'] ?? '1_year';
            $variables['support_warranty_period'] = $warranty['supportPeriod'] ?? '1_year';
            $variables['onsite_warranty_support'] = $warranty['onSiteSupport'] ?? false;
            $variables['advanced_replacement'] = $warranty['advancedReplacement'] ?? false;

            // Pricing
            $pricing = $hardwareData['pricing'] ?? [];
            $variables['markup_model'] = $pricing['markupModel'] ?? 'fixed_percentage';
            $variables['installation_rate'] = $pricing['installationRate'] ?? '';
            $variables['configuration_rate'] = $pricing['configurationRate'] ?? '';
            $variables['hardware_payment_terms'] = $pricing['hardwarePaymentTerms'] ?? 'net_30';
        }

        return $variables;
    }

    /**
     * Generate Compliance-specific variables from compliance schedule
     */
    protected function generateComplianceVariables(Contract $contract): array
    {
        $variables = [];
        $schedules = $contract->schedules;

        // Set default boolean variables to false for compliance conditionals
        $variables['includes_internal_audits'] = false;
        $variables['includes_external_audits'] = false;
        $variables['includes_penetration_testing'] = false;
        $variables['includes_vulnerability_scanning'] = false;

        $complianceSchedule = $schedules->where('schedule_type', 'compliance')->first();
        if ($complianceSchedule) {
            $complianceData = $complianceSchedule->schedule_data;

            // Framework selection
            $frameworks = $complianceData['selectedFrameworks'] ?? [];
            $variables['compliance_frameworks'] = implode(', ', $frameworks);
            $variables['compliance_scope'] = $complianceData['scope'] ?? '';
            $variables['risk_level'] = $complianceData['riskLevel'] ?? 'medium';
            $variables['industry_sector'] = $complianceData['industrySector'] ?? '';

            // Audit configuration
            $audits = $complianceData['audits'] ?? [];
            $variables['includes_internal_audits'] = $audits['internal'] ?? false;
            $variables['includes_external_audits'] = $audits['external'] ?? false;
            $variables['includes_penetration_testing'] = $audits['penetrationTesting'] ?? false;
            $variables['includes_vulnerability_scanning'] = $audits['vulnerabilityScanning'] ?? false;

            // Frequency
            $frequency = $complianceData['frequency'] ?? [];
            $variables['comprehensive_audit_frequency'] = $frequency['comprehensive'] ?? 'annually';
            $variables['interim_audit_frequency'] = $frequency['interim'] ?? 'quarterly';
            $variables['vulnerability_scan_frequency'] = $frequency['vulnerability'] ?? 'monthly';

            // Training
            $training = $complianceData['training'] ?? [];
            $variables['training_programs'] = implode(', ', $training['selectedPrograms'] ?? []);
            $variables['training_delivery_method'] = $training['deliveryMethod'] ?? 'online';
            $variables['training_frequency'] = $training['frequency'] ?? 'annually';

            // Response times
            $response = $complianceData['response'] ?? [];
            $variables['critical_response_time'] = $response['criticalTime'] ?? '1_hour';
            $variables['high_response_time'] = $response['highTime'] ?? '4_hours';
            $variables['standard_response_time'] = $response['standardTime'] ?? '24_hours';
        }

        return $variables;
    }

    /**
     * Generate General template variables
     */
    protected function generateGeneralVariables(Contract $contract): array
    {
        $variables = [];

        // Add flexible variables based on contract metadata
        if ($contract->metadata) {
            $variables = array_merge($variables, $contract->metadata);
        }

        return $variables;
    }

    /**
     * Format asset types list for display in clauses
     */
    protected function formatAssetTypesList(array $assetTypes): string
    {
        if (empty($assetTypes)) {
            return '';
        }

        $names = array_map(function ($type) {
            return self::ASSET_TYPE_NAMES[$type] ?? ucwords(str_replace('_', ' ', $type));
        }, $assetTypes);

        if (count($names) === 1) {
            return $names[0];
        }

        if (count($names) === 2) {
            return implode(' and ', $names);
        }

        $last = array_pop($names);

        return implode(', ', $names).', and '.$last;
    }

    /**
     * Check if specific asset type category is supported
     */
    public function hasAssetTypeCategory(array $assetTypes, string $category): bool
    {
        return match ($category) {
            'hypervisor' => in_array('hypervisor_node', $assetTypes),
            'server' => in_array('server', $assetTypes) || in_array('hypervisor_node', $assetTypes),
            'workstation' => in_array('workstation', $assetTypes),
            'network' => in_array('network_device', $assetTypes),
            'mobile' => in_array('mobile_device', $assetTypes),
            'storage' => in_array('storage', $assetTypes),
            'security' => in_array('security_device', $assetTypes),
            'printer' => in_array('printer', $assetTypes),
            default => false
        };
    }

    /**
     * Get accepted payment methods based on enabled gateways
     */
    protected function getAcceptedPaymentMethods($company): array
    {
        if (! $company || ! $company->setting) {
            return [];
        }

        $setting = $company->setting;
        $methods = [];

        if ($setting->stripe_enabled) {
            $methods[] = 'Credit Card';
        }

        if ($setting->paypal_enabled) {
            $methods[] = 'PayPal';
        }

        if ($setting->ach_enabled) {
            $methods[] = 'ACH';
        }

        if ($setting->wire_enabled) {
            $methods[] = 'Wire Transfer';
        }

        if ($setting->check_enabled) {
            $methods[] = 'Check';
        }

        return $methods;
    }

    /**
     * Get formatted payment methods string for contract templates
     */
    protected function getFormattedPaymentMethods($company): string
    {
        $methods = $this->getAcceptedPaymentMethods($company);

        if (empty($methods)) {
            return 'ACH or Wire Transfer'; // Default fallback
        }

        if (count($methods) === 1) {
            return $methods[0];
        }

        if (count($methods) === 2) {
            return implode(' and ', $methods);
        }

        // For 3+ methods: "Method1, Method2, and Method3"
        $last = array_pop($methods);

        return implode(', ', $methods).', and '.$last;
    }

    /**
     * Check if company accepts credit cards
     */
    protected function acceptsCreditCards($company): bool
    {
        if (! $company || ! $company->setting) {
            return false;
        }

        // Credit cards are typically processed through Stripe or other gateways
        return $company->setting->stripe_enabled || $company->setting->paypal_enabled;
    }

    /**
     * Check if company accepts ACH payments
     */
    protected function acceptsAch($company): bool
    {
        if (! $company || ! $company->setting) {
            return false;
        }

        return $company->setting->ach_enabled;
    }

    /**
     * Check if company accepts wire transfers
     */
    protected function acceptsWireTransfer($company): bool
    {
        if (! $company || ! $company->setting) {
            return false;
        }

        return $company->setting->wire_enabled;
    }

    /**
     * Check if company accepts check payments
     */
    protected function acceptsChecks($company): bool
    {
        if (! $company || ! $company->setting) {
            return false;
        }

        return $company->setting->check_enabled;
    }

    /**
     * Format term months into readable format
     */
    protected function formatTerm(int $months): string
    {
        if ($months === 12) {
            return 'one (1) year';
        } elseif ($months === 24) {
            return 'two (2) years';
        } elseif ($months === 36) {
            return 'three (3) years';
        } elseif ($months % 12 === 0) {
            $years = $months / 12;

            return "$years ($years) years";
        } elseif ($months === 1) {
            return 'one (1) month';
        } else {
            return "$months ($months) months";
        }
    }

    /**
     * Generate dynamic section reference variables from section mapping.
     */
    protected function generateSectionReferenceVariables(array $sectionMapping): array
    {
        $variables = [];

        // Generate standard section reference variables
        foreach ($sectionMapping as $category => $data) {
            $variableName = $category.'_section_ref';
            $variables[$variableName] = $data['reference'];
        }

        // Add common alternative names for easier template usage
        $commonMappings = [
            'definitions_section_ref' => $sectionMapping['definitions']['reference'] ?? 'DEFINITIONS SECTION NOT PRESENT',
            'services_section_ref' => $sectionMapping['services']['reference'] ?? 'SERVICES SECTION NOT PRESENT',
            'sla_section_ref' => $sectionMapping['sla']['reference'] ?? 'SLA SECTION NOT PRESENT',
            'obligations_section_ref' => $sectionMapping['obligations']['reference'] ?? 'OBLIGATIONS SECTION NOT PRESENT',
            'financial_section_ref' => $sectionMapping['financial']['reference'] ?? 'FINANCIAL SECTION NOT PRESENT',
            'exclusions_section_ref' => $sectionMapping['exclusions']['reference'] ?? 'EXCLUSIONS SECTION NOT PRESENT',
            'warranties_section_ref' => $sectionMapping['warranties']['reference'] ?? 'WARRANTIES SECTION NOT PRESENT',
            'confidentiality_section_ref' => $sectionMapping['confidentiality']['reference'] ?? 'CONFIDENTIALITY SECTION NOT PRESENT',
            'legal_section_ref' => $sectionMapping['legal']['reference'] ?? 'LEGAL SECTION NOT PRESENT',
            'admin_section_ref' => $sectionMapping['admin']['reference'] ?? 'ADMIN SECTION NOT PRESENT',
        ];

        return array_merge($variables, $commonMappings);
    }

    /**
     * Extract variables from contract metadata for wizard-created contracts
     */
    protected function extractContractMetadataVariables(Contract $contract): array
    {
        $variables = [];
        $metadata = $contract->metadata ?? [];

        // PRIORITY 1: Extract variables from variable_values in metadata (primary wizard data)
        $variableValues = $metadata['variable_values'] ?? [];
        if (! empty($variableValues)) {
            $variables = array_merge($variables, $variableValues);

            // Special processing for term_months from wizard data - generate formatted terms if not already provided
            if (! empty($variableValues['term_months']) && ! isset($variableValues['initial_term'])) {
                $termMonths = (int) $variableValues['term_months'];
                $variables['initial_term'] = $this->formatTerm($termMonths);
                $variables['renewal_term'] = $this->formatTerm($termMonths);
            }
        }

        // PRIORITY 2: Extract from standard contract form fields
        $this->extractFromContractFields($contract, $variables);

        // PRIORITY 3: Extract from SLA terms if present (form field data)
        $this->extractFromSlaTerms($contract, $variables);

        // PRIORITY 4: Extract from pricing structure if present (form field data)
        $this->extractFromPricingStructure($contract, $variables);

        // PRIORITY 5: Extract from VoIP specifications (telecom contracts)
        $this->extractFromVoipSpecifications($contract, $variables);

        // PRIORITY 6: Extract from compliance requirements (compliance contracts)
        $this->extractFromComplianceRequirements($contract, $variables);

        // PRIORITY 7: Extract billing configuration from metadata
        $this->extractFromBillingConfig($metadata, $variables);

        // PRIORITY 8: If contract has stored variables column, merge those too (lowest priority)
        if (! empty($contract->variables) && is_array($contract->variables)) {
            // Merge with existing data taking precedence over stored variables
            $contractVariables = $contract->variables;
            $variables = array_merge($contractVariables, $variables);
        }

        return $variables;
    }

    /**
     * Extract variables from core contract fields
     */
    protected function extractFromContractFields(Contract $contract, array &$variables): void
    {
        $fieldMappings = [
            'title' => 'contract_title',
            'contract_type' => 'contract_type',
            'description' => 'contract_description',
            'currency_code' => 'currency_code',
            'payment_terms' => 'payment_terms',
            'jurisdiction' => 'jurisdiction',
            'term_months' => 'term_months',
        ];

        $this->extractFields($contract, $variables, $fieldMappings);

        // Special processing for governing_law to resolve form selections
        if (! empty($contract->governing_law)) {
            $variables['governing_law'] = $this->resolveGoverningLaw($contract);
            $variables['governing_state'] = $this->resolveGoverningLaw($contract);
        }

        // Special processing for term_months to generate formatted terms
        if (! empty($contract->term_months) && ! isset($variables['initial_term'])) {
            $termMonths = (int) $contract->term_months;
            $variables['initial_term'] = $this->formatTerm($termMonths);
            $variables['renewal_term'] = $this->formatTerm($termMonths);
        }
    }

    /**
     * Extract variables from SLA terms
     */
    protected function extractFromSlaTerms(Contract $contract, array &$variables): void
    {
        if (empty($contract->sla_terms)) {
            return;
        }

        $slaTerms = $contract->sla_terms;

        if (! empty($slaTerms['response_time_hours'])) {
            $variables['response_time_hours'] = (string) $slaTerms['response_time_hours'];
        }
        if (! empty($slaTerms['resolution_time_hours'])) {
            $variables['resolution_time_hours'] = (string) $slaTerms['resolution_time_hours'];
        }
        if (! empty($slaTerms['uptime_percentage'])) {
            $variables['uptime_percentage'] = (string) $slaTerms['uptime_percentage'];
        }
        if (! empty($slaTerms['service_tier'])) {
            $variables['service_tier'] = $slaTerms['service_tier'];
        }
        if (! empty($slaTerms['business_hours'])) {
            $variables['business_hours'] = $slaTerms['business_hours'];
        }
    }

    /**
     * Extract variables from pricing structure
     */
    protected function extractFromPricingStructure(Contract $contract, array &$variables): void
    {
        if (empty($contract->pricing_structure)) {
            return;
        }

        $pricingStructure = $contract->pricing_structure;

        if (! empty($pricingStructure['recurring_monthly'])) {
            $variables['monthly_base_rate'] = '$'.number_format((float) $pricingStructure['recurring_monthly'], 2);
        }
        if (! empty($pricingStructure['setup_fee'])) {
            $variables['setup_fee'] = '$'.number_format((float) $pricingStructure['setup_fee'], 2);
        }
        if (! empty($pricingStructure['one_time'])) {
            $variables['hourly_rate'] = '$'.number_format((float) $pricingStructure['one_time'], 2);
        }
        if (! empty($pricingStructure['billing_model'])) {
            $variables['billing_model'] = $pricingStructure['billing_model'];
        }
        if (! empty($pricingStructure['billing_frequency'])) {
            $variables['billing_frequency'] = $pricingStructure['billing_frequency'];
        }
    }

    /**
     * Extract variables from VoIP specifications (telecom contracts)
     */
    protected function extractFromVoipSpecifications(Contract $contract, array &$variables): void
    {
        if (empty($contract->voip_specifications)) {
            return;
        }

        $voipSpecs = $contract->voip_specifications;

        if (! empty($voipSpecs['services'])) {
            $variables['voip_services'] = is_array($voipSpecs['services'])
                ? implode(', ', $voipSpecs['services'])
                : $voipSpecs['services'];
        }
        if (! empty($voipSpecs['equipment'])) {
            $variables['voip_equipment'] = is_array($voipSpecs['equipment'])
                ? implode(', ', $voipSpecs['equipment'])
                : $voipSpecs['equipment'];
        }
        if (! empty($voipSpecs['phone_numbers'])) {
            $variables['phone_numbers'] = (string) $voipSpecs['phone_numbers'];
        }
        if (! empty($voipSpecs['channel_count'])) {
            $variables['channel_count'] = (string) $voipSpecs['channel_count'];
        }
        if (! empty($voipSpecs['calling_plan'])) {
            $variables['calling_plan'] = $voipSpecs['calling_plan'];
        }
        if (! empty($voipSpecs['international_calling'])) {
            $variables['international_calling'] = $voipSpecs['international_calling'];
        }
    }

    /**
     * Extract variables from compliance requirements (compliance contracts)
     */
    protected function extractFromComplianceRequirements(Contract $contract, array &$variables): void
    {
        if (empty($contract->compliance_requirements)) {
            return;
        }

        $compliance = $contract->compliance_requirements;

        if (! empty($compliance['frameworks'])) {
            $variables['compliance_frameworks'] = is_array($compliance['frameworks'])
                ? implode(', ', $compliance['frameworks'])
                : $compliance['frameworks'];
        }
        if (! empty($compliance['scope'])) {
            $variables['compliance_scope'] = $compliance['scope'];
        }
        if (! empty($compliance['risk_level'])) {
            $variables['risk_level'] = $compliance['risk_level'];
        }
        if (! empty($compliance['audit_frequency'])) {
            $variables['audit_frequency'] = $compliance['audit_frequency'];
        }
        if (! empty($compliance['industry_sector'])) {
            $variables['industry_sector'] = $compliance['industry_sector'];
        }
    }

    /**
     * Extract variables from billing configuration in metadata
     */
    protected function extractFromBillingConfig(array $metadata, array &$variables): void
    {
        $billingConfig = $metadata['billing_config'] ?? [];
        if (empty($billingConfig)) {
            return;
        }

        if (! empty($billingConfig['model']) && empty($variables['billing_model'])) {
            $variables['billing_model'] = $billingConfig['model'];
        }
        if (! empty($billingConfig['base_rate']) && empty($variables['monthly_base_rate'])) {
            $variables['monthly_base_rate'] = '$'.number_format((float) $billingConfig['base_rate'], 2);
        }

        // Auto-assignment settings
        $variables['auto_assign_assets'] = $billingConfig['auto_assign_assets'] ?? false;
        $variables['auto_assign_new_assets'] = $billingConfig['auto_assign_new_assets'] ?? false;
        $variables['auto_assign_contacts'] = $billingConfig['auto_assign_contacts'] ?? false;
        $variables['auto_assign_new_contacts'] = $billingConfig['auto_assign_new_contacts'] ?? false;
    }

    /**
     * Generic method to extract fields from a model object
     */
    protected function extractFields(Contract $contract, array &$variables, array $fieldMappings): void
    {
        foreach ($fieldMappings as $sourceField => $targetField) {
            $value = $contract->{$sourceField} ?? null;
            if (! empty($value)) {
                $variables[$targetField] = $value;
            }
        }
    }

    /**
     * Generic method to extract and format fields from an array
     */
    protected function extractFromArray(array $sourceArray, array &$variables, array $fieldMappings): void
    {
        foreach ($fieldMappings as $sourceField => $config) {
            $value = $sourceArray[$sourceField] ?? null;

            if (empty($value)) {
                // Handle default values
                if (isset($config['default'])) {
                    $variables[$config['key']] = $config['default'];
                }

                continue;
            }

            // Skip if target variable already exists and skip_if_exists is true
            if (isset($config['skip_if_exists']) && $config['skip_if_exists'] && ! empty($variables[$config['key']])) {
                continue;
            }

            // Apply formatting or casting
            $formattedValue = $this->formatValue($value, $config);
            $variables[$config['key']] = $formattedValue;
        }
    }

    /**
     * Format a value based on the configuration
     */
    protected function formatValue($value, array $config)
    {
        // Handle casting
        if (isset($config['cast'])) {
            $value = match ($config['cast']) {
                'string' => (string) $value,
                'float' => (float) $value,
                'int' => (int) $value,
                'bool' => (bool) $value,
                default => $value
            };
        }

        // Handle formatting
        if (isset($config['format'])) {
            $value = match ($config['format']) {
                'currency' => '$'.number_format((float) $value, 2),
                'array_to_string' => is_array($value) ? implode(', ', $value) : $value,
                default => $value
            };
        }

        return $value;
    }

    /**
     * Get initial term variable with intelligent fallbacks
     */
    protected function getInitialTermVariable(Contract $contract): string
    {
        // Try to get term_months from various sources
        $termMonths = $this->getTermMonths($contract);

        return $this->formatTerm($termMonths);
    }

    /**
     * Get renewal term variable with intelligent fallbacks
     */
    protected function getRenewalTermVariable(Contract $contract): string
    {
        // Try to get term_months from various sources
        $termMonths = $this->getTermMonths($contract);

        return $this->formatTerm($termMonths);
    }

    /**
     * Get term months with intelligent fallbacks from multiple sources
     */
    protected function getTermMonths(Contract $contract): int
    {
        // Priority 1: Direct contract field
        if (! empty($contract->term_months)) {
            return (int) $contract->term_months;
        }

        // Priority 2: From variable_values in metadata (wizard data)
        $metadata = $contract->metadata ?? [];
        $variableValues = $metadata['variable_values'] ?? [];
        if (! empty($variableValues['term_months'])) {
            return (int) $variableValues['term_months'];
        }

        // Priority 3: From contract variables field
        if (! empty($contract->variables['term_months'])) {
            return (int) $contract->variables['term_months'];
        }

        // Priority 4: Calculate from start_date and end_date if available
        if (! empty($contract->start_date) && ! empty($contract->end_date)) {
            $startDate = \Carbon\Carbon::parse($contract->start_date);
            $endDate = \Carbon\Carbon::parse($contract->end_date);
            $diffInMonths = $startDate->diffInMonths($endDate);
            if ($diffInMonths > 0) {
                return $diffInMonths;
            }
        }

        // Fallback: Default to 12 months
        return 12;
    }

    /**
     * Resolve governing law from form selections to actual state values
     */
    protected function resolveGoverningLaw(Contract $contract): string
    {
        $governingLaw = $contract->governing_law;

        // Handle form selection values and resolve them to actual state names
        if ($governingLaw === 'provider_state') {
            return $contract->company->state ?? 'Texas';
        }

        if ($governingLaw === 'client_state') {
            return $contract->client->state ?? 'Texas';
        }

        // If it's already a state name or other value, use it directly
        if (! empty($governingLaw)) {
            return $governingLaw;
        }

        // Fallback: Use client's state or default to Texas
        return $contract->client->state ?? 'Texas';
    }

    /**
     * Expand asset type categories to concrete asset types
     *
     * @param  array  $categories  Array of asset type categories
     * @return \Illuminate\Support\Collection Collection of concrete asset types
     */
    protected function expandAssetTypeCategories(array $categories)
    {
        // Map category-based types to actual asset types from Asset::TYPES
        // Note: Only map to types that exist in Asset::TYPES to avoid empty queries
        $typeMap = [
            'workstation' => ['Desktop', 'Laptop'],
            'mobile_device' => ['Tablet', 'Phone'],
            'network_device' => ['Switch', 'Router', 'Firewall', 'Access Point'],
            'server' => ['Server'],
            'printer' => ['Printer'],
            'storage' => ['Storage'], // Added Storage to Asset::TYPES
            'security_device' => ['Firewall'], // Firewall exists in Asset::TYPES
        ];

        return collect($categories)->flatMap(fn ($category) => $typeMap[$category] ?? [$category])->unique()->values();
    }
}
