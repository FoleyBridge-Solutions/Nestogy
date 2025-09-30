<?php

namespace App\Domains\Core\Services;

use App\Domains\Contract\Models\Contract;

/**
 * TemplateContentGenerator Service
 *
 * Generates template-aware content that replaces static "Section A/B/C" references
 * with dynamic content based on wizard selections and template types.
 */
class TemplateContentGenerator
{
    protected $variableMapper;

    public function __construct(?TemplateVariableMapper $variableMapper = null)
    {
        $this->variableMapper = $variableMapper ?: new TemplateVariableMapper;
    }

    /**
     * Generate dynamic service description content replacing Section A/B/C references
     */
    public function generateServiceContent(Contract $contract, array $variables): string
    {
        $templateCategory = $this->variableMapper->getTemplateCategory($contract->template);

        return match ($templateCategory) {
            'msp' => $this->generateMspServiceContent($contract, $variables),
            'voip' => $this->generateVoipServiceContent($contract, $variables),
            'var' => $this->generateVarServiceContent($contract, $variables),
            'compliance' => $this->generateComplianceServiceContent($contract, $variables),
            'general' => $this->generateGeneralServiceContent($contract, $variables),
            default => $this->generateMspServiceContent($contract, $variables)
        };
    }

    /**
     * Generate MSP-specific service content
     */
    protected function generateMspServiceContent(Contract $contract, array $variables): string
    {
        $content = "SCOPE OF SUPPORT SERVICES:\n";
        $content .= "{{service_provider_short_name}} shall provide the Support Services to the Client for the Supported Infrastructure selected by the Client and detailed in Schedule A.\n\n";

        // Dynamic asset-based service descriptions
        $supportedAssets = $variables['supported_asset_types'] ?? '';
        if ($supportedAssets) {
            $content .= "**Supported Infrastructure Coverage:**\n";
            $content .= "Support services cover the following asset types: {$supportedAssets}.\n\n";

            // Generate specific service descriptions based on asset types
            $assetTypes = $this->extractAssetTypesFromString($supportedAssets);

            if (in_array('hypervisor_node', $assetTypes) || in_array('server', $assetTypes)) {
                $content .= "**Server & Hypervisor Support:** Support pertaining to the core server infrastructure and virtualization environment (\"Managed Server Infrastructure\"), encompassing physical servers, hypervisor hosts, management interfaces, cluster functionality, and directly connected storage infrastructure. Support includes monitoring, maintenance, performance optimization, and troubleshooting of server hardware and virtualization platforms as specified in Schedule A.\n\n";
            }

            if (in_array('workstation', $assetTypes)) {
                $content .= "**Workstation Support:** Support pertaining to Client-owned physical and virtual workstations (\"Managed Workstations\") as specified in Schedule A, including their operating systems and standard installed business applications. Services include hardware troubleshooting coordination, software support, and direct end-user assistance for issues related to workstation functionality, connectivity, and productivity applications.\n\n";
            }

            if (in_array('network_device', $assetTypes)) {
                $content .= "**Network Infrastructure Support:** Support for network devices including routers, switches, firewalls, and wireless access points (\"Managed Network Infrastructure\"). Services encompass configuration management, performance monitoring, security policy implementation, and troubleshooting of network connectivity and performance issues.\n\n";
            }

            if (in_array('mobile_device', $assetTypes)) {
                $content .= "**Mobile Device Support:** Support for mobile endpoints including smartphones, tablets, and mobile workstations (\"Managed Mobile Devices\"). Services include device management, security policy enforcement, application support, and connectivity troubleshooting.\n\n";
            }

            if (in_array('storage', $assetTypes)) {
                $content .= "**Storage Systems Support:** Support for storage infrastructure including NAS, SAN, and backup appliances (\"Managed Storage Systems\"). Services include capacity monitoring, performance optimization, backup verification, and data recovery assistance.\n\n";
            }

            if (in_array('security_device', $assetTypes)) {
                $content .= "**Security Infrastructure Support:** Support for security devices and systems including firewalls, intrusion detection systems, and access control systems (\"Managed Security Infrastructure\"). Services include policy management, threat monitoring, and security incident response coordination.\n\n";
            }

            if (in_array('printer', $assetTypes)) {
                $content .= "**Printer & Peripheral Support:** Support for network printers, scanners, and multifunction devices (\"Managed Peripherals\"). Services include driver management, print queue troubleshooting, and coordination of hardware maintenance.\n\n";
            }
        } else {
            // Fallback to generic service description
            $content .= "The Client may elect from the following service coverage areas based on their infrastructure requirements and service tier selection.\n\n";
        }

        // Service Level Agreement integration
        if (! empty($variables['service_tier'])) {
            $content .= "**Service Level Agreement:**\n";
            $content .= "The selected {$variables['service_tier']} service tier provides:\n";
            $content .= "- Response Time: {$variables['response_time_hours']} hours\n";
            $content .= "- Resolution Target: {$variables['resolution_time_hours']} hours\n";
            $content .= "- Uptime Guarantee: {$variables['uptime_percentage']}%\n";
            $content .= "- Support Coverage: {$variables['business_hours']}\n\n";
        }

        // Coverage rules and conditions
        if (! empty($variables['includes_remote_support'])) {
            $content .= 'Support services include remote monitoring, diagnostics, and resolution capabilities. ';
        }
        if (! empty($variables['includes_onsite_support'])) {
            $content .= 'On-site support is included for issues requiring physical presence. ';
        }
        $content .= "\n\n";

        // Exclusions
        if (! empty($variables['excluded_asset_types']) || ! empty($variables['excluded_services'])) {
            $content .= "**Service Exclusions:**\n";
            if (! empty($variables['excluded_asset_types'])) {
                $content .= "Excluded asset types: {$variables['excluded_asset_types']}\n";
            }
            if (! empty($variables['excluded_services'])) {
                $content .= "Excluded services: {$variables['excluded_services']}\n";
            }
            $content .= "\n";
        }

        return $content;
    }

