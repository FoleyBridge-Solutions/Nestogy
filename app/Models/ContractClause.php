<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * ContractClause Model
 * 
 * Represents reusable contract clauses that can be combined to build contracts.
 * Supports multi-tenancy, versioning, and conditional logic.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $slug
 * @property string $category
 * @property string $clause_type
 * @property string $content
 * @property array|null $variables
 * @property array|null $conditions
 * @property string|null $description
 * @property int $sort_order
 * @property string $status
 * @property string $version
 * @property bool $is_system
 * @property bool $is_required
 * @property array|null $applicable_contract_types
 * @property array|null $metadata
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class ContractClause extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_clauses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'category',
        'clause_type',
        'content',
        'variables',
        'conditions',
        'description',
        'sort_order',
        'status',
        'version',
        'is_system',
        'is_required',
        'applicable_contract_types',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'variables' => 'array',
        'conditions' => 'array',
        'applicable_contract_types' => 'array',
        'metadata' => 'array',
        'is_system' => 'boolean',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Clause status enumeration
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Clause type enumeration
     */
    const TYPE_REQUIRED = 'required';
    const TYPE_CONDITIONAL = 'conditional';
    const TYPE_OPTIONAL = 'optional';

    /**
     * Clause categories
     */
    const CATEGORY_DEFINITIONS = 'definitions';
    const CATEGORY_SERVICES = 'services';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_LEGAL = 'legal';
    const CATEGORY_ADMIN = 'admin';
    const CATEGORY_HEADER = 'header';
    const CATEGORY_SIGNATURE = 'signature';
    const CATEGORY_OBLIGATIONS = 'obligations';
    const CATEGORY_EXCLUSIONS = 'exclusions';
    const CATEGORY_WARRANTIES = 'warranties';
    const CATEGORY_CONFIDENTIALITY = 'confidentiality';
    const CATEGORY_COMPLIANCE = 'compliance';
    const CATEGORY_SLA = 'sla';
    const CATEGORY_DATA_PROTECTION = 'data_protection';
    const CATEGORY_INTELLECTUAL_PROPERTY = 'intellectual_property';
    const CATEGORY_CHANGE_MANAGEMENT = 'change_management';

    /**
     * Get the templates that use this clause.
     */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(ContractTemplate::class, 'contract_template_clauses', 'clause_id', 'template_id')
                    ->withPivot(['sort_order', 'is_required', 'conditions', 'variable_overrides', 'metadata'])
                    ->withTimestamps()
                    ->orderByPivot('sort_order');
    }

    /**
     * Get the user who created this clause.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this clause.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if clause is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if clause is conditional (requires logic to determine inclusion).
     */
    public function isConditional(): bool
    {
        return $this->clause_type === self::TYPE_CONDITIONAL;
    }

    /**
     * Check if clause is required for all contracts.
     */
    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Check if clause is system-created (read-only for users).
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Check if clause applies to a specific contract type.
     */
    public function appliesToContractType(string $contractType): bool
    {
        if (!$this->applicable_contract_types) {
            return true; // If no restrictions, applies to all
        }

        return in_array($contractType, $this->applicable_contract_types);
    }

    /**
     * Get clause dependencies - other clauses that must be present when this clause is used.
     */
    public function getDependencies(): array
    {
        $metadata = $this->metadata ?? [];
        return $metadata['dependencies'] ?? [];
    }

    /**
     * Get clause conflicts - other clauses that cannot be present when this clause is used.
     */
    public function getConflicts(): array
    {
        $metadata = $this->metadata ?? [];
        return $metadata['conflicts'] ?? [];
    }

    /**
     * Check if this clause has dependencies that need to be satisfied.
     */
    public function hasDependencies(): bool
    {
        return !empty($this->getDependencies());
    }

    /**
     * Check if this clause has conflicts that need to be avoided.
     */
    public function hasConflicts(): bool
    {
        return !empty($this->getConflicts());
    }

    /**
     * Get variables used in this clause.
     */
    public function getVariables(): array
    {
        return $this->variables ?? [];
    }

    /**
     * Get conditions for this clause.
     */
    public function getConditions(): array
    {
        return $this->conditions ?? [];
    }

    /**
     * Process clause content with variables and conditions.
     */
    public function processContent(array $variables, array $templateConditions = [], array $sectionNumbers = []): string
    {
        $content = $this->content;
        
        // Apply template-specific conditions if they exist
        $conditions = array_merge($this->getConditions(), $templateConditions);
        
        // Process handlebars conditional blocks (always run for any content that contains them)
        $content = $this->processConditionalBlocks($content, $variables);
        
        // Process dynamic section numbers
        $content = $this->processSectionNumbers($content, $sectionNumbers);
        
        // Process variable substitutions
        $content = $this->processVariables($content, $variables);
        
        return $content;
    }

    /**
     * Process handlebars-style conditionals in clause content.
     */
    protected function processConditionalBlocks(string $content, array $variables): string
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
     * Process variable substitutions in clause content with enhanced formatting.
     */
    protected function processVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Handle formatters like {{variable|upper}}
            $content = preg_replace_callback(
                '/\{\{' . preg_quote($key) . '(\|([^}]+))?\}\}/',
                function ($matches) use ($value) {
                    $formatter = $matches[2] ?? null;
                    return $this->applyFormatter($value, $formatter);
                },
                $content
            );
        }
        
        // Process list generation patterns (e.g., {{#list asset_types}}...{{/list}})
        $content = $this->processListBlocks($content, $variables);
        
        // Process conditional existence checks (e.g., {{#exists variable}})
        $content = $this->processExistenceBlocks($content, $variables);
        
        return $content;
    }

    /**
     * Process list generation blocks for arrays and comma-separated values.
     */
    protected function processListBlocks(string $content, array $variables): string
    {
        // Pattern to match {{#list variable}}template{{/list}} blocks
        $pattern = '/\{\{#list\s+([^}]+)\}\}(.*?)\{\{\/list\}\}/s';
        
        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variable = trim($matches[1]);
            $template = $matches[2];
            
            if (!isset($variables[$variable])) {
                return '';
            }
            
            $items = $this->getArrayFromVariable($variables[$variable]);
            
            if (empty($items)) {
                return '';
            }
            
            $output = '';
            foreach ($items as $index => $item) {
                $itemTemplate = $template;
                $itemTemplate = str_replace('{{item}}', $item, $itemTemplate);
                $itemTemplate = str_replace('{{index}}', $index + 1, $itemTemplate);
                $itemTemplate = str_replace('{{index0}}', $index, $itemTemplate);
                $output .= $itemTemplate;
            }
            
            return $output;
        }, $content);
    }

    /**
     * Process existence check blocks for conditional content based on variable presence.
     */
    protected function processExistenceBlocks(string $content, array $variables): string
    {
        // Pattern to match {{#exists variable}}content{{/exists}} blocks
        $pattern = '/\{\{#exists\s+([^}]+)\}\}(.*?)\{\{\/exists\}\}/s';
        
        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variable = trim($matches[1]);
            $conditionalContent = $matches[2];
            
            // Check if variable exists and has a non-empty value
            if (isset($variables[$variable]) && !empty($variables[$variable])) {
                return $conditionalContent;
            }
            
            return '';
        }, $content);
    }

    /**
     * Convert variable to array for list processing.
     */
    protected function getArrayFromVariable($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            // Handle comma-separated lists
            if (strpos($value, ',') !== false) {
                return array_map('trim', explode(',', $value));
            }
            
            // Handle "and" separated lists
            if (strpos($value, ' and ') !== false) {
                return array_map('trim', explode(' and ', $value));
            }
            
            // Single item
            return [$value];
        }
        
        return [];
    }

    /**
     * Process dynamic section numbering in clause content.
     */
    protected function processSectionNumbers(string $content, array $sectionNumbers): string
    {
        // Replace section number placeholders with actual numbers
        // New format: section_number, clause_number for subsection numbering
        
        if (isset($sectionNumbers['section_number']) && isset($sectionNumbers['clause_number'])) {
            // Format: "1.1", "1.2", etc. for clauses within sections
            $subsectionNumber = $sectionNumbers['section_number'] . '.' . $sectionNumbers['clause_number'];
            $content = str_replace('{{subsection_number}}', $subsectionNumber, $content);
        }
        
        // Legacy support for old placeholders
        foreach ($sectionNumbers as $placeholder => $number) {
            $content = str_replace("{{" . $placeholder . "}}", $number, $content);
        }
        
        return $content;
    }

    /**
     * Get section numbering metadata for this clause.
     */
    public function getSectionNumberingInfo(): array
    {
        $metadata = $this->metadata ?? [];
        return $metadata['section_numbering'] ?? [
            'type' => 'none', // none, numbered, lettered, subsection
            'level' => 1, // 1 = main section, 2 = subsection, 3 = sub-subsection
            'format' => 'numeric', // numeric, alpha, roman
            'prefix' => '', // "Section", "Subsection", etc.
        ];
    }

    /**
     * Check if this clause creates a new section.
     */
    public function createsSection(): bool
    {
        $numberingInfo = $this->getSectionNumberingInfo();
        return $numberingInfo['type'] !== 'none';
    }

    /**
     * Get the section level (1 = main, 2 = sub, 3 = sub-sub, etc.).
     */
    public function getSectionLevel(): int
    {
        $numberingInfo = $this->getSectionNumberingInfo();
        return $numberingInfo['level'] ?? 1;
    }

    /**
     * Apply formatting to a variable value.
     */
    protected function applyFormatter($value, ?string $formatter): string
    {
        if (!$formatter) {
            return (string) $value;
        }
        
        switch (strtolower($formatter)) {
            case 'upper':
                return strtoupper($value);
            case 'lower':
                return strtolower($value);
            case 'title':
                return ucwords($value);
            case 'capitalize':
                return ucfirst($value);
            case 'currency':
                return '$' . number_format((float) $value, 2);
            case 'number':
                return number_format((float) $value);
            case 'percent':
                return number_format((float) $value, 1) . '%';
            case 'date':
                return date('F j, Y', strtotime($value));
            case 'date_short':
                return date('m/d/Y', strtotime($value));
            case 'replace_underscore':
                return str_replace('_', ' ', $value);
            case 'replace_underscore_title':
                return ucwords(str_replace('_', ' ', $value));
            case 'list':
                // Format arrays or comma-separated values as a proper list
                if (is_array($value)) {
                    return $this->formatList($value);
                }
                if (is_string($value) && strpos($value, ',') !== false) {
                    return $this->formatList(array_map('trim', explode(',', $value)));
                }
                return (string) $value;
            case 'bullet_list':
                // Format as bullet list
                if (is_array($value)) {
                    return "- " . implode("\n- ", $value);
                }
                if (is_string($value) && strpos($value, ',') !== false) {
                    $items = array_map('trim', explode(',', $value));
                    return "- " . implode("\n- ", $items);
                }
                return "- " . $value;
            case 'hours':
                // Format numbers as hour descriptions
                $hours = (int) $value;
                if ($hours === 1) {
                    return '1 hour';
                }
                return $hours . ' hours';
            case 'days':
                // Format numbers as day descriptions
                $days = (int) $value;
                if ($days === 1) {
                    return '1 day';
                }
                return $days . ' days';
            case 'boolean_text':
                // Convert boolean to Yes/No text
                return $this->isTruthy($value) ? 'Yes' : 'No';
            case 'boolean_enabled':
                // Convert boolean to Enabled/Disabled text
                return $this->isTruthy($value) ? 'Enabled' : 'Disabled';
            default:
                return (string) $value;
        }
    }

    /**
     * Format an array as a grammatically correct list
     */
    protected function formatList(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        if (count($items) === 1) {
            return $items[0];
        }

        if (count($items) === 2) {
            return implode(' and ', $items);
        }

        $last = array_pop($items);
        return implode(', ', $items) . ', and ' . $last;
    }

    /**
     * Check if a value is truthy for conditional logic.
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
     * Extract variables from clause content.
     */
    public function extractVariables(): array
    {
        $pattern = '/\{\{([^}#\/]+)\}\}/';
        preg_match_all($pattern, $this->content, $matches);
        
        $variables = [];
        foreach ($matches[1] ?? [] as $match) {
            $variable = trim($match);
            
            // Extract base variable name from formatted variables like "variable|formatter"
            if (strpos($variable, '|') !== false) {
                $variable = trim(explode('|', $variable)[0]);
            }
            
            $variables[] = $variable;
        }
        
        return array_unique($variables);
    }

    /**
     * Extract defined terms that appear in this clause content.
     * Returns terms that might need definitions (capitalized terms, key phrases).
     */
    public function extractDefinedTerms(): array
    {
        $content = $this->content;
        $definedTerms = [];
        
        // Common contract terms that typically need definitions
        $commonTerms = [
            'Agreement', 'Business Hours', 'Confidential Information', 
            'Emergency Support', 'Force Majeure Event', 'Response Time',
            'Resolution Time', 'Service Levels', 'Service Tier',
            'Support Request', 'Support Services', 'Supported Infrastructure',
            'Term', 'Client', 'Service Provider'
        ];
        
        foreach ($commonTerms as $term) {
            // Look for the term used in a way that suggests it needs definition
            // (not just as part of other words)
            if (preg_match('/\b' . preg_quote($term, '/') . '\b/', $content)) {
                $definedTerms[] = strtolower(str_replace(' ', '_', $term));
            }
        }
        
        return array_unique($definedTerms);
    }

    /**
     * Get required definitions for this clause based on its category and content.
     */
    public function getRequiredDefinitions(): array
    {
        $requiredDefinitions = [];
        
        // Map categories to definition requirements
        $categoryMappings = [
            'scope_of_services' => ['agreement', 'support_services', 'supported_infrastructure'],
            'sla' => ['response_time', 'resolution_time', 'service_levels', 'service_tier', 'business_hours'],
            'legal' => ['agreement', 'term', 'force_majeure_event'],
            'confidentiality' => ['confidential_information'],
            'emergency_services' => ['emergency_support'],
            'definitions' => [], // Definitions clause doesn't require other definitions
        ];
        
        // Get requirements based on category
        if (isset($categoryMappings[$this->category])) {
            $requiredDefinitions = $categoryMappings[$this->category];
        }
        
        // Also analyze content for specific terms
        $contentTerms = $this->extractDefinedTerms();
        $requiredDefinitions = array_merge($requiredDefinitions, $contentTerms);
        
        return array_unique($requiredDefinitions);
    }

    /**
     * Check if this clause defines terms (i.e., is a definitions clause).
     */
    public function definesTerms(): bool
    {
        return $this->category === 'definitions';
    }

    /**
     * Get the definition metadata for this clause.
     */
    public function getDefinitionMetadata(): array
    {
        $metadata = $this->metadata ?? [];
        return $metadata['definitions'] ?? [
            'defines_terms' => $this->definesTerms(),
            'required_definitions' => $this->getRequiredDefinitions(),
            'extracted_terms' => $this->extractDefinedTerms(),
        ];
    }

    /**
     * Create new version of this clause.
     */
    public function createVersion(array $changes = []): ContractClause
    {
        $newClause = $this->replicate();
        $newClause->version = $this->getNextVersion();
        $newClause->slug = $this->slug . '-v' . str_replace('.', '-', $newClause->version);
        $newClause->created_by = auth()->id();
        
        // Apply changes
        foreach ($changes as $key => $value) {
            $newClause->$key = $value;
        }
        
        $newClause->save();
        
        return $newClause;
    }

    /**
     * Get next version number.
     */
    protected function getNextVersion(): string
    {
        $versionParts = explode('.', $this->version);
        $majorVersion = (int) $versionParts[0];
        $minorVersion = isset($versionParts[1]) ? (int) $versionParts[1] : 0;
        
        return $majorVersion . '.' . ($minorVersion + 1);
    }

    /**
     * Get available clause categories.
     */
    public static function getAvailableCategories(): array
    {
        return [
            self::CATEGORY_HEADER => 'Header & Preamble',
            self::CATEGORY_DEFINITIONS => 'Definitions',
            self::CATEGORY_SERVICES => 'Services & Scope',
            self::CATEGORY_OBLIGATIONS => 'Client Obligations',
            self::CATEGORY_FINANCIAL => 'Financial Terms',
            self::CATEGORY_SLA => 'Service Level Agreements',
            self::CATEGORY_EXCLUSIONS => 'Service Exclusions',
            self::CATEGORY_WARRANTIES => 'Warranties & Liability',
            self::CATEGORY_CONFIDENTIALITY => 'Confidentiality',
            self::CATEGORY_DATA_PROTECTION => 'Data Protection & Privacy',
            self::CATEGORY_INTELLECTUAL_PROPERTY => 'Intellectual Property',
            self::CATEGORY_COMPLIANCE => 'Compliance & Regulatory',
            self::CATEGORY_CHANGE_MANAGEMENT => 'Change Management',
            self::CATEGORY_LEGAL => 'Legal Framework',
            self::CATEGORY_ADMIN => 'Administrative',
            self::CATEGORY_SIGNATURE => 'Signature Block',
        ];
    }

    /**
     * Get available clause types.
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_REQUIRED => 'Required (Always Included)',
            self::TYPE_CONDITIONAL => 'Conditional (Based on Logic)',
            self::TYPE_OPTIONAL => 'Optional (User Choice)',
        ];
    }

    /**
     * Scope to get active clauses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get clauses by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get clauses by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('clause_type', $type);
    }

    /**
     * Scope to get system clauses.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get user-created clauses.
     */
    public function scopeUserCreated($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope to get clauses applicable to contract type.
     */
    public function scopeForContractType($query, string $contractType)
    {
        return $query->where(function ($q) use ($contractType) {
            $q->whereNull('applicable_contract_types')
              ->orWhereJsonContains('applicable_contract_types', $contractType);
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Generate slug if not provided
        static::creating(function ($clause) {
            if (!$clause->slug) {
                $clause->slug = Str::slug($clause->name);
            }
            
            if (!$clause->created_by) {
                $clause->created_by = auth()->id();
            }
        });
        
        // Update slug when name changes
        static::updating(function ($clause) {
            if ($clause->isDirty('name') && !$clause->isDirty('slug')) {
                $clause->slug = Str::slug($clause->name);
            }
            
            $clause->updated_by = auth()->id();
        });
    }
}