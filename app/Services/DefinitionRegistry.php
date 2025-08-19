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
            'definition' => 'This Recurring Support Services Agreement, inclusive of all schedules and exhibits attached hereto and incorporated herein by reference, specifically Schedule A and Schedule B, as may be amended from time to time in accordance with Section 12.',
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
            'definition' => 'Shall have the meaning set forth in Section 9.a.',
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
            'definition' => 'Shall have the meaning set forth in Section 15.',
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
            'definition' => 'Shall mean the recurring information technology support services to be furnished by {{service_provider_short_name}} to the Client, as delineated in Section 2 hereof and further specified in Schedule A, corresponding to the Supported Infrastructure and Service Tier selected by the Client.',
            'required_by' => ['scope_of_services'],
            'depends_on_variables' => ['service_provider_short_name'],
            'always_include' => false,
        ],
        
        'supported_infrastructure' => [
            'term' => 'Supported Infrastructure',
            'definition' => 'Shall mean the specific information technology hardware, software, and systems components designated for coverage under this Agreement{{#if has_service_sections}}, corresponding to the service sections selected by the Client{{#if service_section_a}} (Section A{{/if}}{{#if service_section_b}}, Section B{{/if}}{{#if service_section_c}}, Section C{{/if}}{{#if service_section_a}}){{/if}}{{/if}} and enumerated with specificity in Schedule A.',
            'required_by' => ['scope_of_services', 'asset_coverage'],
            'depends_on_variables' => ['service_section_a', 'service_section_b', 'service_section_c'],
            'always_include' => false,
        ],
        
        'term' => [
            'term' => 'Term',
            'definition' => 'Shall mean the duration of this Agreement as defined in Section 5.a, including the Initial Term and any Renewal Terms.',
            'required_by' => ['legal', 'termination'],
            'depends_on_variables' => [],
            'always_include' => false,
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
        $requiredDefinitions = [];
        
        foreach ($this->definitions as $key => $definition) {
            foreach ($definition['required_by'] as $requiredBy) {
                if (in_array($requiredBy, $categories)) {
                    $requiredDefinitions[$key] = $definition;
                    break;
                }
            }
        }
        
        return $requiredDefinitions;
    }

    /**
     * Get definitions that should always be included
     */
    public function getAlwaysIncludedDefinitions(): array
    {
        return array_filter($this->definitions, function ($definition) {
            return $definition['always_include'] ?? false;
        });
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
        
        // Add service section logic for supported_infrastructure
        if ($termKey === 'supported_infrastructure') {
            $variables['has_service_sections'] = 
                ($variables['service_section_a'] ?? false) || 
                ($variables['service_section_b'] ?? false) || 
                ($variables['service_section_c'] ?? false);
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
}