    /**
     * Generate VoIP-specific service content
     */
    protected function generateVoipServiceContent(Contract $contract, array $variables): string
    {
        $content = "TELECOMMUNICATIONS SERVICES:\n";
        $content .= "Provider shall provide Customer with the following telecommunications services:\n\n";

        // Channel configuration
        if (! empty($variables['channel_count'])) {
            $content .= "**Service Configuration:**\n";
            $content .= "- {$variables['channel_count']} concurrent voice channels\n";
            $content .= '- Calling Plan: '.($variables['calling_plan'] ?? 'Local and Long Distance')."\n";
            $content .= '- Protocol: '.strtoupper($variables['protocol'] ?? 'SIP')."\n";
            $content .= '- Emergency Services: '.($variables['emergency_services'] ?? 'Enhanced 911')."\n\n";
        }

        // Quality of Service
        if (! empty($variables['mos_score'])) {
            $content .= "**Quality of Service Metrics:**\n";
            $content .= "- Mean Opinion Score (MOS): {$variables['mos_score']}\n";
            $content .= "- Maximum Jitter: {$variables['jitter_ms']}ms\n";
            $content .= "- Packet Loss: <{$variables['packet_loss_percent']}%\n";
            $content .= "- Uptime Guarantee: {$variables['telecom_uptime_percent']}%\n";
            $content .= "- Maximum Latency: {$variables['latency_ms']}ms\n\n";
        }

        // Support and response times
        if (! empty($variables['telecom_response_time'])) {
            $content .= "**Service Level Agreement:**\n";
            $content .= "- Response Time: {$variables['telecom_response_time']} hour(s)\n";
            $content .= "- Resolution Target: {$variables['telecom_resolution_time']} hour(s)\n";
            $content .= "- Support Coverage: {$variables['support_coverage']}\n\n";
        }

        // Compliance and security
        $content .= "**Regulatory Compliance:**\n";
        if (! empty($variables['fcc_compliant'])) {
            $content .= "- FCC Regulations: Compliant\n";
        }
        if (! empty($variables['karis_law'])) {
            $content .= "- Kari's Law: Compliant\n";
        }
        if (! empty($variables['ray_baums'])) {
            $content .= "- RAY BAUM's Act: Compliant\n";
        }
        $content .= "\n";

        if (! empty($variables['encryption_enabled'])) {
            $content .= "**Security Features:**\n";
            $content .= "- Voice encryption enabled\n";
            if (! empty($variables['fraud_protection'])) {
                $content .= "- Fraud protection and monitoring\n";
            }
            $content .= "\n";
        }

        return $content;
    }

