<?php

namespace App\Services;

/**
 * DefinitionRegistry
 * 
 * Centralized registry for all possible contract term definitions.
 * Manages which definitions are needed based on clause content and variables.
 */
class DefinitionRegistry
{
    /**
     * Registry of all available definitions with metadata
     */
    protected array $definitions = [
        'agreement' => [
            'term' => 'Agreement',
            'definition' => 'This Recurring Support Services Agreement, inclusive of all schedules and exhibits attached hereto and incorporated herein by reference, specifically Schedule A and Schedule B, as may be amended from time to time in accordance with {{admin_section_ref}}.',
            'required_by' => ['general', 'scope_of_services', 'legal'],
            'depends_on_variables' => [],
            'always_include' => true, // Core terms that should always be included
        ],
        
        'business_hours' => [
            'term' => 'Business Hours',
            'definition' => 'Shall mean {{business_hours}}, excluding {{service_provider_short_name}} recognized holidays as specified in Schedule A.',
            'required_by' => ['sla_terms', 'response_time'],
            'depends_on_variables' => ['business_hours', 'service_provider_short_name'],
            'always_include' => false,
        ],
        
        'confidential_information' => [
            'term' => 'Confidential Information',
            'definition' => 'Shall have the meaning set forth in {{confidentiality_section_ref}}.a.',
            'required_by' => ['confidentiality'],
            'depends_on_variables' => [],
            'always_include' => false,
        ],
        
        'emergency_support' => [
            'term' => 'Emergency Support',
            'definition' => 'Shall mean support necessitated by critical issues that materially and adversely impact the Client\'s core business operations, the specific conditions and response protocols for which shall be further defined in Schedule A based on the selected Service Tier.',
            'required_by' => ['sla_terms', 'emergency_services'],
            'depends_on_variables' => [],
            'always_include' => false,
        ],
        
        'force_majeure_event' => [
            'term' => 'Force Majeure Event',
            'definition' => 'Shall have the meaning set forth in {{legal_section_ref}}.',
            'required_by' => ['legal'],
            'depends_on_variables' => [],
            'always_include' => false,
        ],
        
        'response_time' => [
            'term' => 'Response Time',
            'definition' => 'Shall mean the target timeframe within which {{service_provider_short_name}} shall acknowledge receipt of a properly submitted Support Request, as specified for the applicable Service Tier in Schedule A.',
            'required_by' => ['sla_terms', 'service_levels'],
            'depends_on_variables' => ['service_provider_short_name'],
            'always_include' => false,
        ],
        
        'resolution_time' => [
            'term' => 'Resolution Time',
            'definition' => 'Shall mean the target timeframe within which {{service_provider_short_name}} shall endeavor to resolve a properly submitted Support Request, such timeframe potentially varying based on the issue\'s severity, complexity, and the applicable Service Tier, as specified in Schedule A. Resolution Times are targets and not guaranteed fix times.',
            'required_by' => ['sla_terms', 'service_levels'],
            'depends_on_variables' => ['service_provider_short_name'],
            'always_include' => false,
        ],
        
        'service_levels' => [
            'term' => 'Service Levels',
            'definition' => 'Shall mean the standards, performance metrics, Response Times, and Resolution Times governing {{service_provider_short_name}}\'s provision of the Support Services, as detailed for the selected Service Tier in Schedule A.',
            'required_by' => ['sla_terms'],
            'depends_on_variables' => ['service_provider_short_name'],
            'always_include' => false,
        ],
        
        'service_tier' => [
            'term' => 'Service Tier',
            'definition' => 'Shall mean the specific level of service (e.g., {{service_tier}}) selected by the Client for the Supported Infrastructure, as designated in Schedule A, which dictates the applicable Service Levels and fees (Schedule B).',
            'required_by' => ['sla_terms', 'pricing'],
            'depends_on_variables' => ['service_tier'],
            'always_include' => false,
        ],
        
        'support_request' => [
            'term' => 'Support Request',
            'definition' => 'Shall mean a request for technical assistance pertaining to the Supported Infrastructure, submitted by an authorized Client representative to {{service_provider_short_name}} in compliance with the procedures stipulated in Schedule A.',
            'required_by' => ['scope_of_services', 'sla_terms'],
            'depends_on_variables' => ['service_provider_short_name'],
            'always_include' => false,
        ],
        
        'support_services' => [
            'term' => 'Support Services',
            'definition' => 'Shall mean the recurring information technology support services to be furnished by {{service_provider_short_name}} to the Client, as delineated in {{services_section_ref}} hereof and further specified in Schedule A, corresponding to the Supported Infrastructure and Service Tier selected by the Client.',
            'required_by' => ['scope_of_services'],
            'depends_on_variables' => ['service_provider_short_name'],
            'always_include' => false,
        ],
        
        // MSP-specific definitions
        'supported_infrastructure' => [
            'term' => 'Supported Infrastructure',
            'definition' => 'Shall mean the specific information technology hardware, software, and systems components designated for coverage under this Agreement, encompassing {{supported_asset_types}} as enumerated with specificity in Schedule A.',
            'required_by' => ['msp_services', 'scope_of_services'],
            'depends_on_variables' => ['supported_asset_types'],
            'always_include' => false,
            'template_category' => 'msp',
        ],
        
        'managed_infrastructure' => [
            'term' => 'Managed Infrastructure',
            'definition' => 'The {{supported_asset_types}} and associated systems under active management and monitoring by {{service_provider_short_name}}, providing {{service_tier}} tier service levels.',
            'required_by' => ['msp_services'],
            'depends_on_variables' => ['supported_asset_types', 'service_provider_short_name', 'service_tier'],
            'always_include' => false,
            'template_category' => 'msp',
        ],
        
        // VoIP-specific definitions
        'voip_services' => [
            'term' => 'VoIP Services',
            'definition' => 'Voice over Internet Protocol telecommunications services including {{channel_count}} concurrent channels using {{protocol|upper}} protocol with {{calling_plan}} calling capabilities.',
            'required_by' => ['voip_services', 'telecommunications'],
            'depends_on_variables' => ['channel_count', 'protocol', 'calling_plan'],
            'always_include' => false,
            'template_category' => 'voip',
        ],
        
        'quality_of_service' => [
            'term' => 'Quality of Service (QoS)',
            'definition' => 'The measurement of service performance including call completion rates, latency ({{latency_ms}}ms max), jitter ({{jitter_ms}}ms max), packet loss (<{{packet_loss_percent}}%), and Mean Opinion Score ({{mos_score}} minimum).',
            'required_by' => ['voip_qos', 'telecommunications_sla'],
            'depends_on_variables' => ['latency_ms', 'jitter_ms', 'packet_loss_percent', 'mos_score'],
            'always_include' => false,
            'template_category' => 'voip',
        ],
        
        'emergency_services' => [
            'term' => 'Emergency Services',
            'definition' => 'Enhanced 911 (E911) services providing emergency response capabilities including {{#if karis_law}}Kari\'s Law compliance for direct 911 dialing{{/if}}{{#if ray_baums}} and RAY BAUM\'s Act compliance for dispatchable location information{{/if}}.',
            'required_by' => ['e911_services', 'telecommunications'],
            'depends_on_variables' => ['karis_law', 'ray_baums'],
            'always_include' => false,
            'template_category' => 'voip',
        ],
        
        // VAR-specific definitions
        'hardware_procurement' => [
            'term' => 'Hardware Procurement Services',
            'definition' => 'Professional services for sourcing, purchasing, and delivering {{hardware_categories}} using {{procurement_model|replace_underscore}} model with {{lead_time_days}} {{lead_time_type}} standard lead time.',
            'required_by' => ['var_services', 'procurement'],
            'depends_on_variables' => ['hardware_categories', 'procurement_model', 'lead_time_days', 'lead_time_type'],
            'always_include' => false,
            'template_category' => 'var',
        ],
        
        'installation_services' => [
            'term' => 'Installation Services',
            'definition' => 'Hardware installation and configuration services including {{#if includes_installation}}basic installation{{/if}}{{#if includes_rack_stack}}, rack and stack services{{/if}}{{#if includes_configuration}}, system configuration{{/if}}{{#if includes_project_management}}, and project management{{/if}}.',
            'required_by' => ['var_services', 'installation'],
            'depends_on_variables' => ['includes_installation', 'includes_rack_stack', 'includes_configuration', 'includes_project_management'],
            'always_include' => false,
            'template_category' => 'var',
        ],
        
        'warranty_coverage' => [
            'term' => 'Warranty Coverage',
            'definition' => 'Hardware warranty protection for {{hardware_warranty_period|replace_underscore}} with support coverage for {{support_warranty_period|replace_underscore}}{{#if onsite_warranty_support}}, including on-site warranty support{{/if}}{{#if advanced_replacement}} and advanced replacement services{{/if}}.',
            'required_by' => ['var_warranty', 'hardware_services'],
            'depends_on_variables' => ['hardware_warranty_period', 'support_warranty_period', 'onsite_warranty_support', 'advanced_replacement'],
            'always_include' => false,
            'template_category' => 'var',
        ],
        
        // Compliance-specific definitions
        'compliance_frameworks' => [
            'term' => 'Compliance Frameworks',
            'definition' => 'The regulatory and industry standards applicable to this engagement, specifically {{compliance_frameworks}}, with {{risk_level}} risk assessment level{{#if industry_sector}} for the {{industry_sector}} industry sector{{/if}}.',
            'required_by' => ['compliance_services', 'regulatory'],
            'depends_on_variables' => ['compliance_frameworks', 'risk_level', 'industry_sector'],
            'always_include' => false,
            'template_category' => 'compliance',
        ],
        
        'audit_services' => [
            'term' => 'Audit Services',
            'definition' => 'Compliance assessment and validation services including {{#if includes_internal_audits}}internal audits{{/if}}{{#if includes_external_audits}}, external audit coordination{{/if}}{{#if includes_penetration_testing}}, penetration testing{{/if}}{{#if includes_vulnerability_scanning}}, and vulnerability scanning{{/if}} performed {{comprehensive_audit_frequency}}.',
            'required_by' => ['compliance_audits', 'regulatory_services'],
            'depends_on_variables' => ['includes_internal_audits', 'includes_external_audits', 'includes_penetration_testing', 'includes_vulnerability_scanning', 'comprehensive_audit_frequency'],
            'always_include' => false,
            'template_category' => 'compliance',
        ],
        
        'training_programs' => [
            'term' => 'Training Programs',
            'definition' => 'Compliance and security awareness training including {{training_programs}} delivered via {{training_delivery_method|replace_underscore}} method on a {{training_frequency}} basis.',
            'required_by' => ['compliance_training', 'security_awareness'],
            'depends_on_variables' => ['training_programs', 'training_delivery_method', 'training_frequency'],
            'always_include' => false,
            'template_category' => 'compliance',
        ],
        
        // Financial definitions
        'billing_model' => [
            'term' => 'Billing Model',
            'definition' => '{{billing_model|replace_underscore_title}} billing structure{{#if monthly_base_rate}} with {{monthly_base_rate}} monthly base rate{{/if}}{{#if hourly_rate}} and {{hourly_rate}} hourly rate for additional services{{/if}}.',
            'required_by' => ['financial_terms', 'pricing'],
            'depends_on_variables' => ['billing_model', 'monthly_base_rate', 'hourly_rate'],
            'always_include' => false,
            'template_category' => ['msp', 'voip', 'var'],
        ],
        
        // General definitions
        'contract_term' => [
            'term' => 'Term',
            'definition' => 'Shall mean the duration of this Agreement as defined in {{financial_section_ref}}.a, including the Initial Term and any Renewal Terms.',
            'required_by' => ['legal', 'termination'],
            'depends_on_variables' => [],
            'always_include' => true,
        ],
    ];

