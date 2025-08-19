<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractClause;
use App\Models\ContractTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Modern Contract Clause Seeder
 * 
 * Seeds template-aware contract clauses that replace static "Section A/B/C" 
 * references with dynamic content based on wizard selections.
 */
class ModernContractClauseSeeder extends Seeder
{
    public function run()
    {
        // Create MSP-specific clauses (for 11 MSP template types)
        $this->createMspClauses();
        
        // Create VoIP-specific clauses (for 8 VoIP template types)
        $this->createVoipClauses();
        
        // Create VAR-specific clauses (for 6 VAR template types)  
        $this->createVarClauses();
        
        // Create Compliance-specific clauses (for 4 Compliance template types)
        $this->createComplianceClauses();
        
        // Create General template clauses (for 4 General template types)
        $this->createGeneralClauses();
    }

    /**
     * Create MSP-specific clauses for 11 template types
     */
    protected function createMspClauses()
    {
        // MSP Services Clause - replaces static Section A/B/C
        ContractClause::create([
            'company_id' => 1,
            'name' => 'MSP Infrastructure Support Services',
            'slug' => 'msp-infrastructure-services',
            'category' => ContractClause::CATEGORY_SERVICES,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getMspServicesContent(),
            'variables' => [
                'supported_asset_types',
                'service_tier', 
                'response_time_hours',
                'resolution_time_hours',
                'uptime_percentage',
                'business_hours',
                'includes_remote_support',
                'includes_onsite_support',
                'excluded_asset_types',
                'excluded_services'
            ],
            'applicable_contract_types' => [
                'managed_services',
                'cybersecurity_services', 
                'backup_dr',
                'cloud_migration',
                'm365_management',
                'break_fix',
                'enterprise_managed',
                'mdr_services',
                'support_contract',
                'maintenance_agreement',
                'sla_contract'
            ],
            'description' => 'Dynamic MSP services clause based on selected asset types and service tier',
            'sort_order' => 200,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'msp',
                'replaces_legacy' => ['section_a', 'section_b', 'section_c'],
                'dependencies' => ['msp-sla-terms', 'msp-definitions'],
                'dynamic_content' => true
            ]
        ]);

