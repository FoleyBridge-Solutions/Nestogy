<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractTemplate;
use App\Services\DefinitionRegistry;
use Illuminate\Support\Collection;

/**
 * ContractClauseService
 * 
 * Handles contract generation from modular clauses with dynamic section numbering.
 */
class ContractClauseService
{
    protected DefinitionRegistry $definitionRegistry;

    public function __construct(DefinitionRegistry $definitionRegistry = null)
    {
        $this->definitionRegistry = $definitionRegistry ?: new DefinitionRegistry();
    }
    /**
     * Generate contract content from template clauses.
     */
    public function generateContractFromClauses(ContractTemplate $template, array $variables): string
    {
        // Get clauses in order
        $clauses = $template->clauses()
                            ->active()
                            ->orderBy('contract_template_clauses.sort_order')
                            ->get();
        
        // Validate dependencies before generation
        $dependencyErrors = $this->validateClauseDependencies($template);
        if (!empty($dependencyErrors)) {
            throw new \Exception('Contract generation failed due to dependency errors: ' . implode('; ', $dependencyErrors));
        }
        
        // Resolve clause inclusion based on conditions and dependencies
        $resolvedClauses = $this->resolveClauseInclusion($clauses, $variables);
        
        // Generate dynamic definitions based on included clauses
        $resolvedClauses = $this->replaceDynamicDefinitions($resolvedClauses, $variables);
        
        // Group resolved clauses by category to create sections
        $clausesByCategory = $this->groupClausesByCategory($resolvedClauses);
        
        // Generate section headers and clause numbering
        $sectionHeaders = $this->generateSectionHeaders($clausesByCategory);
        $clauseNumbers = $this->generateClauseNumbers($clausesByCategory);
        
        // Process each section and its clauses
        $contractContent = '';
        foreach ($clausesByCategory as $category => $categoryData) {
            $sectionHeader = $sectionHeaders[$category] ?? '';
            $categoryClauses = $categoryData['clauses'];
            
            // Add section header if not header category
            if ($category !== 'header' && !empty($sectionHeader)) {
                $contractContent .= $sectionHeader . "\n\n";
            }
            
            // Process clauses in this section
            foreach ($categoryClauses as $clause) {
                $clauseNumber = $clauseNumbers[$clause->id] ?? [];
                $templateConditions = $clause->pivot->conditions ?? [];
                
                $processedContent = $clause->processContent($variables, $templateConditions, $clauseNumber);
                
                // Skip empty content (e.g., from failed conditions)
                if (!empty(trim($processedContent))) {
                    $contractContent .= $processedContent . "\n\n";
                }
            }
        }
        
        return $contractContent;
    }