    /**
     * Get definition for a specific term
     */
    public function getDefinition(string $termKey): ?array
    {
        return $this->definitions[$termKey] ?? null;
    }

    /**
     * Get all available definitions
     */
    public function getAllDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * Get definitions required by specific clause categories
     */
    public function getDefinitionsForCategories(array $categories): array
    {
        return $this->filterDefinitions(function ($definition) use ($categories) {
            return !empty(array_intersect($definition['required_by'], $categories));
        });
    }

    /**
     * Get definitions that should always be included
     */
    public function getAlwaysIncludedDefinitions(): array
    {
        return $this->filterDefinitions(function ($definition) {
            return $definition['always_include'] ?? false;
        });
    }

    /**
     * Get definitions for specific template category
     */
    public function getDefinitionsForTemplateCategory(string $templateCategory): array
    {
        return $this->filterDefinitions(function ($definition) use ($templateCategory) {
            // Always include definitions
            if ($definition['always_include'] ?? false) {
                return true;
            }
            
            // Check template category match
            return $this->matchesTemplateCategory($definition, $templateCategory);
        });
    }

    /**
     * Check if variables are available for a definition
     */
    public function hasRequiredVariables(string $termKey, array $variables): bool
    {
        $definition = $this->getDefinition($termKey);
        if (!$definition) {
            return false;
        }
        
        $requiredVars = $definition['depends_on_variables'] ?? [];
        
        foreach ($requiredVars as $var) {
            if (!isset($variables[$var]) || empty($variables[$var])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Generate definition content with variable substitution
     */
    public function generateDefinitionContent(string $termKey, array $variables): string
    {
        $definition = $this->getDefinition($termKey);
        if (!$definition) {
            return '';
        }

        $content = $definition['definition'];
        
        // Add modern asset-based logic for supported_infrastructure
        if ($termKey === 'supported_infrastructure') {
            $variables['has_asset_types'] = 
                !empty($variables['supported_asset_types']);
        }

        // Process handlebars conditionals
        $content = $this->processConditionals($content, $variables);
        
        // Process variable substitutions
        foreach ($variables as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }

        return $definition['term'] . ': ' . $content;
    }

    /**
     * Process handlebars-style conditionals
     */
    protected function processConditionals(string $content, array $variables): string
    {
        // Pattern to match {{#if variable}}...{{/if}} blocks
        $pattern = '/\{\{#if\s+([^}]+)\}\}(.*?)\{\{\/if\}\}/s';
        
        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variable = trim($matches[1]);
            $conditionalContent = $matches[2];
            
            // Check if the variable exists and is truthy
            if (isset($variables[$variable]) && $this->isTruthy($variables[$variable])) {
                return $conditionalContent;
            }
            
            return ''; // Remove the block if condition is false
        }, $content);
    }

    /**
     * Check if a value is truthy for conditional logic
     */
    protected function isTruthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return !empty($value) && strtolower($value) !== 'false' && strtolower($value) !== 'no';
        }
        if (is_numeric($value)) {
            return $value != 0;
        }
        if (is_array($value)) {
            return !empty($value);
        }
        return !empty($value);
    }

    /**
     * Analyze clause content to determine required definitions
     */
    public function analyzeRequiredDefinitions(array $clauseContents): array
    {
        $requiredDefinitions = [];
        
        foreach ($this->definitions as $key => $definition) {
            $term = $definition['term'];
            
            // Check if this term appears in any clause content
            foreach ($clauseContents as $content) {
                if (stripos($content, $term) !== false) {
                    $requiredDefinitions[$key] = $definition;
                    break;
                }
            }
        }
        
        // Always include core definitions
        $alwaysIncluded = $this->getAlwaysIncludedDefinitions();
        $requiredDefinitions = array_merge($alwaysIncluded, $requiredDefinitions);
        
        return $requiredDefinitions;
    }

    /**
     * Filter definitions by a given callback function
     */
    protected function filterDefinitions(callable $callback): array
    {
        $filtered = [];
        
        foreach ($this->definitions as $key => $definition) {
            if ($callback($definition)) {
                $filtered[$key] = $definition;
            }
        }
        
        return $filtered;
    }

    /**
     * Check if a definition matches a template category
     */
    protected function matchesTemplateCategory(array $definition, string $templateCategory): bool
    {
        $defCategory = $definition['template_category'] ?? null;
        
        if ($defCategory === null) {
            // Include general definitions for all templates
            return true;
        }
        
        if (is_array($defCategory)) {
            return in_array($templateCategory, $defCategory);
        }
        
        return $defCategory === $templateCategory;
    }
}