        // MSP SLA Clause - tier-specific metrics
        ContractClause::create([
            'company_id' => 1,
            'name' => 'MSP Service Level Agreement',
            'slug' => 'msp-sla-terms',
            'category' => ContractClause::CATEGORY_SLA,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getMspSlaContent(),
            'variables' => [
                'service_tier',
                'response_time_hours',
                'resolution_time_hours', 
                'uptime_percentage',
                'business_hours',
                'tier_benefits'
            ],
            'applicable_contract_types' => [
                'managed_services',
                'cybersecurity_services',
                'backup_dr',
                'cloud_migration',
                'm365_management',
                'enterprise_managed',
                'mdr_services',
                'support_contract',
                'maintenance_agreement',
                'sla_contract'
            ],
            'description' => 'Service tier-specific SLA terms with dynamic metrics',
            'sort_order' => 300,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'msp',
                'tier_specific' => true,
                'dynamic_metrics' => true
            ]
        ]);

        // MSP Asset Coverage Definitions
        ContractClause::create([
            'company_id' => 1,
            'name' => 'MSP Asset Type Definitions',
            'slug' => 'msp-definitions',
            'category' => ContractClause::CATEGORY_DEFINITIONS,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getMspDefinitionsContent(),
            'variables' => [
                'supported_asset_types',
                'service_tier'
            ],
            'applicable_contract_types' => [
                'managed_services',
                'cybersecurity_services',
                'backup_dr',
                'cloud_migration',
                'm365_management',
                'enterprise_managed',
                'mdr_services',
                'support_contract',
                'maintenance_agreement'
            ],
            'description' => 'Definitions for supported asset types and infrastructure terms',
            'sort_order' => 100,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'msp',
                'provides_definitions' => ['supported_infrastructure', 'managed_assets', 'service_tier'],
                'asset_type_aware' => true
            ]
        ]);

        // MSP Exclusions Clause - dynamic based on non-selected assets
        ContractClause::create([
            'company_id' => 1,
            'name' => 'MSP Service Exclusions',
            'slug' => 'msp-exclusions',
            'category' => ContractClause::CATEGORY_EXCLUSIONS,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getMspExclusionsContent(),
            'variables' => [
                'supported_asset_types',
                'excluded_asset_types',
                'excluded_services'
            ],
            'applicable_contract_types' => [
                'managed_services',
                'cybersecurity_services',
                'backup_dr',
                'cloud_migration',
                'm365_management',
                'break_fix',
                'enterprise_managed',
                'mdr_services'
            ],
            'description' => 'Dynamic exclusions based on non-selected asset types',
            'sort_order' => 600,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => false,
            'metadata' => [
                'template_category' => 'msp',
                'generates_exclusions' => true,
                'asset_aware' => true
            ]
        ]);

        // MSP Financial Terms - billing model specific
        ContractClause::create([
            'company_id' => 1,
            'name' => 'MSP Billing Terms',
            'slug' => 'msp-financial-terms',
            'category' => ContractClause::CATEGORY_FINANCIAL,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getMspFinancialContent(),
            'variables' => [
                'billing_model',
                'monthly_base_rate',
                'setup_fee',
                'hourly_rate',
                'billing_frequency',
                'payment_terms'
            ],
            'applicable_contract_types' => [
                'managed_services',
                'cybersecurity_services',
                'backup_dr',
                'cloud_migration',
                'm365_management',
                'break_fix',
                'enterprise_managed',
                'mdr_services'
            ],
            'description' => 'Billing model-specific financial terms',
            'sort_order' => 400,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'msp',
                'billing_model_aware' => true
            ]
        ]);
    }

    /**
     * Get MSP services content template
     */
    protected function getMspServicesContent(): string
    {
        return "SCOPE OF SUPPORT SERVICES

{{service_provider_short_name}} shall provide the Support Services to the Client for the Supported Infrastructure selected by the Client and detailed in Schedule A.

{{#if supported_asset_types}}
**Supported Infrastructure Coverage:**
Support services cover the following asset types: {{supported_asset_types}}.

**Service Descriptions:**
{{#if has_server_support}}
**Server & Infrastructure Support:** Support pertaining to server infrastructure and virtualization environments (\"Managed Server Infrastructure\"), encompassing physical servers, hypervisor hosts, management interfaces, cluster functionality, and directly connected storage infrastructure. Support includes monitoring, maintenance, performance optimization, and troubleshooting of server hardware and virtualization platforms as specified in Schedule A.
{{/if}}

{{#if has_workstation_support}}
**Workstation Support:** Support pertaining to Client-owned physical and virtual workstations (\"Managed Workstations\") as specified in Schedule A, including their operating systems and standard installed business applications. Services include hardware troubleshooting coordination, software support, and direct end-user assistance for issues related to workstation functionality, connectivity, and productivity applications.
{{/if}}

{{#if has_network_support}}
**Network Infrastructure Support:** Support for network devices including routers, switches, firewalls, and wireless access points (\"Managed Network Infrastructure\"). Services encompass configuration management, performance monitoring, security policy implementation, and troubleshooting of network connectivity and performance issues.
{{/if}}
{{/if}}

{{#if service_tier}}
**Service Level Agreement:**
The selected {{service_tier}} service tier provides:
- Response Time: {{response_time_hours}} hours
- Resolution Target: {{resolution_time_hours}} hours  
- Uptime Guarantee: {{uptime_percentage}}%
- Support Coverage: {{business_hours}}
{{/if}}

{{#if includes_remote_support}}
Support services include remote monitoring, diagnostics, and resolution capabilities.{{/if}}{{#if includes_onsite_support}} On-site support is included for issues requiring physical presence.{{/if}}

The specific Service Levels, Response Times, and Resolution Times applicable to the chosen Support Services and selected Service Tier shall be delineated in Schedule A.";
    }

    /**
     * Get MSP SLA content template
     */
    protected function getMspSlaContent(): string
    {
        return "SERVICE LEVEL AGREEMENT

{{service_provider_short_name}} commits to the following service levels for the {{service_tier}} service tier:

**Performance Metrics:**
- **Response Time:** {{response_time_hours}} hours maximum for initial response to properly submitted Support Requests
- **Resolution Target:** {{resolution_time_hours}} hours target for issue resolution, varying based on complexity and severity
- **Uptime Guarantee:** {{uptime_percentage}}% availability for managed infrastructure components
- **Support Coverage:** {{business_hours}} support availability

**Service Tier Benefits:**
{{tier_benefits}}

**Performance Measurement:**
Service levels are measured monthly and reported quarterly. Uptime is calculated based on managed infrastructure availability excluding planned maintenance windows. Response times are measured from receipt of properly submitted Support Request to initial acknowledgment.

**Service Credits:**
In the event of failure to meet the uptime guarantee, Client may be eligible for service credits as detailed in Schedule B.

**Exclusions from SLA:**
Service levels exclude issues caused by Client actions, external network connectivity beyond {{service_provider_short_name}}'s control, force majeure events, or scheduled maintenance performed during agreed maintenance windows.";
    }

    /**
     * Get MSP definitions content template
     */
    protected function getMspDefinitionsContent(): string
    {
        return "DEFINITIONS

As used in this Agreement, the following terms shall have the meanings ascribed to them below:

**Supported Infrastructure:** Shall mean the specific information technology hardware, software, and systems components designated for coverage under this Agreement, encompassing {{supported_asset_types}} as enumerated with specificity in Schedule A.

**{{service_tier}} Service Tier:** The selected service level providing {{response_time_hours}}-hour response time, {{resolution_time_hours}}-hour resolution target, and {{uptime_percentage}}% uptime guarantee with {{business_hours}} support coverage.

**Support Services:** Shall mean the recurring information technology support services to be furnished by {{service_provider_short_name}} to the Client, as delineated in this Agreement and further specified in Schedule A, corresponding to the Supported Infrastructure and Service Tier selected by the Client.

**Response Time:** Shall mean the target timeframe within which {{service_provider_short_name}} shall acknowledge receipt of a properly submitted Support Request.

**Resolution Time:** Shall mean the target timeframe within which {{service_provider_short_name}} shall endeavor to resolve a properly submitted Support Request. Resolution Times are targets and not guaranteed fix times.

**Business Hours:** Shall mean {{business_hours}}, excluding {{service_provider_short_name}} recognized holidays as specified in Schedule A.

**Support Request:** Shall mean a request for technical assistance pertaining to the Supported Infrastructure, submitted by an authorized Client representative to {{service_provider_short_name}} in compliance with the procedures stipulated in Schedule A.";
    }

    /**
     * Get MSP exclusions content template
     */
    protected function getMspExclusionsContent(): string
    {
        return "EXCLUSIONS FROM SUPPORT SERVICES

{{service_provider_short_name}}'s obligations to provide Support Services hereunder expressly exclude, unless otherwise explicitly agreed in writing or detailed in Schedule A:

**Asset Type Exclusions:**
{{#if excluded_asset_types}}
Support services specifically exclude: {{excluded_asset_types}} unless explicitly added to Schedule A.
{{/if}}

**General Exclusions:**
- Issues proximately caused by Client's or its users' negligence, misuse, abuse, failure to follow documented procedures, or unauthorized modifications
- Support for any third-party hardware or software not expressly identified as Supported Infrastructure in Schedule A
- On-site support, except as may be specifically included in the selected Service Tier or procured separately
- Major version upgrades, system migrations, or substantial architectural modifications
- Installation of new hardware or software unless part of an agreed scope
- Issues pertaining to external network connectivity (e.g., Internet Service Provider) beyond {{service_provider_short_name}}'s defined management scope
- Custom software development, coding, scripting, or debugging
- Formal end-user training programs
- Provision of consumable supplies
- Maintenance of the physical environment for Client-premised equipment

{{#if excluded_services}}
**Additional Service Exclusions:**
{{excluded_services}}
{{/if}}

**Third-Party Vendors:** {{service_provider_short_name}} shall not be responsible for resolving issues requiring direct intervention by third-party vendors, unless vendor liaison is an included service per Schedule A.

**End-of-Life/End-of-Support Systems:** Support for EOL/EOS components listed in Schedule A will be on a commercially reasonable efforts basis only, without warranty of resolution.";
    }

    /**
     * Get MSP financial content template
     */
    protected function getMspFinancialContent(): string
    {
        return "FEES AND PAYMENT TERMS

In consideration for the Support Services, Client shall pay {{service_provider_short_name}} the recurring fees calculated based on the selected Service Tier and billing model as set forth in Schedule B.

**Billing Model:** {{billing_model}}
{{#if monthly_base_rate}}
**Monthly Base Rate:** {{monthly_base_rate}}{{/if}}
{{#if setup_fee}}
**Setup Fee:** {{setup_fee}} (one-time){{/if}}
{{#if hourly_rate}}
**Hourly Rate:** {{hourly_rate}} (for services outside included scope){{/if}}

**Payment Terms:**
- **Billing Frequency:** {{billing_frequency}}
- **Payment Terms:** {{payment_terms}}
- **Currency:** {{currency_code}}

**Payment Methods:** All payments shall be made in United States Dollars via ACH or Wire Transfer. Credit Card payments may be arranged upon request and will be subjected to a processing fee not greater than what is charged to {{service_provider_short_name}} by the payment processor.

**Late Payment:** Should payment not be received by the due date, {{service_provider_short_name}} shall be entitled to immediately suspend performance of the Support Services without further notice until payment is received in full. Any amount not paid when due shall accrue interest at a rate of 1.5% per month, or the maximum rate permitted by applicable law, whichever is lower.

**Fee Adjustments:** {{service_provider_short_name}} reserves the right to review and adjust the fees set forth in Schedule B effective upon the annual anniversary of the Effective Date. Fees may also be adjusted upon mutual written agreement following material changes to the Supported Infrastructure.";
    }

    /**
     * Create VoIP-specific clauses for 8 template types
     */
    protected function createVoipClauses()
    {
        // VoIP Services Clause
        ContractClause::create([
            'company_id' => 1,
            'name' => 'VoIP Telecommunications Services',
            'slug' => 'voip-services',
            'category' => ContractClause::CATEGORY_SERVICES,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getVoipServicesContent(),
            'variables' => [
                'channel_count',
                'calling_plan',
                'protocol',
                'emergency_services',
                'mos_score',
                'jitter_ms',
                'packet_loss_percent',
                'telecom_uptime_percent',
                'latency_ms'
            ],
            'applicable_contract_types' => [
                'hosted_pbx',
                'sip_trunking', 
                'unified_communications',
                'international_calling',
                'contact_center',
                'e911_services',
                'number_porting',
                'service_agreement'
            ],
            'description' => 'VoIP telecommunications services with QoS specifications',
            'sort_order' => 200,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'voip',
                'qos_aware' => true,
                'regulatory_compliant' => true
            ]
        ]);

        // VoIP Quality of Service
        ContractClause::create([
            'company_id' => 1,
            'name' => 'VoIP Quality of Service Standards',
            'slug' => 'voip-qos',
            'category' => ContractClause::CATEGORY_SLA,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getVoipQosContent(),
            'variables' => [
                'mos_score',
                'jitter_ms',
                'packet_loss_percent',
                'telecom_uptime_percent',
                'latency_ms',
                'telecom_response_time',
                'telecom_resolution_time'
            ],
            'applicable_contract_types' => [
                'hosted_pbx',
                'sip_trunking',
                'unified_communications',
                'contact_center'
            ],
            'description' => 'Quality of Service metrics and performance standards',
            'sort_order' => 300,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'voip',
                'qos_metrics' => true
            ]
        ]);

        // VoIP Regulatory Compliance
        ContractClause::create([
            'company_id' => 1,
            'name' => 'VoIP Regulatory Compliance',
            'slug' => 'voip-compliance',
            'category' => ContractClause::CATEGORY_COMPLIANCE,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getVoipComplianceContent(),
            'variables' => [
                'fcc_compliant',
                'karis_law',
                'ray_baums',
                'encryption_enabled',
                'fraud_protection'
            ],
            'applicable_contract_types' => [
                'hosted_pbx',
                'sip_trunking',
                'unified_communications',
                'e911_services'
            ],
            'description' => 'Telecommunications regulatory compliance requirements',
            'sort_order' => 500,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'voip',
                'regulatory_frameworks' => ['fcc', 'karis_law', 'ray_baums']
            ]
        ]);
    }

    protected function getVoipServicesContent(): string
    {
        return "TELECOMMUNICATIONS SERVICES

Provider shall provide Customer with the following telecommunications services:

**Service Configuration:**
{{#if channel_count}}
- {{channel_count}} concurrent voice channels
{{/if}}
{{#if calling_plan}}
- Calling Plan: {{calling_plan}}
{{/if}}
{{#if protocol}}
- Protocol: {{protocol|upper}}
{{/if}}
{{#if emergency_services}}
- Emergency Services: {{emergency_services}}
{{/if}}

**Core Features:**
- Voice calling and call management
- Call forwarding, transfer, and hold functionality
- Voicemail services with email notification
- Caller ID and call waiting services
- Conference calling capabilities

**Quality of Service:**
{{#if mos_score}}
- Mean Opinion Score (MOS): {{mos_score}}
{{/if}}
{{#if jitter_ms}}
- Maximum Jitter: {{jitter_ms}}ms
{{/if}}
{{#if packet_loss_percent}}
- Packet Loss: <{{packet_loss_percent}}%
{{/if}}
{{#if telecom_uptime_percent}}
- Service Uptime: {{telecom_uptime_percent}}%
{{/if}}

Provider shall maintain all necessary telecommunications licenses and authorizations. Customer remains responsible for compliance with applicable regulations governing its use of telecommunications services.";
    }

    protected function getVoipQosContent(): string
    {
        return "QUALITY OF SERVICE STANDARDS

Provider commits to the following Quality of Service metrics for telecommunications services:

**Performance Standards:**
{{#if mos_score}}
- **Mean Opinion Score (MOS):** {{mos_score}} minimum
{{/if}}
{{#if jitter_ms}}
- **Jitter:** {{jitter_ms}}ms maximum
{{/if}}
{{#if packet_loss_percent}}
- **Packet Loss:** {{packet_loss_percent}}% maximum
{{/if}}
{{#if latency_ms}}
- **Latency:** {{latency_ms}}ms maximum end-to-end
{{/if}}
{{#if telecom_uptime_percent}}
- **Service Availability:** {{telecom_uptime_percent}}% uptime guarantee
{{/if}}

**Response and Resolution:**
{{#if telecom_response_time}}
- **Support Response Time:** {{telecom_response_time}} hour(s)
{{/if}}
{{#if telecom_resolution_time}}
- **Issue Resolution Target:** {{telecom_resolution_time}} hour(s)
{{/if}}

**Measurement and Reporting:**
Quality metrics are monitored continuously and reported monthly. Service credits may apply for failure to meet guaranteed performance levels as specified in Schedule B.

**Network Management:**
Provider employs traffic management and quality assurance measures to maintain service levels. Customer acknowledges that performance may be affected by internet connectivity quality and network conditions beyond Provider's control.";
    }

    protected function getVoipComplianceContent(): string
    {
        return "REGULATORY COMPLIANCE

Provider and Customer acknowledge the following regulatory compliance requirements:

**Federal Communications Commission (FCC):**
{{#if fcc_compliant}}
- Full compliance with FCC telecommunications regulations
- Maintenance of required FCC authorizations and licenses
{{/if}}

**Enhanced 911 (E911) Compliance:**
{{#if karis_law}}
- **Kari's Law:** Direct 911 dialing capability without prefix requirements
{{/if}}
{{#if ray_baums}}
- **RAY BAUM's Act:** Dispatchable location information provided with 911 calls
{{/if}}

**Security and Privacy:**
{{#if encryption_enabled}}
- Voice traffic encryption for secure communications
{{/if}}
{{#if fraud_protection}}
- Fraud monitoring and prevention measures
- Suspicious activity detection and blocking
{{/if}}

**Customer Responsibilities:**
- Maintain accurate emergency location information
- Ensure user awareness of 911 service capabilities and limitations
- Comply with applicable telecommunications regulations
- Report suspected fraud or security incidents promptly

**Service Limitations:**
Customer acknowledges that VoIP emergency services may have limitations compared to traditional landline services, including potential service interruptions during power outages or internet connectivity issues.";
    }

    /**
     * Create VAR-specific clauses for 6 template types
     */
    protected function createVarClauses()
    {
        // VAR Hardware Services
        ContractClause::create([
            'company_id' => 1,
            'name' => 'VAR Hardware Procurement Services',
            'slug' => 'var-hardware-services',
            'category' => ContractClause::CATEGORY_SERVICES,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getVarServicesContent(),
            'variables' => [
                'hardware_categories',
                'procurement_model',
                'lead_time_days',
                'lead_time_type',
                'includes_installation',
                'includes_configuration',
                'includes_project_management'
            ],
            'applicable_contract_types' => [
                'hardware_procurement',
                'software_licensing',
                'vendor_partner',
                'solution_integration',
                'equipment_lease',
                'installation_contract'
            ],
            'description' => 'Hardware procurement and installation services',
            'sort_order' => 200,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'var',
                'procurement_aware' => true
            ]
        ]);
    }

    protected function getVarServicesContent(): string
    {
        return "HARDWARE PROCUREMENT AND SERVICES

Service Provider shall provide the following hardware procurement and related services:

**Product Categories:**
{{#if hardware_categories}}
Authorized to procure and provide: {{hardware_categories}}
{{/if}}

**Procurement Model:**
{{#if procurement_model}}
Service delivery model: {{procurement_model}}
{{/if}}
{{#if lead_time_days}}
Standard lead time: {{lead_time_days}} {{lead_time_type}}
{{/if}}

**Installation Services:**
{{#if includes_installation}}
- Hardware installation and setup
{{/if}}
{{#if includes_configuration}}
- System configuration and testing
{{/if}}
{{#if includes_project_management}}
- Project management and coordination
{{/if}}

Service Provider shall maintain vendor partnerships and certifications necessary to provide authorized hardware and support services.";
    }

    /**
     * Create Compliance-specific clauses for 4 template types
     */
    protected function createComplianceClauses()
    {
        // Compliance Services
        ContractClause::create([
            'company_id' => 1,
            'name' => 'Compliance Framework Services',
            'slug' => 'compliance-services',
            'category' => ContractClause::CATEGORY_SERVICES,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => $this->getComplianceServicesContent(),
            'variables' => [
                'compliance_frameworks',
                'risk_level',
                'industry_sector',
                'includes_internal_audits',
                'includes_external_audits',
                'comprehensive_audit_frequency'
            ],
            'applicable_contract_types' => [
                'business_associate',
                'professional_services',
                'data_processing',
                'master_service'
            ],
            'description' => 'Regulatory compliance and audit services',
            'sort_order' => 200,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'compliance',
                'framework_aware' => true
            ]
        ]);
    }

    protected function getComplianceServicesContent(): string
    {
        return "COMPLIANCE SERVICES

Service Provider shall provide comprehensive compliance services for the following regulatory frameworks:

**Regulatory Frameworks:**
{{#if compliance_frameworks}}
{{compliance_frameworks}}
{{/if}}

**Risk Assessment:**
{{#if risk_level}}
Risk Level: {{risk_level|title}}
{{/if}}
{{#if industry_sector}}
Industry Sector: {{industry_sector}}
{{/if}}

**Audit Services:**
{{#if includes_internal_audits}}
- Internal compliance audits
{{/if}}
{{#if includes_external_audits}}
- External audit coordination
{{/if}}
{{#if comprehensive_audit_frequency}}
- Comprehensive audits: {{comprehensive_audit_frequency}}
{{/if}}

Service Provider shall maintain current knowledge of applicable regulatory requirements and industry best practices.";
    }

    /**
     * Create General template clauses for 4 template types
     */
    protected function createGeneralClauses()
    {
        // General Services
        ContractClause::create([
            'company_id' => 1,
            'name' => 'General Service Provisions',
            'slug' => 'general-services',
            'category' => ContractClause::CATEGORY_SERVICES,
            'clause_type' => ContractClause::TYPE_REQUIRED,
            'content' => "SERVICES\n\nService Provider shall provide the services as specified in this agreement and its associated schedules. Service details and specifications are defined in Schedule A and related attachments.\n\nServices shall be performed in a professional and workmanlike manner consistent with industry standards and best practices.",
            'applicable_contract_types' => [
                'consumption_based',
                'international_service',
                'equipment_lease',
                'master_service'
            ],
            'description' => 'General service provisions for flexible contract types',
            'sort_order' => 200,
            'status' => ContractClause::STATUS_ACTIVE,
            'is_system' => true,
            'is_required' => true,
            'metadata' => [
                'template_category' => 'general',
                'flexible_content' => true
            ]
        ]);
    }
}