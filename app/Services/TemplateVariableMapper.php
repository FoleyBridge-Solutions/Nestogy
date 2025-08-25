<?php

namespace App\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Models\ContractSchedule;
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
                'Email & phone support'
            ]
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
                'Quarterly reviews'
            ]
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
                'Monthly reviews & reporting'
            ]
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
                'Proactive monitoring included'
            ]
        ]
    ];

    /**
     * Generate template-specific variables from contract and schedule data
     */
    public function generateVariables(Contract $contract, ?array $sectionMapping = null): array
    {
        Log::info('🔧 TemplateVariableMapper: Starting variable generation', [
            'contract_id' => $contract->id,
            'template_id' => $contract->template_id,
            'has_metadata' => !empty($contract->metadata),
            'metadata_count' => $contract->metadata ? count($contract->metadata) : 0
        ]);
        
        $baseVariables = $this->generateBaseVariables($contract, $sectionMapping);
        Log::info('📊 Base variables generated', [
            'count' => count($baseVariables),
            'keys' => array_keys($baseVariables)
        ]);
        
        $templateCategory = $this->getTemplateCategory($contract->template, $contract->contract_type);
        Log::info('📋 Template category determined', [
            'category' => $templateCategory,
            'template_type' => $contract->template?->template_type,
            'contract_type' => $contract->contract_type
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
            'keys' => array_keys($categoryVariables)
        ]);

        // Merge in contract-specific variables from metadata
        $contractVariables = $this->extractContractMetadataVariables($contract);
        Log::info('📝 Contract metadata variables extracted', [
            'count' => count($contractVariables),
            'wizard_variables' => array_intersect_key($contractVariables, array_flip([
                'billing_model', 'service_tier', 'payment_terms', 'response_time_hours',
                'voip_enabled', 'hardware_support', 'price_per_user'
            ]))
        ]);
        
        $finalVariables = array_merge($baseVariables, $categoryVariables, $contractVariables);
        
        Log::info('✅ Final variable set assembled', [
            'total_count' => count($finalVariables),
            'base_count' => count($baseVariables),
            'category_count' => count($categoryVariables),
            'metadata_count' => count($contractVariables),
            'final_wizard_vars' => array_intersect_key($finalVariables, array_flip([
                'billing_model', 'service_tier', 'payment_terms', 'response_time_hours',
                'voip_enabled', 'hardware_support', 'price_per_user', 'setup_fee'
            ]))
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
            'client_short_name' => $contract->client->short_name ?? $contract->client->name ?? '',
            'client_address' => $contract->client->address ?? '',
            
            // Service provider information (from contract's company)
            'service_provider_name' => $contract->company->name ?? '',
            'service_provider_short_name' => $contract->company->short_name ?? $contract->company->name ?? '',
            'service_provider_address' => $contract->company->address ?? '',
            
            // Legal and contract terms
            'governing_state' => $contract->governing_law ?? $contract->client->state ?? 'Texas',
            'initial_term' => $this->formatTerm($contract->term_months ?? 12),
            'renewal_term' => $this->formatTerm($contract->term_months ?? 12),
            'termination_notice_days' => $contract->custom_clauses['termination']['noticePeriod'] ?? '30 days',
            'arbitration_location' => $contract->client->city . ', ' . ($contract->client->state ?? 'Texas'),
            
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
            // Fallback to static references if no section mapping available
            $variables = array_merge($variables, [
                'definitions_section_ref' => 'Section 1 (Definitions)',
                'services_section_ref' => 'Section 2 (Scope of Support Services)',
                'sla_section_ref' => 'Section 3 (Service Level Agreements)',
                'obligations_section_ref' => 'Section 4 (Client Obligations and Responsibilities)',
                'financial_section_ref' => 'Section 5 (Fees and Payment Terms)',
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
        $infraSchedule = $schedules->where('schedule_type', 'A')->first();
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

        return $variables;
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

        $names = array_map(function($type) {
            return self::ASSET_TYPE_NAMES[$type] ?? ucwords(str_replace('_', ' ', $type));
        }, $assetTypes);

        if (count($names) === 1) {
            return $names[0];
        }

        if (count($names) === 2) {
            return implode(' and ', $names);
        }

        $last = array_pop($names);
        return implode(', ', $names) . ', and ' . $last;
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
            $variableName = $category . '_section_ref';
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
        if (!empty($variableValues)) {
            $variables = array_merge($variables, $variableValues);
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
        if (!empty($contract->variables) && is_array($contract->variables)) {
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
            'governing_law' => 'governing_law',
            'jurisdiction' => 'jurisdiction'
        ];
        
        $this->extractFields($contract, $variables, $fieldMappings);
    }
    
    /**
     * Extract variables from SLA terms
     */
    protected function extractFromSlaTerms(Contract $contract, array &$variables): void
    {
        if (empty($contract->sla_terms)) return;
        
        $slaTerms = $contract->sla_terms;
        
        if (!empty($slaTerms['response_time_hours'])) {
            $variables['response_time_hours'] = (string)$slaTerms['response_time_hours'];
        }
        if (!empty($slaTerms['resolution_time_hours'])) {
            $variables['resolution_time_hours'] = (string)$slaTerms['resolution_time_hours'];
        }
        if (!empty($slaTerms['uptime_percentage'])) {
            $variables['uptime_percentage'] = (string)$slaTerms['uptime_percentage'];
        }
        if (!empty($slaTerms['service_tier'])) {
            $variables['service_tier'] = $slaTerms['service_tier'];
        }
        if (!empty($slaTerms['business_hours'])) {
            $variables['business_hours'] = $slaTerms['business_hours'];
        }
    }
    
    /**
     * Extract variables from pricing structure
     */
    protected function extractFromPricingStructure(Contract $contract, array &$variables): void
    {
        if (empty($contract->pricing_structure)) return;
        
        $pricingStructure = $contract->pricing_structure;
        
        if (!empty($pricingStructure['recurring_monthly'])) {
            $variables['monthly_base_rate'] = '$' . number_format((float)$pricingStructure['recurring_monthly'], 2);
        }
        if (!empty($pricingStructure['setup_fee'])) {
            $variables['setup_fee'] = '$' . number_format((float)$pricingStructure['setup_fee'], 2);
        }
        if (!empty($pricingStructure['one_time'])) {
            $variables['hourly_rate'] = '$' . number_format((float)$pricingStructure['one_time'], 2);
        }
        if (!empty($pricingStructure['billing_model'])) {
            $variables['billing_model'] = $pricingStructure['billing_model'];
        }
        if (!empty($pricingStructure['billing_frequency'])) {
            $variables['billing_frequency'] = $pricingStructure['billing_frequency'];
        }
    }
    
    /**
     * Extract variables from VoIP specifications (telecom contracts)
     */
    protected function extractFromVoipSpecifications(Contract $contract, array &$variables): void
    {
        if (empty($contract->voip_specifications)) return;
        
        $voipSpecs = $contract->voip_specifications;
        
        if (!empty($voipSpecs['services'])) {
            $variables['voip_services'] = is_array($voipSpecs['services']) 
                ? implode(', ', $voipSpecs['services']) 
                : $voipSpecs['services'];
        }
        if (!empty($voipSpecs['equipment'])) {
            $variables['voip_equipment'] = is_array($voipSpecs['equipment']) 
                ? implode(', ', $voipSpecs['equipment']) 
                : $voipSpecs['equipment'];
        }
        if (!empty($voipSpecs['phone_numbers'])) {
            $variables['phone_numbers'] = (string)$voipSpecs['phone_numbers'];
        }
        if (!empty($voipSpecs['channel_count'])) {
            $variables['channel_count'] = (string)$voipSpecs['channel_count'];
        }
        if (!empty($voipSpecs['calling_plan'])) {
            $variables['calling_plan'] = $voipSpecs['calling_plan'];
        }
        if (!empty($voipSpecs['international_calling'])) {
            $variables['international_calling'] = $voipSpecs['international_calling'];
        }
    }
    
    /**
     * Extract variables from compliance requirements (compliance contracts)
     */
    protected function extractFromComplianceRequirements(Contract $contract, array &$variables): void
    {
        if (empty($contract->compliance_requirements)) return;
        
        $compliance = $contract->compliance_requirements;
        
        if (!empty($compliance['frameworks'])) {
            $variables['compliance_frameworks'] = is_array($compliance['frameworks']) 
                ? implode(', ', $compliance['frameworks']) 
                : $compliance['frameworks'];
        }
        if (!empty($compliance['scope'])) {
            $variables['compliance_scope'] = $compliance['scope'];
        }
        if (!empty($compliance['risk_level'])) {
            $variables['risk_level'] = $compliance['risk_level'];
        }
        if (!empty($compliance['audit_frequency'])) {
            $variables['audit_frequency'] = $compliance['audit_frequency'];
        }
        if (!empty($compliance['industry_sector'])) {
            $variables['industry_sector'] = $compliance['industry_sector'];
        }
    }
    
    /**
     * Extract variables from billing configuration in metadata
     */
    protected function extractFromBillingConfig(array $metadata, array &$variables): void
    {
        $billingConfig = $metadata['billing_config'] ?? [];
        if (empty($billingConfig)) return;
        
        if (!empty($billingConfig['model']) && empty($variables['billing_model'])) {
            $variables['billing_model'] = $billingConfig['model'];
        }
        if (!empty($billingConfig['base_rate']) && empty($variables['monthly_base_rate'])) {
            $variables['monthly_base_rate'] = '$' . number_format((float)$billingConfig['base_rate'], 2);
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
            if (!empty($value)) {
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
            if (isset($config['skip_if_exists']) && $config['skip_if_exists'] && !empty($variables[$config['key']])) {
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
            $value = match($config['cast']) {
                'string' => (string)$value,
                'float' => (float)$value,
                'int' => (int)$value,
                'bool' => (bool)$value,
                default => $value
            };
        }
        
        // Handle formatting
        if (isset($config['format'])) {
            $value = match($config['format']) {
                'currency' => '$' . number_format((float)$value, 2),
                'array_to_string' => is_array($value) ? implode(', ', $value) : $value,
                default => $value
            };
        }
        
        return $value;
    }
}