    /**
     * Group clauses by category to create logical sections.
     */
    protected function groupClausesByCategory(Collection $clauses): array
    {
        $grouped = [];
        $categoryOrder = [
            'header' => 0,
            'definitions' => 1, 
            'services' => 2,
            'sla' => 3,
            'obligations' => 4,
            'financial' => 5,
            'exclusions' => 6,
            'warranties' => 7,
            'confidentiality' => 8,
            'data_protection' => 9,
            'intellectual_property' => 10,
            'compliance' => 11,
            'change_management' => 12,
            'legal' => 13,
            'admin' => 14,
            'signature' => 15
        ];
        
        foreach ($clauses as $clause) {
            $category = $clause->category;
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'clauses' => collect(),
                    'order' => $categoryOrder[$category] ?? 999
                ];
            }
            $grouped[$category]['clauses']->push($clause);
        }
        
        // Sort by category order
        uasort($grouped, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        
        return $grouped;
    }

    /**
     * Generate section headers for each category.
     */
    protected function generateSectionHeaders(array $clausesByCategory): array
    {
        $headers = [];
        $sectionNumber = 1;
        
        $sectionTitles = [
            'definitions' => 'DEFINITIONS',
            'services' => 'SCOPE OF SUPPORT SERVICES', 
            'sla' => 'SERVICE LEVEL AGREEMENTS',
            'obligations' => 'CLIENT OBLIGATIONS AND RESPONSIBILITIES',
            'financial' => 'FEES AND PAYMENT TERMS',
            'exclusions' => 'EXCLUSIONS FROM SUPPORT SERVICES',
            'warranties' => 'WARRANTIES AND DISCLAIMERS',
            'confidentiality' => 'CONFIDENTIALITY',
            'data_protection' => 'DATA PROTECTION AND PRIVACY',
            'intellectual_property' => 'INTELLECTUAL PROPERTY',
            'compliance' => 'COMPLIANCE AND REGULATORY REQUIREMENTS',
            'change_management' => 'CHANGE MANAGEMENT',
            'legal' => 'GOVERNING LAW AND DISPUTE RESOLUTION',
            'admin' => 'GENERAL PROVISIONS',
            'signature' => '' // No header for signature section
        ];
        
        foreach ($clausesByCategory as $category => $data) {
            if ($category === 'header' || $category === 'signature') {
                $headers[$category] = '';
                continue;
            }
            
            $title = $sectionTitles[$category] ?? strtoupper(str_replace('_', ' ', $category));
            $headers[$category] = "{$sectionNumber}. {$title}";
            $sectionNumber++;
        }
        
        return $headers;
    }

    /**
     * Generate clause numbering within each section.
     */
    protected function generateClauseNumbers(array $clausesByCategory): array
    {
        $clauseNumbers = [];
        
        foreach ($clausesByCategory as $category => $data) {
            $clauses = $data['clauses'];
            $clauseCounter = 1;
            
            foreach ($clauses as $clause) {
                if ($category === 'header' || $category === 'signature') {
                    // No numbering for header and signature
                    $clauseNumbers[$clause->id] = [];
                } else {
                    // Number clauses within sections as subsections
                    $clauseNumbers[$clause->id] = [
                        'clause_number' => $clauseCounter,
                        'section_number' => $this->getSectionNumberForCategory($category, $clausesByCategory)
                    ];
                    $clauseCounter++;
                }
            }
        }
        
        return $clauseNumbers;
    }

    /**
     * Get the section number for a given category.
     */
    protected function getSectionNumberForCategory(string $category, array $clausesByCategory): int
    {
        $sectionNumber = 1;
        foreach ($clausesByCategory as $cat => $data) {
            if ($cat === 'header' || $cat === 'signature') {
                continue;
            }
            if ($cat === $category) {
                return $sectionNumber;
            }
            $sectionNumber++;
        }
        return $sectionNumber;
    }

    /**
     * Resolve which clauses should be included based on conditions and dependencies.
     */
    protected function resolveClauseInclusion(Collection $clauses, array $variables): Collection
    {
        $includedClauses = collect();
        $excludedClauses = collect();
        
        // First pass: Evaluate conditional clauses
        foreach ($clauses as $clause) {
            if ($clause->isConditional()) {
                if ($this->evaluateClauseConditions($clause, $variables)) {
                    $includedClauses->push($clause);
                } else {
                    $excludedClauses->push($clause);
                }
            } else {
                // Required and optional clauses are included by default
                $includedClauses->push($clause);
            }
        }
        
        // Second pass: Check dependencies and auto-include required dependencies
        $finalClauses = $this->resolveDependencies($includedClauses, $clauses);
        
        // Third pass: Check for conflicts and remove conflicting clauses
        $finalClauses = $this->resolveConflicts($finalClauses);
        
        return $finalClauses;
    }

    /**
     * Evaluate clause conditions to determine if it should be included.
     */
    protected function evaluateClauseConditions(ContractClause $clause, array $variables): bool
    {
        $conditions = $clause->getConditions();
        
        if (empty($conditions)) {
            return true; // No conditions means always include
        }
        
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $variables)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Evaluate a single condition.
     */
    protected function evaluateCondition(array $condition, array $variables): bool
    {
        $type = $condition['type'] ?? 'equals';
        $variable = $condition['variable'] ?? '';
        $value = $condition['value'] ?? null;
        
        $actualValue = $variables[$variable] ?? null;
        
        switch ($type) {
            case 'equals':
                return $actualValue == $value;
            case 'not_equals':
                return $actualValue != $value;
            case 'exists':
                return isset($variables[$variable]);
            case 'not_exists':
                return !isset($variables[$variable]);
            case 'truthy':
                return !empty($actualValue);
            case 'falsy':
                return empty($actualValue);
            case 'contains':
                return is_string($actualValue) && strpos($actualValue, $value) !== false;
            case 'in_array':
                return is_array($value) && in_array($actualValue, $value);
            default:
                return true;
        }
    }

    /**
     * Resolve dependencies by auto-including required dependent clauses.
     */
    protected function resolveDependencies(Collection $includedClauses, Collection $allClauses): Collection
    {
        $finalClauses = $includedClauses->keyBy('slug');
        $added = true;
        
        // Keep adding dependencies until no new ones are found
        while ($added) {
            $added = false;
            
            foreach ($finalClauses as $clause) {
                if ($clause->hasDependencies()) {
                    $dependencies = $clause->getDependencies();
                    
                    foreach ($dependencies as $dependencySlug) {
                        if (!$finalClauses->has($dependencySlug)) {
                            // Find the dependency clause
                            $dependencyClause = $allClauses->firstWhere('slug', $dependencySlug);
                            
                            if ($dependencyClause) {
                                $finalClauses[$dependencySlug] = $dependencyClause;
                                $added = true;
                            }
                        }
                    }
                }
            }
        }
        
        return $finalClauses->values();
    }

    /**
     * Resolve conflicts by removing conflicting clauses based on priority.
     */
    protected function resolveConflicts(Collection $clauses): Collection
    {
        $finalClauses = collect();
        $conflictMap = [];
        
        // Build conflict map
        foreach ($clauses as $clause) {
            if ($clause->hasConflicts()) {
                $conflicts = $clause->getConflicts();
                foreach ($conflicts as $conflictSlug) {
                    $conflictMap[$clause->slug][] = $conflictSlug;
                }
            }
        }
        
        // Process clauses and resolve conflicts
        foreach ($clauses as $clause) {
            $hasConflict = false;
            
            if (isset($conflictMap[$clause->slug])) {
                $conflicts = $conflictMap[$clause->slug];
                
                // Check if any conflicting clause is already included
                foreach ($finalClauses as $existingClause) {
                    if (in_array($existingClause->slug, $conflicts)) {
                        // Resolve conflict based on priority (required > optional, system > user)
                        if ($this->getClausePriority($clause) > $this->getClausePriority($existingClause)) {
                            // Remove the existing clause and add this one
                            $finalClauses = $finalClauses->reject(function($c) use ($existingClause) {
                                return $c->slug === $existingClause->slug;
                            });
                        } else {
                            // Skip this clause
                            $hasConflict = true;
                            break;
                        }
                    }
                }
            }
            
            if (!$hasConflict) {
                $finalClauses->push($clause);
            }
        }
        
        return $finalClauses;
    }

    /**
     * Get clause priority for conflict resolution.
     */
    protected function getClausePriority(ContractClause $clause): int
    {
        $priority = 0;
        
        // Required clauses have higher priority
        if ($clause->is_required) {
            $priority += 100;
        }
        
        // System clauses have higher priority
        if ($clause->is_system) {
            $priority += 50;
        }
        
        // Clause type priority
        switch ($clause->clause_type) {
            case 'required':
                $priority += 30;
                break;
            case 'conditional':
                $priority += 20;
                break;
            case 'optional':
                $priority += 10;
                break;
        }
        
        return $priority;
    }

    /**
     * Validate clause dependencies for a template.
     */
    public function validateClauseDependencies(ContractTemplate $template): array
    {
        $clauses = $template->clauses;
        $errors = [];
        $clauseSlugs = $clauses->pluck('slug')->toArray();
        
        foreach ($clauses as $clause) {
            // Check dependencies
            if ($clause->hasDependencies()) {
                $dependencies = $clause->getDependencies();
                foreach ($dependencies as $dependencySlug) {
                    if (!in_array($dependencySlug, $clauseSlugs)) {
                        $errors[] = "Clause '{$clause->name}' requires '{$dependencySlug}' but it's not included in template";
                    }
                }
            }
            
            // Check conflicts
            if ($clause->hasConflicts()) {
                $conflicts = $clause->getConflicts();
                foreach ($conflicts as $conflictSlug) {
                    if (in_array($conflictSlug, $clauseSlugs)) {
                        $errors[] = "Clause '{$clause->name}' conflicts with '{$conflictSlug}' - both cannot be in the same template";
                    }
                }
            }
        }
        
        return $errors;
    }

    /**
     * Get missing required clauses for a contract type.
     */
    public function getMissingRequiredClauses(ContractTemplate $template): array
    {
        $existingCategories = $template->clauses->pluck('category')->unique()->toArray();
        
        // Define required categories by contract type
        $requiredCategories = $this->getRequiredCategoriesForContractType($template->template_type);
        
        $missingCategories = array_diff($requiredCategories, $existingCategories);
        
        return $missingCategories;
    }

    /**
     * Get required clause categories for a specific contract type.
     */
    protected function getRequiredCategoriesForContractType(string $contractType): array
    {
        $baseRequired = ['header', 'definitions', 'services', 'financial', 'legal', 'signature'];
        
        $typeSpecificRequired = [
            'managed_services' => ['sla', 'obligations', 'warranties', 'exclusions'],
            'cybersecurity_services' => ['compliance', 'data_protection', 'confidentiality'],
            'data_processing' => ['data_protection', 'compliance', 'confidentiality'],
            'business_associate' => ['compliance', 'data_protection', 'confidentiality'],
            'hosted_pbx' => ['sla', 'warranties'],
            'sip_trunking' => ['sla', 'warranties'],
            'professional_services' => ['intellectual_property', 'warranties'],
            'software_licensing' => ['intellectual_property', 'warranties'],
        ];
        
        $additional = $typeSpecificRequired[$contractType] ?? [];
        
        return array_merge($baseRequired, $additional);
    }

    /**
     * Add clause to template with automatic dependency resolution.
     */
    public function addClauseToTemplate(ContractTemplate $template, ContractClause $clause, array $options = []): array
    {
        $addedClauses = [];
        $errors = [];
        
        // Check if clause is already in template
        if ($template->clauses()->where('contract_clauses.id', $clause->id)->exists()) {
            return [
                'added' => [],
                'errors' => ["Clause '{$clause->name}' is already in template"]
            ];
        }
        
        // Check for conflicts first
        if ($clause->hasConflicts()) {
            $conflicts = $clause->getConflicts();
            $existingClauses = $template->clauses->pluck('slug')->toArray();
            
            foreach ($conflicts as $conflictSlug) {
                if (in_array($conflictSlug, $existingClauses)) {
                    $conflictingClause = $template->clauses->firstWhere('slug', $conflictSlug);
                    $errors[] = "Cannot add '{$clause->name}' - conflicts with existing clause '{$conflictingClause->name}'";
                }
            }
        }
        
        if (!empty($errors)) {
            return ['added' => [], 'errors' => $errors];
        }
        
        // Add dependencies first
        if ($clause->hasDependencies()) {
            $dependencies = $clause->getDependencies();
            $existingClauses = $template->clauses->pluck('slug')->toArray();
            
            foreach ($dependencies as $dependencySlug) {
                if (!in_array($dependencySlug, $existingClauses)) {
                    // Find and add the dependency
                    $dependencyClause = ContractClause::where('company_id', $template->company_id)
                                                     ->where('slug', $dependencySlug)
                                                     ->where('status', 'active')
                                                     ->first();
                    
                    if ($dependencyClause) {
                        $this->attachClauseToTemplate($template, $dependencyClause, [
                            'auto_added' => true,
                            'reason' => "Required dependency for '{$clause->name}'"
                        ]);
                        $addedClauses[] = $dependencyClause;
                    } else {
                        $errors[] = "Required dependency '{$dependencySlug}' not found for clause '{$clause->name}'";
                    }
                }
            }
        }
        
        // Add the main clause if no errors
        if (empty($errors)) {
            $this->attachClauseToTemplate($template, $clause, $options);
            $addedClauses[] = $clause;
        }
        
        return [
            'added' => $addedClauses,
            'errors' => $errors
        ];
    }

    /**
     * Remove clause from template with dependency checking.
     */
    public function removeClauseFromTemplate(ContractTemplate $template, ContractClause $clause, array $options = []): array
    {
        $removedClauses = [];
        $errors = [];
        $warnings = [];
        
        // Check if any other clauses depend on this one
        $dependentClauses = $template->clauses->filter(function($otherClause) use ($clause) {
            return $otherClause->id !== $clause->id && 
                   $otherClause->hasDependencies() && 
                   in_array($clause->slug, $otherClause->getDependencies());
        });
        
        if ($dependentClauses->isNotEmpty() && !($options['force'] ?? false)) {
            $dependentNames = $dependentClauses->pluck('name')->toArray();
            $errors[] = "Cannot remove '{$clause->name}' - required by: " . implode(', ', $dependentNames);
            $warnings[] = "Use force option to remove anyway";
        } else {
            // Remove dependent clauses first if forcing
            if ($dependentClauses->isNotEmpty() && ($options['force'] ?? false)) {
                foreach ($dependentClauses as $dependentClause) {
                    $template->clauses()->detach($dependentClause->id);
                    $removedClauses[] = $dependentClause;
                }
            }
            
            // Remove the main clause
            $template->clauses()->detach($clause->id);
            $removedClauses[] = $clause;
        }
        
        return [
            'removed' => $removedClauses,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Attach clause to template with proper pivot data.
     */
    protected function attachClauseToTemplate(ContractTemplate $template, ContractClause $clause, array $options = []): void
    {
        $metadata = array_merge([
            'auto_added' => $options['auto_added'] ?? false,
            'reason' => $options['reason'] ?? null,
            'added_at' => now()->toISOString(),
        ], $options['metadata'] ?? []);
        
        $pivotData = [
            'sort_order' => $options['sort_order'] ?? $clause->sort_order,
            'is_required' => $options['is_required'] ?? $clause->is_required,
            'conditions' => isset($options['conditions']) ? json_encode($options['conditions']) : null,
            'variable_overrides' => isset($options['variable_overrides']) ? json_encode($options['variable_overrides']) : null,
            'metadata' => json_encode($metadata)
        ];
        
        $template->clauses()->attach($clause->id, $pivotData);
    }

    /**
     * Get dependency tree for a clause.
     */
    public function getDependencyTree(ContractClause $clause, array $visited = []): array
    {
        $tree = [
            'clause' => $clause,
            'dependencies' => [],
            'conflicts' => $clause->getConflicts()
        ];
        
        if (in_array($clause->id, $visited)) {
            $tree['circular_dependency'] = true;
            return $tree;
        }
        
        $visited[] = $clause->id;
        
        if ($clause->hasDependencies()) {
            foreach ($clause->getDependencies() as $dependencySlug) {
                $dependencyClause = ContractClause::where('company_id', $clause->company_id)
                                                  ->where('slug', $dependencySlug)
                                                  ->where('status', 'active')
                                                  ->first();
                
                if ($dependencyClause) {
                    $tree['dependencies'][] = $this->getDependencyTree($dependencyClause, $visited);
                } else {
                    $tree['dependencies'][] = [
                        'missing' => $dependencySlug,
                        'error' => 'Dependency not found'
                    ];
                }
            }
        }
        
        return $tree;
    }

    /**
     * Generate dynamic section numbers for a collection of clauses.
     * @deprecated Use generateClauseNumbers instead
     */
    protected function generateSectionNumbers(Collection $clauses): array
    {
        $sectionNumbers = [];
        $currentNumbers = [
            1 => 1, // Main sections (1, 2, 3...)
            2 => 1, // Subsections (a, b, c...)
            3 => 1, // Sub-subsections (i, ii, iii...)
        ];
        
        foreach ($clauses as $clause) {
            $numberingInfo = $clause->getSectionNumberingInfo();
            
            if ($clause->createsSection()) {
                $level = $clause->getSectionLevel();
                $format = $numberingInfo['format'] ?? 'numeric';
                $prefix = $numberingInfo['prefix'] ?? '';
                
                // Generate the section number based on format
                $number = $this->formatSectionNumber($currentNumbers[$level], $format);
                
                // Add prefix if specified
                if (!empty($prefix)) {
                    $number = $prefix . ' ' . $number;
                }
                
                // Store the section numbers for this clause
                $sectionNumbers[$clause->id] = [
                    'section_number' => $number,
                    'section_level' => $level,
                    'raw_number' => $currentNumbers[$level],
                ];
                
                // Increment counter for this level
                $currentNumbers[$level]++;
                
                // Reset lower level counters when a higher level increments
                for ($resetLevel = $level + 1; $resetLevel <= 3; $resetLevel++) {
                    if (isset($currentNumbers[$resetLevel])) {
                        $currentNumbers[$resetLevel] = 1;
                    }
                }
            }
        }
        
        return $sectionNumbers;
    }

    /**
     * Format a section number based on the specified format.
     */
    protected function formatSectionNumber(int $number, string $format): string
    {
        switch ($format) {
            case 'alpha':
                // Convert to letters: 1=a, 2=b, etc.
                return chr(96 + $number); // 97 is 'a'
                
            case 'alpha_upper':
                // Convert to uppercase letters: 1=A, 2=B, etc.
                return chr(64 + $number); // 65 is 'A'
                
            case 'roman':
                return $this->toRoman($number);
                
            case 'roman_upper':
                return strtoupper($this->toRoman($number));
                
            case 'decimal':
                return $number . '.';
                
            case 'parenthetical':
                return '(' . $number . ')';
                
            case 'parenthetical_alpha':
                return '(' . chr(96 + $number) . ')';
                
            case 'numeric':
            default:
                return (string) $number;
        }
    }

    /**
     * Convert integer to Roman numeral.
     */
    protected function toRoman(int $number): string
    {
        $map = [
            1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
            100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'
        ];
        
        $result = '';
        foreach ($map as $value => $roman) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }
        
        return strtolower($result);
    }

    /**
     * Parse existing template content into clauses.
     */
    public function parseTemplateIntoCluses(ContractTemplate $template): array
    {
        $content = $template->template_content;
        
        // Define the clause structure based on the RECURRING SUPPORT SERVICES AGREEMENT
        $clauseDefinitions = $this->getRecurringServicesClauseDefinitions();
        
        $parsedClauses = [];
        
        foreach ($clauseDefinitions as $clauseDef) {
            $startPattern = $clauseDef['start_pattern'];
            $endPattern = $clauseDef['end_pattern'] ?? null;
            
            // Extract clause content using regex patterns
            $clauseContent = $this->extractClauseContent($content, $startPattern, $endPattern);
            
            if (!empty($clauseContent)) {
                $parsedClauses[] = [
                    'name' => $clauseDef['name'],
                    'slug' => $clauseDef['slug'],
                    'category' => $clauseDef['category'],
                    'clause_type' => $clauseDef['clause_type'],
                    'content' => $clauseContent,
                    'variables' => $this->extractVariablesFromContent($clauseContent),
                    'sort_order' => $clauseDef['sort_order'],
                    'metadata' => $clauseDef['metadata'] ?? null,
                ];
            }
        }
        
        return $parsedClauses;
    }

    /**
     * Extract clause content between patterns.
     */
    protected function extractClauseContent(string $content, string $startPattern, ?string $endPattern = null): string
    {
        if ($endPattern) {
            // Extract between start and end patterns
            $pattern = '/' . preg_quote($startPattern, '/') . '(.*?)' . preg_quote($endPattern, '/') . '/s';
            if (preg_match($pattern, $content, $matches)) {
                return trim($matches[1]);
            }
        } else {
            // Extract from start pattern to end of content or next major section
            $pos = strpos($content, $startPattern);
            if ($pos !== false) {
                $start = $pos + strlen($startPattern);
                return trim(substr($content, $start));
            }
        }
        
        return '';
    }

    /**
     * Extract variables from clause content.
     */
    protected function extractVariablesFromContent(string $content): array
    {
        $pattern = '/\{\{([^}#\/]+)\}\}/';
        preg_match_all($pattern, $content, $matches);
        
        $variables = [];
        foreach ($matches[1] ?? [] as $match) {
            $variable = trim($match);
            
            // Skip conditional directives
            if (strpos($variable, '#if') === 0 || strpos($variable, '/if') === 0) {
                continue;
            }
            
            // Extract base variable name from formatted variables
            if (strpos($variable, '|') !== false) {
                $variable = trim(explode('|', $variable)[0]);
            }
            
            $variables[] = $variable;
        }
        
        return array_unique($variables);
    }

    /**
     * Get clause definitions for the Recurring Support Services Agreement.
     */
    protected function getRecurringServicesClauseDefinitions(): array
    {
        return [
            [
                'name' => 'Contract Header',
                'slug' => 'contract-header',
                'category' => ContractClause::CATEGORY_HEADER,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'RECURRING SUPPORT SERVICES AGREEMENT',
                'end_pattern' => 'RECITALS:',
                'sort_order' => 10,
                'metadata' => ['section_numbering' => ['type' => 'none']],
            ],
            [
                'name' => 'Recitals',
                'slug' => 'recitals-msp',
                'category' => ContractClause::CATEGORY_HEADER,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'RECITALS:',
                'end_pattern' => 'DEFINITIONS:',
                'sort_order' => 20,
                'metadata' => ['section_numbering' => ['type' => 'none']],
            ],
            [
                'name' => 'Definitions',
                'slug' => 'definitions-comprehensive',
                'category' => ContractClause::CATEGORY_DEFINITIONS,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'DEFINITIONS:',
                'end_pattern' => 'SCOPE OF SUPPORT SERVICES:',
                'sort_order' => 30,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Scope of Support Services',
                'slug' => 'scope-support-services',
                'category' => ContractClause::CATEGORY_SERVICES,
                'clause_type' => ContractClause::TYPE_CONDITIONAL,
                'start_pattern' => 'SCOPE OF SUPPORT SERVICES:',
                'end_pattern' => 'CLIENT OBLIGATIONS AND RESPONSIBILITIES:',
                'sort_order' => 40,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Client Obligations and Responsibilities',
                'slug' => 'client-obligations',
                'category' => ContractClause::CATEGORY_OBLIGATIONS,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'CLIENT OBLIGATIONS AND RESPONSIBILITIES:',
                'end_pattern' => 'FEES AND PAYMENT TERMS:',
                'sort_order' => 50,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Fees and Payment Terms',
                'slug' => 'fees-payment-terms',
                'category' => ContractClause::CATEGORY_FINANCIAL,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'FEES AND PAYMENT TERMS:',
                'end_pattern' => 'TERM AND TERMINATION:',
                'sort_order' => 60,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Term and Termination',
                'slug' => 'term-termination',
                'category' => ContractClause::CATEGORY_LEGAL,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'TERM AND TERMINATION:',
                'end_pattern' => 'EXCLUSIONS FROM SUPPORT SERVICES:',
                'sort_order' => 70,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Exclusions from Support Services',
                'slug' => 'service-exclusions',
                'category' => ContractClause::CATEGORY_EXCLUSIONS,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'EXCLUSIONS FROM SUPPORT SERVICES:',
                'end_pattern' => 'WARRANTIES AND DISCLAIMERS:',
                'sort_order' => 80,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Warranties and Disclaimers',
                'slug' => 'warranties-disclaimers',
                'category' => ContractClause::CATEGORY_WARRANTIES,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'WARRANTIES AND DISCLAIMERS:',
                'end_pattern' => 'LIMITATION OF LIABILITY:',
                'sort_order' => 90,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Limitation of Liability',
                'slug' => 'liability-limitation',
                'category' => ContractClause::CATEGORY_WARRANTIES,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'LIMITATION OF LIABILITY:',
                'end_pattern' => 'CONFIDENTIALITY:',
                'sort_order' => 100,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Confidentiality',
                'slug' => 'confidentiality',
                'category' => ContractClause::CATEGORY_CONFIDENTIALITY,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'CONFIDENTIALITY:',
                'end_pattern' => 'GOVERNING LAW AND DISPUTE RESOLUTION:',
                'sort_order' => 110,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Governing Law and Dispute Resolution',
                'slug' => 'governing-law-disputes',
                'category' => ContractClause::CATEGORY_LEGAL,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'GOVERNING LAW AND DISPUTE RESOLUTION:',
                'end_pattern' => 'ENTIRE AGREEMENT:',
                'sort_order' => 120,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Administrative Clauses',
                'slug' => 'administrative-clauses',
                'category' => ContractClause::CATEGORY_ADMIN,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'ENTIRE AGREEMENT:',
                'end_pattern' => 'IN WITNESS WHEREOF',
                'sort_order' => 130,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric', 'prefix' => '']],
            ],
            [
                'name' => 'Signature Block',
                'slug' => 'signature-block',
                'category' => ContractClause::CATEGORY_SIGNATURE,
                'clause_type' => ContractClause::TYPE_REQUIRED,
                'start_pattern' => 'IN WITNESS WHEREOF',
                'end_pattern' => null, // Goes to end of document
                'sort_order' => 140,
                'metadata' => ['section_numbering' => ['type' => 'none']],
            ],
        ];
    }

    /**
     * Replace static definitions clauses with dynamic ones based on included clauses.
     */
    protected function replaceDynamicDefinitions(Collection $clauses, array $variables): Collection
    {
        // Find existing definitions clause
        $definitionsClause = $clauses->firstWhere('category', 'definitions');
        
        if (!$definitionsClause) {
            return $clauses; // No definitions clause to replace
        }
        
        // Analyze all clauses to determine required definitions
        $requiredDefinitions = $this->analyzeRequiredDefinitions($clauses);
        
        // Generate dynamic definitions content
        $dynamicContent = $this->generateDynamicDefinitionsContent($requiredDefinitions, $variables);
        
        // Create a clone of the definitions clause with dynamic content
        $dynamicDefinitionsClause = $definitionsClause->replicate();
        $dynamicDefinitionsClause->content = $dynamicContent;
        $dynamicDefinitionsClause->exists = true; // Preserve the clause as if it exists
        $dynamicDefinitionsClause->id = $definitionsClause->id; // Keep same ID for pivot relationship
        
        // Replace the original definitions clause with the dynamic one
        $updatedClauses = $clauses->map(function ($clause) use ($definitionsClause, $dynamicDefinitionsClause) {
            if ($clause->id === $definitionsClause->id) {
                return $dynamicDefinitionsClause;
            }
            return $clause;
        });
        
        return $updatedClauses;
    }

    /**
     * Analyze clauses to determine which definitions are required.
     */
    protected function analyzeRequiredDefinitions(Collection $clauses): array
    {
        $requiredDefinitions = [];
        
        // Collect all clause contents for analysis
        $clauseContents = $clauses->pluck('content')->toArray();
        
        // Use the definition registry to analyze requirements
        $requiredDefinitions = $this->definitionRegistry->analyzeRequiredDefinitions($clauseContents);
        
        // Also get definitions required by specific clause categories
        $categories = $clauses->pluck('category')->unique()->toArray();
        $categoryDefinitions = $this->definitionRegistry->getDefinitionsForCategories($categories);
        
        // Merge and deduplicate
        $requiredDefinitions = array_merge($requiredDefinitions, $categoryDefinitions);
        
        return $requiredDefinitions;
    }

    /**
     * Generate dynamic definitions content based on required definitions.
     */
    protected function generateDynamicDefinitionsContent(array $requiredDefinitions, array $variables): string
    {
        if (empty($requiredDefinitions)) {
            return "DEFINITIONS:\nAs used in this Agreement, the following terms shall have the meanings ascribed to them below:\n";
        }
        
        $content = "DEFINITIONS:\nAs used in this Agreement, the following terms shall have the meanings ascribed to them below:\n";
        
        // Sort definitions alphabetically by term
        uasort($requiredDefinitions, function ($a, $b) {
            return strcmp($a['term'], $b['term']);
        });
        
        foreach ($requiredDefinitions as $key => $definition) {
            $definitionContent = $this->definitionRegistry->generateDefinitionContent($key, $variables);
            if (!empty($definitionContent)) {
                $content .= $definitionContent . "\n";
            }
        }
        
        return $content;
    }
}