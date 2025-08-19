<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractSchedule;
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
    public function generateVariables(Contract $contract): array
    {
        $baseVariables = $this->generateBaseVariables($contract);
        $templateCategory = $this->getTemplateCategory($contract->template);
        
        // Generate category-specific variables
        $categoryVariables = match ($templateCategory) {
            'msp' => $this->generateMspVariables($contract),
            'voip' => $this->generateVoipVariables($contract),
            'var' => $this->generateVarVariables($contract),
            'compliance' => $this->generateComplianceVariables($contract),
            'general' => $this->generateGeneralVariables($contract),
            default => []
        };

        return array_merge($baseVariables, $categoryVariables);
    }

    /**
     * Get template category for a contract template
     */
    public function getTemplateCategory(?ContractTemplate $template): string
    {
        if (!$template) {
            return 'general';
        }

        return self::TEMPLATE_CATEGORIES[$template->template_type] ?? 'general';
    }

    /**
     * Generate base variables common to all contracts
     */
    protected function generateBaseVariables(Contract $contract): array
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
            'client_address' => $contract->client->address ?? '',
            
            // Service provider information (from company)
            'service_provider_name' => auth()->user()->company->name ?? '',
            'service_provider_short_name' => auth()->user()->company->short_name ?? '',
            'service_provider_address' => auth()->user()->company->address ?? '',
        ];

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
        
        // Infrastructure schedule variables
        $infraSchedule = $schedules->where('schedule_type', 'infrastructure')->first();
        if ($infraSchedule) {
            $infraData = $infraSchedule->schedule_data;
            
            // Supported asset types
            $supportedAssets = $infraData['supportedAssetTypes'] ?? [];
            $variables['supported_asset_types'] = $this->formatAssetTypesList($supportedAssets);
            $variables['supported_asset_count'] = count($supportedAssets);
            $variables['has_workstation_support'] = in_array('workstation', $supportedAssets);
            $variables['has_server_support'] = in_array('server', $supportedAssets);
            $variables['has_network_support'] = in_array('network_device', $supportedAssets);
            
            // SLA configuration
            $sla = $infraData['sla'] ?? [];
            $serviceTier = $sla['serviceTier'] ?? 'bronze';
            $tierConfig = self::SERVICE_TIERS[$serviceTier] ?? self::SERVICE_TIERS['bronze'];
            
            $variables['service_tier'] = $tierConfig['name'];
            $variables['response_time_hours'] = $tierConfig['response_time'];
            $variables['resolution_time_hours'] = $tierConfig['resolution_time'];
            $variables['uptime_percentage'] = $tierConfig['uptime'];
            $variables['business_hours'] = $tierConfig['coverage'];
            $variables['tier_benefits'] = implode(', ', $tierConfig['benefits']);
            
            // Coverage rules
            $coverage = $infraData['coverageRules'] ?? [];
            $variables['includes_remote_support'] = $coverage['includeRemoteSupport'] ?? true;
            $variables['includes_onsite_support'] = $coverage['includeOnsiteSupport'] ?? false;
            $variables['auto_assign_assets'] = $coverage['autoAssignNewAssets'] ?? false;
            
            // Exclusions
            $exclusions = $infraData['exclusions'] ?? [];
            $variables['excluded_asset_types'] = $exclusions['assetTypes'] ?? '';
            $variables['excluded_services'] = $exclusions['services'] ?? '';
        }

        // Pricing schedule variables
        $pricingSchedule = $schedules->where('schedule_type', 'pricing')->first();
        if ($pricingSchedule) {
            $pricingData = $pricingSchedule->schedule_data;
            
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
}