<?php

namespace App\Domains\Core\Services;

/**
 * Definition Registry Service
 *
 * Manages standardized definition content for contract clauses.
 * Provides centralized definition repository with proper variable substitution.
 */
class DefinitionRegistryService
{
    protected DefinitionRegistry $registry;

    public function __construct(DefinitionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Get the standardized definition content for a given definition key.
     */
    public function getDefinitionContent(string $key): ?string
    {
        $definitions = $this->getDefinitionRegistry();

        return $definitions[$key] ?? null;
    }

    /**
     * Get multiple definition contents.
     */
    public function getDefinitionContents(array $keys): array
    {
        $contents = [];
        foreach ($keys as $key) {
            $content = $this->getDefinitionContent($key);
            if ($content !== null) {
                $contents[$key] = $content;
            }
        }

        return $contents;
    }

    /**
     * Get all available definitions.
     */
    public function getAllDefinitions(): array
    {
        return $this->getDefinitionRegistry();
    }

    /**
     * Get definition keys that are available.
     */
    public function getAvailableKeys(): array
    {
        return array_keys($this->getDefinitionRegistry());
    }

    /**
     * Check if a definition key exists.
     */
    public function hasDefinition(string $key): bool
    {
        return array_key_exists($key, $this->getDefinitionRegistry());
    }

    /**
     * Generate dynamic definitions content based on required definition keys.
     */
    public function generateDefinitionsSection(array $requiredKeys, array $variables = []): string
    {
        if (empty($requiredKeys)) {
            return '';
        }

        $definitions = $this->getDefinitionContents($requiredKeys);

        if (empty($definitions)) {
            return '';
        }

        $content = "DEFINITIONS\n\n";

        foreach ($definitions as $key => $definition) {
            // Process variables in definition content
            $processedDefinition = $this->processVariables($definition, $variables);
            $content .= $processedDefinition."\n\n";
        }

        return trim($content);
    }

    /**
     * Process variables in definition content.
     */
    protected function processVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        return $content;
    }

    /**
     * Get the definition registry with standardized content.
     */
    protected function getDefinitionRegistry(): array
    {
        $definitions = [];
        $structuredDefinitions = $this->registry->getAllDefinitions();

        // Transform structured definitions to simple key-value format
        foreach ($structuredDefinitions as $key => $definition) {
            $definitions[$key] = $definition['term'].': '.$definition['definition'];
        }

        return $definitions;
    }

    /**
     * Get definitions organized by category for UI display.
     */
    public function getDefinitionsByCategory(): array
    {
        return [
            'Core Terms' => [
                'agreement' => 'Agreement',
                'client' => 'Client',
                'service_provider' => 'Service Provider',
                'service_provider_short_name' => 'Service Provider (Short)',
                'term' => 'Term',
            ],
            'Service Terms' => [
                'support_services' => 'Support Services',
                'supported_infrastructure' => 'Supported Infrastructure',
                'support_request' => 'Support Request',
                'service_tier' => 'Service Tier',
                'service_levels' => 'Service Levels',
                'emergency_support' => 'Emergency Support',
            ],
            'Time & Response' => [
                'business_hours' => 'Business Hours',
                'response_time' => 'Response Time',
                'resolution_time' => 'Resolution Time',
            ],
            'Legal & Compliance' => [
                'confidential_information' => 'Confidential Information',
                'force_majeure_event' => 'Force Majeure Event',
            ],
            'Section References' => [
                'admin_section_ref' => 'Administrative Section Reference',
                'confidentiality_section_ref' => 'Confidentiality Section Reference',
                'legal_section_ref' => 'Legal Section Reference',
                'services_section_ref' => 'Services Section Reference',
            ],
        ];
    }

    /**
     * Validate that all required definitions exist in the registry.
     */
    public function validateDefinitions(array $keys): array
    {
        $missing = [];
        foreach ($keys as $key) {
            if (! $this->hasDefinition($key)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * Get definition suggestions based on clause content analysis.
     */
    public function suggestDefinitions(string $content): array
    {
        $suggestions = [];
        $registry = $this->getDefinitionRegistry();

        // Analyze content for terms that might need definitions
        foreach ($registry as $key => $definition) {
            // Extract the term name from the definition
            if (preg_match('/^([^:]+):/', $definition, $matches)) {
                $term = trim($matches[1]);

                // Check if the term appears in the content
                if (preg_match('/\b'.preg_quote($term, '/').'\b/i', $content)) {
                    $suggestions[] = $key;
                }
            }
        }

        return array_unique($suggestions);
    }
}
