<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * ContractTemplate Model
 * 
 * Template management for contract generation with VoIP-specific features,
 * variable field support, and compliance templates.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $template_type
 * @property string|null $category
 * @property array|null $tags
 * @property string $status
 * @property string $version
 * @property int|null $parent_template_id
 * @property bool $is_default
 * @property string $template_content
 * @property array|null $variable_fields
 * @property array|null $default_values
 * @property array|null $required_fields
 * @property array|null $voip_service_types
 * @property array|null $default_sla_terms
 * @property array|null $default_pricing_structure
 * @property array|null $compliance_templates
 * @property array|null $jurisdictions
 * @property array|null $regulatory_requirements
 * @property string|null $legal_disclaimers
 * @property array|null $customization_options
 * @property array|null $conditional_clauses
 * @property array|null $pricing_models
 * @property int $usage_count
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property float|null $success_rate
 * @property bool $requires_approval
 * @property array|null $approval_workflow
 * @property \Illuminate\Support\Carbon|null $last_reviewed_at
 * @property \Illuminate\Support\Carbon|null $next_review_date
 * @property array|null $metadata
 * @property array|null $rendering_options
 * @property array|null $signature_settings
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class ContractTemplate extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'template_type',
        'category',
        'tags',
        'status',
        'version',
        'parent_template_id',
        'is_default',
        'template_content',
        'variable_fields',
        'default_values',
        'required_fields',
        'voip_service_types',
        'default_sla_terms',
        'default_pricing_structure',
        'compliance_templates',
        'jurisdictions',
        'regulatory_requirements',
        'legal_disclaimers',
        'customization_options',
        'conditional_clauses',
        'pricing_models',
        'usage_count',
        'last_used_at',
        'success_rate',
        'requires_approval',
        'approval_workflow',
        'last_reviewed_at',
        'next_review_date',
        'metadata',
        'rendering_options',
        'signature_settings',
        'created_by',
        'updated_by',
        'approved_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'parent_template_id' => 'integer',
        'is_default' => 'boolean',
        'tags' => 'array',
        'variable_fields' => 'array',
        'default_values' => 'array',
        'required_fields' => 'array',
        'voip_service_types' => 'array',
        'default_sla_terms' => 'array',
        'default_pricing_structure' => 'array',
        'compliance_templates' => 'array',
        'jurisdictions' => 'array',
        'regulatory_requirements' => 'array',
        'customization_options' => 'array',
        'conditional_clauses' => 'array',
        'pricing_models' => 'array',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'success_rate' => 'decimal:2',
        'requires_approval' => 'boolean',
        'approval_workflow' => 'array',
        'last_reviewed_at' => 'datetime',
        'next_review_date' => 'date',
        'metadata' => 'array',
        'rendering_options' => 'array',
        'signature_settings' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'approved_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Template status enumeration
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Template types (matching contract types)
     */
    const TYPE_SERVICE_AGREEMENT = 'service_agreement';
    const TYPE_EQUIPMENT_LEASE = 'equipment_lease';
    const TYPE_INSTALLATION_CONTRACT = 'installation_contract';
    const TYPE_MAINTENANCE_AGREEMENT = 'maintenance_agreement';
    const TYPE_SLA_CONTRACT = 'sla_contract';
    const TYPE_INTERNATIONAL_SERVICE = 'international_service';
    const TYPE_MASTER_SERVICE = 'master_service';
    const TYPE_DATA_PROCESSING = 'data_processing';
    const TYPE_PROFESSIONAL_SERVICES = 'professional_services';
    const TYPE_SUPPORT_CONTRACT = 'support_contract';
    
    // MSP Specific Templates
    const TYPE_MANAGED_SERVICES = 'managed_services';
    const TYPE_CYBERSECURITY_SERVICES = 'cybersecurity_services';
    const TYPE_BACKUP_DR = 'backup_dr';
    const TYPE_CLOUD_MIGRATION = 'cloud_migration';
    const TYPE_M365_MANAGEMENT = 'm365_management';
    const TYPE_BREAK_FIX = 'break_fix';
    const TYPE_ENTERPRISE_MANAGED = 'enterprise_managed';
    
    // VoIP Carrier Templates
    const TYPE_HOSTED_PBX = 'hosted_pbx';
    const TYPE_SIP_TRUNKING = 'sip_trunking';
    const TYPE_UNIFIED_COMMUNICATIONS = 'unified_communications';
    const TYPE_INTERNATIONAL_CALLING = 'international_calling';
    const TYPE_CONTACT_CENTER = 'contact_center';
    const TYPE_E911_SERVICES = 'e911_services';
    const TYPE_NUMBER_PORTING = 'number_porting';
    
    // IT VAR Templates
    const TYPE_HARDWARE_PROCUREMENT = 'hardware_procurement';
    const TYPE_SOFTWARE_LICENSING = 'software_licensing';
    const TYPE_VENDOR_PARTNER = 'vendor_partner';
    const TYPE_SOLUTION_INTEGRATION = 'solution_integration';
    
    // Cross-Industry Templates
    const TYPE_BUSINESS_ASSOCIATE = 'business_associate';
    const TYPE_CONSUMPTION_BASED = 'consumption_based';
    const TYPE_MDR_SERVICES = 'mdr_services';
    
    /**
     * Template categories
     */
    const CATEGORY_MSP = 'msp';
    const CATEGORY_VOIP = 'voip';
    const CATEGORY_VAR = 'var';
    const CATEGORY_COMPLIANCE = 'compliance';
    const CATEGORY_GENERAL = 'general';
    
    /**
     * Billing model enumeration
     */
    const BILLING_FIXED = 'fixed';
    const BILLING_PER_ASSET = 'per_asset';
    const BILLING_PER_USER = 'per_user';
    const BILLING_PER_EXTENSION = 'per_extension';
    const BILLING_HOURLY = 'hourly';
    const BILLING_TIERED = 'tiered';
    const BILLING_HYBRID = 'hybrid';
    const BILLING_CONSUMPTION = 'consumption';
    const BILLING_PROJECT = 'project';
    const BILLING_CONCURRENT_CALLS = 'concurrent_calls';

    /**
     * Get the parent template.
     */
    public function parentTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'parent_template_id');
    }

    /**
     * Get child template versions.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ContractTemplate::class, 'parent_template_id');
    }

    /**
     * Get contracts created from this template.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'template_id');
    }

    /**
     * Get the clauses associated with this template.
     */
    public function clauses(): BelongsToMany
    {
        return $this->belongsToMany(ContractClause::class, 'contract_template_clauses', 'template_id', 'clause_id')
                    ->withPivot(['sort_order', 'is_required', 'conditions', 'variable_overrides', 'metadata'])
                    ->withTimestamps()
                    ->orderByPivot('sort_order');
    }

    /**
     * Get the user who created this template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this template.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved this template.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if template is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if template is default for its type.
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Check if template needs review.
     */
    public function needsReview(): bool
    {
        if (!$this->next_review_date) {
            return false;
        }

        return Carbon::now()->gte($this->next_review_date);
    }

    /**
     * Get template variable fields with descriptions.
     */
    public function getVariableFields(): array
    {
        return $this->variable_fields ?? [];
    }

    /**
     * Get required fields that must be filled.
     */
    public function getRequiredFields(): array
    {
        return $this->required_fields ?? [];
    }

    /**
     * Get default values for variables.
     */
    public function getDefaultValues(): array
    {
        return $this->default_values ?? [];
    }

    /**
     * Create contract from this template.
     */
    public function createContract(Client $client, array $customizations = []): Contract
    {
        $contractData = array_merge([
            'company_id' => $this->company_id,
            'client_id' => $client->id,
            'template_id' => $this->id,
            'template_type' => $this->template_type,
            'contract_type' => $this->template_type,
            'title' => $customizations['title'] ?? $this->name,
            'description' => $customizations['description'] ?? $this->description,
            'created_by' => auth()->id(),
        ], $this->getDefaultValues(), $customizations);

        $contract = Contract::create($contractData);

        // Increment usage count
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);

        return $contract;
    }

    /**
     * Create new version of this template.
     */
    public function createVersion(array $changes = []): ContractTemplate
    {
        $newTemplate = $this->replicate();
        $newTemplate->parent_template_id = $this->id;
        $newTemplate->version = $this->getNextVersion();
        $newTemplate->status = self::STATUS_DRAFT;
        $newTemplate->slug = $this->slug . '-v' . $newTemplate->version;
        $newTemplate->is_default = false;
        $newTemplate->usage_count = 0;
        $newTemplate->last_used_at = null;
        $newTemplate->created_by = auth()->id();

        // Apply changes
        foreach ($changes as $key => $value) {
            $newTemplate->$key = $value;
        }

        $newTemplate->save();

        return $newTemplate;
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
     * Process template content with variables and advanced features.
     */
    public function processContent(array $variables): string
    {
        $content = $this->template_content;
        
        // Step 1: Process conditional blocks
        $content = $this->processConditionalBlocks($content, $variables);
        
        // Step 2: Process simple variable replacements with formatting
        $content = $this->processSimpleVariables($content, $variables);
        
        // Step 3: Clean up any remaining template syntax
        $content = $this->cleanupTemplateSyntax($content);
        
        return $content;
    }

    /**
     * Process conditional blocks like {{#if variable}}...{{/if}}
     */
    private function processConditionalBlocks(string $content, array $variables): string
    {
        // Pattern to match {{#if variable}}...{{/if}} blocks
        $pattern = '/\{\{#if\s+([^}]+)\}\}(.*?)\{\{\/if\}\}/s';
        
        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variable = trim($matches[1]);
            $blockContent = $matches[2];
            
            // Check if the variable exists and is truthy
            $shouldInclude = isset($variables[$variable]) && $this->isTruthy($variables[$variable]);
            
            return $shouldInclude ? $blockContent : '';
        }, $content);
    }

    /**
     * Process simple variable replacements with optional formatting
     */
    private function processSimpleVariables(string $content, array $variables): string
    {
        // Pattern to match {{variable}} or {{variable|formatter}}
        $pattern = '/\{\{([^}|]+)(\|([^}]+))?\}\}/';
        
        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variable = trim($matches[1]);
            $formatter = isset($matches[3]) ? trim($matches[3]) : null;
            
            if (!isset($variables[$variable])) {
                return $matches[0]; // Return original if variable not found
            }
            
            $value = $variables[$variable];
            
            // Apply formatting if specified
            if ($formatter) {
                $value = $this->applyFormatter($value, $formatter);
            }
            
            return $value;
        }, $content);
    }

    /**
     * Apply formatting to a variable value
     */
    private function applyFormatter($value, string $formatter): string
    {
        switch (strtolower($formatter)) {
            case 'upper':
                return strtoupper($value);
            case 'lower':
                return strtolower($value);
            case 'title':
                return ucwords($value);
            case 'currency':
                return '$' . number_format((float) $value, 2);
            case 'date':
                if ($value instanceof \DateTime) {
                    return $value->format('F j, Y');
                }
                if (is_string($value)) {
                    try {
                        $date = new \DateTime($value);
                        return $date->format('F j, Y');
                    } catch (\Exception $e) {
                        return $value;
                    }
                }
                return $value;
            case 'short_date':
                if ($value instanceof \DateTime) {
                    return $value->format('m/d/Y');
                }
                if (is_string($value)) {
                    try {
                        $date = new \DateTime($value);
                        return $date->format('m/d/Y');
                    } catch (\Exception $e) {
                        return $value;
                    }
                }
                return $value;
            default:
                return $value;
        }
    }

    /**
     * Check if a value is truthy for conditional logic
     */
    private function isTruthy($value): bool
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
     * Clean up any remaining template syntax
     */
    private function cleanupTemplateSyntax(string $content): string
    {
        // Remove any remaining unprocessed template tags
        $content = preg_replace('/\{\{[^}]*\}\}/', '', $content);
        
        // Clean up extra whitespace that might be left from removed blocks
        $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
        
        return trim($content);
    }

    /**
     * Extract variables from template content.
     */
    public function extractVariables(): array
    {
        $pattern = '/\{\{([^}]+)\}\}/';
        preg_match_all($pattern, $this->template_content, $matches);
        
        $variables = [];
        foreach ($matches[1] ?? [] as $match) {
            $variable = trim($match);
            
            // Skip conditional directives like #if, /if
            if (strpos($variable, '#if') === 0 || strpos($variable, '/if') === 0) {
                continue;
            }
            
            // Extract base variable name from formatted variables like "variable|formatter"
            if (strpos($variable, '|') !== false) {
                $variable = trim(explode('|', $variable)[0]);
            }
            
            $variables[] = $variable;
        }
        
        return array_unique($variables);
    }

    /**
     * Validate template content.
     */
    public function validateContent(): array
    {
        $errors = [];
        $extractedVariables = $this->extractVariables();
        $definedVariables = array_keys($this->getVariableFields());

        // Check for undefined variables
        $undefinedVariables = array_diff($extractedVariables, $definedVariables);
        if (!empty($undefinedVariables)) {
            $errors['undefined_variables'] = $undefinedVariables;
        }

        // Check for unused variable definitions
        $unusedVariables = array_diff($definedVariables, $extractedVariables);
        if (!empty($unusedVariables)) {
            $errors['unused_variables'] = $unusedVariables;
        }

        return $errors;
    }

    /**
     * Get template statistics.
     */
    public function getStatistics(): array
    {
        $contractsCreated = $this->contracts()->count();
        $activeContracts = $this->contracts()->where('status', Contract::STATUS_ACTIVE)->count();
        
        return [
            'usage_count' => $this->usage_count,
            'contracts_created' => $contractsCreated,
            'active_contracts' => $activeContracts,
            'success_rate' => $this->success_rate,
            'last_used' => $this->last_used_at?->diffForHumans(),
            'variables_count' => count($this->getVariableFields()),
            'content_length' => strlen($this->template_content),
        ];
    }

    /**
     * Scope to get active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get default templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get templates by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Scope to get templates needing review.
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('next_review_date', '<=', now());
    }

    /**
     * Scope to search templates.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }

    /**
     * Get validation rules for template creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:contract_templates,slug',
            'template_type' => 'required|in:' . implode(',', array_keys(self::getAvailableTypes())),
            'template_content' => 'required|string',
            'status' => 'required|in:' . implode(',', [
                self::STATUS_DRAFT,
                self::STATUS_ACTIVE,
                self::STATUS_ARCHIVED,
            ]),
            'version' => 'required|string|max:20',
            'variable_fields' => 'nullable|array',
            'default_values' => 'nullable|array',
            'required_fields' => 'nullable|array',
        ];
    }

    /**
     * Get available template types.
     */
    public static function getAvailableTypes(): array
    {
        return [
            // General Templates
            self::TYPE_SERVICE_AGREEMENT => 'VoIP Service Agreement',
            self::TYPE_EQUIPMENT_LEASE => 'Equipment Lease Agreement',
            self::TYPE_INSTALLATION_CONTRACT => 'Installation Services Contract',
            self::TYPE_MAINTENANCE_AGREEMENT => 'Maintenance Agreement',
            self::TYPE_SLA_CONTRACT => 'Service Level Agreement',
            self::TYPE_INTERNATIONAL_SERVICE => 'International Services Agreement',
            self::TYPE_MASTER_SERVICE => 'Master Service Agreement',
            self::TYPE_DATA_PROCESSING => 'Data Processing Agreement',
            self::TYPE_PROFESSIONAL_SERVICES => 'Professional Services Agreement',
            self::TYPE_SUPPORT_CONTRACT => 'Support Contract',
            
            // MSP Templates
            self::TYPE_MANAGED_SERVICES => 'Comprehensive Managed Services',
            self::TYPE_CYBERSECURITY_SERVICES => 'Cybersecurity Services',
            self::TYPE_BACKUP_DR => 'Backup and Disaster Recovery',
            self::TYPE_CLOUD_MIGRATION => 'Cloud Migration Services',
            self::TYPE_M365_MANAGEMENT => 'Microsoft 365 Management',
            self::TYPE_BREAK_FIX => 'Break-Fix Services',
            self::TYPE_ENTERPRISE_MANAGED => 'Enterprise Managed Services',
            
            // VoIP Templates
            self::TYPE_HOSTED_PBX => 'Hosted PBX Services',
            self::TYPE_SIP_TRUNKING => 'SIP Trunking Services',
            self::TYPE_UNIFIED_COMMUNICATIONS => 'Unified Communications',
            self::TYPE_INTERNATIONAL_CALLING => 'International Calling Services',
            self::TYPE_CONTACT_CENTER => 'Contact Center Solutions',
            self::TYPE_E911_SERVICES => 'E911 Emergency Services',
            self::TYPE_NUMBER_PORTING => 'Number Porting Services',
            
            // IT VAR Templates
            self::TYPE_HARDWARE_PROCUREMENT => 'Hardware Procurement & Installation',
            self::TYPE_SOFTWARE_LICENSING => 'Software Licensing Agreement',
            self::TYPE_VENDOR_PARTNER => 'Vendor Partner Agreement',
            self::TYPE_SOLUTION_INTEGRATION => 'Solution Integration Services',
            
            // Cross-Industry Templates
            self::TYPE_BUSINESS_ASSOCIATE => 'Business Associate Agreement (HIPAA)',
            self::TYPE_CONSUMPTION_BASED => 'Consumption-Based Services',
            self::TYPE_MDR_SERVICES => 'Managed Detection & Response',
        ];
    }

    /**
     * Get available template categories.
     */
    public static function getAvailableCategories(): array
    {
        return [
            self::CATEGORY_MSP => 'Managed Service Provider',
            self::CATEGORY_VOIP => 'VoIP Carrier',
            self::CATEGORY_VAR => 'IT Value-Added Reseller',
            self::CATEGORY_COMPLIANCE => 'Compliance & Legal',
            self::CATEGORY_GENERAL => 'General Purpose',
        ];
    }

    /**
     * Get available billing models.
     */
    public static function getAvailableBillingModels(): array
    {
        return [
            self::BILLING_FIXED => 'Fixed Price',
            self::BILLING_PER_ASSET => 'Per Asset/Device',
            self::BILLING_PER_USER => 'Per User/Seat',
            self::BILLING_PER_EXTENSION => 'Per Extension',
            self::BILLING_HOURLY => 'Hourly Rate',
            self::BILLING_TIERED => 'Tiered Pricing',
            self::BILLING_HYBRID => 'Hybrid Model',
            self::BILLING_CONSUMPTION => 'Consumption-Based',
            self::BILLING_PROJECT => 'Project-Based',
            self::BILLING_CONCURRENT_CALLS => 'Concurrent Calls',
        ];
    }

    /**
     * Get templates by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate slug if not provided
        static::creating(function ($template) {
            if (!$template->slug) {
                $template->slug = \Str::slug($template->name);
            }

            if (!$template->version) {
                $template->version = '1.0';
            }

            if (!$template->created_by) {
                $template->created_by = auth()->id();
            }
        });

        // Update slug when name changes
        static::updating(function ($template) {
            if ($template->isDirty('name') && !$template->isDirty('slug')) {
                $template->slug = \Str::slug($template->name);
            }

            $template->updated_by = auth()->id();
        });
    }
}