    /**
     * Generate VAR-specific service content
     */
    protected function generateVarServiceContent(Contract $contract, array $variables): string
    {
        $content = "HARDWARE PROCUREMENT AND SERVICES:\n";
        $content .= "Service Provider shall provide the following hardware procurement and related services:\n\n";

        // Product categories
        if (! empty($variables['hardware_categories'])) {
            $content .= "**Product Categories:**\n";
            $content .= "Authorized to procure and provide: {$variables['hardware_categories']}\n\n";
        }

        // Procurement model
        if (! empty($variables['procurement_model'])) {
            $procurementDescription = match ($variables['procurement_model']) {
                'direct_resale' => 'Direct resale model with manufacturer partnerships',
                'distribution' => 'Distribution partnership model',
                'drop_ship' => 'Drop-ship fulfillment model',
                default => 'Standard procurement model'
            };
            $content .= "**Procurement Model:** {$procurementDescription}\n";
            $content .= "**Lead Time:** {$variables['lead_time_days']} {$variables['lead_time_type']}\n\n";
        }

        // Installation services
        $installationServices = [];
        if (! empty($variables['includes_installation'])) {
            $installationServices[] = 'Basic installation';
        }
        if (! empty($variables['includes_rack_stack'])) {
            $installationServices[] = 'Rack and stack services';
        }
        if (! empty($variables['includes_cabling'])) {
            $installationServices[] = 'Cabling and connectivity';
        }
        if (! empty($variables['includes_configuration'])) {
            $installationServices[] = 'System configuration';
        }
        if (! empty($variables['includes_project_management'])) {
            $installationServices[] = 'Project management';
        }

        if (! empty($installationServices)) {
            $content .= "**Installation Services:**\n";
            foreach ($installationServices as $service) {
                $content .= "- {$service}\n";
            }
            $content .= "\n";
        }

        // Service Level Agreements
        if (! empty($variables['installation_timeline'])) {
            $content .= "**Service Level Agreements:**\n";
            $content .= "- Installation Timeline: {$variables['installation_timeline']}\n";
            $content .= "- Configuration Timeline: {$variables['configuration_timeline']}\n";
            $content .= '- Support Response: '.str_replace('_', ' ', $variables['hardware_support_response'])."\n\n";
        }

        // Warranty terms
        if (! empty($variables['hardware_warranty_period'])) {
            $content .= "**Warranty Coverage:**\n";
            $content .= '- Hardware Warranty: '.str_replace('_', ' ', $variables['hardware_warranty_period'])."\n";
            $content .= '- Support Period: '.str_replace('_', ' ', $variables['support_warranty_period'])."\n";
            if (! empty($variables['onsite_warranty_support'])) {
                $content .= "- On-site warranty support included\n";
            }
            if (! empty($variables['advanced_replacement'])) {
                $content .= "- Advanced replacement service included\n";
            }
            $content .= "\n";
        }

        // Pricing model
        if (! empty($variables['markup_model'])) {
            $pricingDescription = match ($variables['markup_model']) {
                'fixed_percentage' => 'Fixed percentage markup on manufacturer cost',
                'tiered_discount' => 'Volume-based tiered discount structure',
                'cost_plus' => 'Cost-plus pricing model',
                default => 'Standard pricing model'
            };
            $content .= "**Pricing Model:** {$pricingDescription}\n\n";
        }

        return $content;
    }

    /**
     * Generate Compliance-specific service content
     */
    protected function generateComplianceServiceContent(Contract $contract, array $variables): string
    {
        $content = "COMPLIANCE SERVICES:\n";
        $content .= "Service Provider shall provide comprehensive compliance services for the following regulatory frameworks:\n\n";

        // Compliance frameworks
        if (! empty($variables['compliance_frameworks'])) {
            $content .= "**Regulatory Frameworks:**\n";
            $content .= "{$variables['compliance_frameworks']}\n\n";
        }

        // Risk assessment
        if (! empty($variables['risk_level'])) {
            $content .= "**Risk Assessment:**\n";
            $content .= '- Risk Level: '.ucfirst($variables['risk_level'])."\n";
            if (! empty($variables['industry_sector'])) {
                $content .= "- Industry Sector: {$variables['industry_sector']}\n";
            }
            $content .= "\n";
        }

        // Audit services
        $auditServices = [];
        if (! empty($variables['includes_internal_audits'])) {
            $auditServices[] = 'Internal compliance audits';
        }
        if (! empty($variables['includes_external_audits'])) {
            $auditServices[] = 'External audit coordination';
        }
        if (! empty($variables['includes_penetration_testing'])) {
            $auditServices[] = 'Penetration testing';
        }
        if (! empty($variables['includes_vulnerability_scanning'])) {
            $auditServices[] = 'Vulnerability scanning';
        }

        if (! empty($auditServices)) {
            $content .= "**Audit Services:**\n";
            foreach ($auditServices as $service) {
                $content .= "- {$service}\n";
            }
            $content .= "\n";
        }

        // Audit frequency
        if (! empty($variables['comprehensive_audit_frequency'])) {
            $content .= "**Audit Schedule:**\n";
            $content .= '- Comprehensive Audits: '.ucfirst($variables['comprehensive_audit_frequency'])."\n";
            $content .= '- Interim Reviews: '.ucfirst($variables['interim_audit_frequency'])."\n";
            $content .= '- Vulnerability Assessments: '.ucfirst($variables['vulnerability_scan_frequency'])."\n\n";
        }

        // Training programs
        if (! empty($variables['training_programs'])) {
            $content .= "**Training Programs:**\n";
            $content .= "Selected Programs: {$variables['training_programs']}\n";
            $content .= 'Delivery Method: '.ucfirst(str_replace('_', ' ', $variables['training_delivery_method']))."\n";
            $content .= 'Training Frequency: '.ucfirst($variables['training_frequency'])."\n\n";
        }

        // Response times
        if (! empty($variables['critical_response_time'])) {
            $content .= "**Incident Response:**\n";
            $content .= '- Critical Issues: '.str_replace('_', ' ', $variables['critical_response_time'])."\n";
            $content .= '- High Priority: '.str_replace('_', ' ', $variables['high_response_time'])."\n";
            $content .= '- Standard Issues: '.str_replace('_', ' ', $variables['standard_response_time'])."\n\n";
        }

        return $content;
    }

    /**
     * Generate General template service content
     */
    protected function generateGeneralServiceContent(Contract $contract, array $variables): string
    {
        $content = "SERVICES:\n";
        $content .= "Service Provider shall provide the services as specified in this agreement and its associated schedules.\n\n";

        // Add any specific service descriptions based on template type
        if ($contract->template) {
            $templateType = $contract->template->template_type;

            $content .= match ($templateType) {
                'consumption_based' => $this->generateConsumptionBasedContent($variables),
                'international_service' => $this->generateInternationalServiceContent($variables),
                'equipment_lease' => $this->generateEquipmentLeaseContent($variables),
                default => "Service details are defined in the associated schedules and attachments.\n\n"
            };
        }

        return $content;
    }

    /**
     * Generate consumption-based service content
     */
    protected function generateConsumptionBasedContent(array $variables): string
    {
        $content = "**Consumption-Based Service Model:**\n";
        $content .= "Services are provided on a pay-as-you-use basis with flexible resource allocation and real-time usage monitoring.\n\n";

        if (! empty($variables['billing_model'])) {
            $content .= 'Billing Model: '.ucfirst(str_replace('_', ' ', $variables['billing_model']))."\n";
        }

        return $content."\n";
    }

    /**
     * Generate international service content
     */
    protected function generateInternationalServiceContent(array $variables): string
    {
        return "**International Services:**\nSpecialized services for international operations including multi-region support and compliance with international regulations.\n\n";
    }

    /**
     * Generate equipment lease content
     */
    protected function generateEquipmentLeaseContent(array $variables): string
    {
        return "**Equipment Leasing:**\nLeasing services for technology equipment with flexible terms and maintenance options.\n\n";
    }

    /**
     * Extract asset types from formatted string
     */
    protected function extractAssetTypesFromString(string $assetTypesString): array
    {
        // Map display names back to internal asset type values
        $reverseMap = array_flip(TemplateVariableMapper::ASSET_TYPE_NAMES);
        $assetTypes = [];

        // Split by common delimiters and clean up
        $parts = preg_split('/[,\s]+(?:and\s+)?/', $assetTypesString);

        foreach ($parts as $part) {
            $part = trim($part);
            if (isset($reverseMap[$part])) {
                $assetTypes[] = $reverseMap[$part];
            }
        }

        return $assetTypes;
    }

    /**
     * Generate dynamic exclusions content based on non-selected asset types
     */
    public function generateExclusionsContent(Contract $contract, array $variables): string
    {
        $templateCategory = $this->variableMapper->getTemplateCategory($contract->template);

        if ($templateCategory === 'msp' && ! empty($variables['supported_asset_types'])) {
            $allAssetTypes = array_keys(TemplateVariableMapper::ASSET_TYPE_NAMES);
            $selectedAssetTypes = $this->extractAssetTypesFromString($variables['supported_asset_types']);
            $excludedTypes = array_diff($allAssetTypes, $selectedAssetTypes);

            if (! empty($excludedTypes)) {
                $excludedNames = array_map(function ($type) {
                    return TemplateVariableMapper::ASSET_TYPE_NAMES[$type];
                }, $excludedTypes);

                return 'Support services specifically exclude: '.implode(', ', $excludedNames).' unless explicitly added to Schedule A.';
            }
        }

        return 'Service exclusions are detailed in Schedule A and the general terms and conditions.';
    }
}
