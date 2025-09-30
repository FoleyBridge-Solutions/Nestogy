<?php

namespace App\Domains\Contract\Models;

use App\Domains\Contract\Services\ContractConfigurationRegistry;
use App\Domains\Contract\Traits\HasBillingCalculations;
use App\Domains\Contract\Traits\HasCompanyConfiguration;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property string $billing_model
 * @property array|null $asset_billing_rules
 * @property array|null $supported_asset_types
 * @property array|null $asset_service_matrix
 * @property float|null $default_per_asset_rate
 * @property array|null $contact_billing_rules
 * @property array|null $contact_access_tiers
 * @property float|null $default_per_contact_rate
 * @property array|null $calculation_formulas
 * @property array|null $auto_assignment_rules
 * @property array|null $billing_triggers
 * @property array|null $workflow_automation
 * @property array|null $notification_triggers
 * @property array|null $integration_hooks
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
    use BelongsToCompany, HasBillingCalculations, HasCompanyConfiguration, HasFactory, SoftDeletes;

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
        'billing_model',
        'asset_billing_rules',
        'supported_asset_types',
        'asset_service_matrix',
        'default_per_asset_rate',
        'contact_billing_rules',
        'contact_access_tiers',
        'default_per_contact_rate',
        'calculation_formulas',
        'auto_assignment_rules',
        'billing_triggers',
        'workflow_automation',
        'notification_triggers',
        'integration_hooks',
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
        'asset_billing_rules' => 'array',
        'supported_asset_types' => 'array',
        'asset_service_matrix' => 'array',
        'default_per_asset_rate' => 'decimal:2',
        'contact_billing_rules' => 'array',
        'contact_access_tiers' => 'array',
        'default_per_contact_rate' => 'decimal:2',
        'calculation_formulas' => 'array',
        'auto_assignment_rules' => 'array',
        'billing_triggers' => 'array',
        'workflow_automation' => 'array',
        'notification_triggers' => 'array',
        'integration_hooks' => 'array',
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
     * Get the company's contract configuration registry
     */
    protected function getCompanyConfigRegistry(): ContractConfigurationRegistry
    {
        return app(ContractConfigurationRegistry::class, ['companyId' => $this->company_id]);
    }

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
        $statuses = $this->getCompanyConfigRegistry()->getTemplateStatuses();
        $activeStatus = $statuses['active'] ?? 'active';

        return $this->status === $activeStatus;
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
        if (! $this->next_review_date) {
            return false;
        }

        return Carbon::now()->gte($this->next_review_date);
    }

    /**
     * Check if template is programmable (has automation features).
     */
    public function getIsProgrammableAttribute(): bool
    {
        $billingModels = $this->getCompanyConfigRegistry()->getBillingModels();
        $fixedBillingKey = array_search('Fixed', $billingModels) ?: 'fixed';

        return ! is_null($this->calculation_formulas) ||
               ! is_null($this->auto_assignment_rules) ||
               ! is_null($this->billing_triggers) ||
               ! is_null($this->workflow_automation) ||
               ! empty($this->billing_model) && $this->billing_model !== $fixedBillingKey;
    }

    // Billing support methods moved to HasBillingCalculations trait

    /**
     * Get supported asset types.
     */
    public function getSupportedAssetTypes(): array
    {
        return $this->supported_asset_types ?? [];
    }

    /**
     * Check if template supports specific asset type.
     */
    public function supportsAssetType(string $assetType): bool
    {
        return in_array($assetType, $this->getSupportedAssetTypes());
    }

    /**
     * Get contact access tiers.
     */
    public function getContactAccessTiers(): array
    {
        return $this->contact_access_tiers ?? [];
    }

    // Billing rate methods moved to HasBillingCalculations trait

    /**
     * Get services assigned to asset type.
     */
    public function getAssetServices(string $assetType): array
    {
        $matrix = $this->asset_service_matrix ?? [];

        return $matrix[$assetType] ?? [];
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
        $statuses = $this->getCompanyConfigRegistry()->getTemplateStatuses();
        $draftStatus = $statuses['draft'] ?? 'draft';

        $newTemplate = $this->replicate();
        $newTemplate->parent_template_id = $this->id;
        $newTemplate->version = $this->getNextVersion();
        $newTemplate->status = $draftStatus;
        $newTemplate->slug = $this->slug.'-v'.$newTemplate->version;
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

        return $majorVersion.'.'.($minorVersion + 1);
    }

    /**
     * Extract variables from clauses associated with this template.
     */
    public function extractVariables(): array
    {
        $variables = [];

        foreach ($this->clauses as $clause) {
            $clauseVariables = $clause->extractVariables();
            $variables = array_merge($variables, $clauseVariables);
        }

        return array_unique($variables);
    }

    /**
     * Synchronize variable_fields from attached clauses.
     * This method extracts variables from all attached clauses and updates
     * the template's variable_fields column with the aggregated data.
     */
    public function syncVariablesFromClauses(): void
    {
        // Extract unique variables from all attached clauses
        $extractedVariables = $this->extractVariables();

        // Convert to the expected format for variable_fields
        $variableFields = [];
        foreach ($extractedVariables as $variable) {
            $variableFields[] = [
                'name' => $variable,
                'type' => 'text', // Default type
                'label' => ucfirst(str_replace('_', ' ', $variable)),
                'required' => false, // Default to optional
                'default_value' => '',
                'description' => 'Auto-extracted from clause content',
                'placeholder' => "Enter {$variable}...",
            ];
        }

        // Update the template with the new variable fields
        $this->update(['variable_fields' => $variableFields]);

        // Log the synchronization for debugging
        \Log::info("Synchronized variables for template {$this->name}: ".count($variableFields).' variables found', [
            'template_id' => $this->id,
            'variables' => array_column($variableFields, 'name'),
        ]);
    }

    /**
     * Validate template clause structure.
     */
    public function validateContent(): array
    {
        $errors = [];

        // Check if template has any clauses
        if ($this->clauses()->count() === 0) {
            $errors['no_clauses'] = 'Template must have at least one clause';
        }

        // Check for required clause categories
        $clauseService = app(\App\Domains\Contract\Services\ContractClauseService::class);
        $missingCategories = $clauseService->getMissingRequiredClauses($this);

        if (! empty($missingCategories)) {
            $errors['missing_categories'] = $missingCategories;
        }

        // Validate clause dependencies
        $dependencyErrors = $clauseService->validateClauseDependencies($this);
        if (! empty($dependencyErrors)) {
            $errors['dependency_errors'] = $dependencyErrors;
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
     * Calculate billing for given usage data.
     */
    public function calculateBilling(array $usageData): array
    {
        $calculation = [
            'base_amount' => 0,
            'asset_charges' => 0,
            'contact_charges' => 0,
            'usage_charges' => 0,
            'total_amount' => 0,
            'breakdown' => [],
        ];

        // Apply calculation formulas if present
        if ($this->calculation_formulas) {
            $calculation = $this->applyCalculationFormulas($usageData, $calculation);
        }

        // Calculate asset-based charges
        if ($this->supportsAssetBilling() && isset($usageData['assets'])) {
            $calculation['asset_charges'] = $this->calculateAssetCharges($usageData['assets']);
        }

        // Calculate contact-based charges
        if ($this->supportsContactBilling() && isset($usageData['contacts'])) {
            $calculation['contact_charges'] = $this->calculateContactCharges($usageData['contacts']);
        }

        // Calculate total
        $calculation['total_amount'] = $calculation['base_amount'] +
                                     $calculation['asset_charges'] +
                                     $calculation['contact_charges'] +
                                     $calculation['usage_charges'];

        return $calculation;
    }

    // Charge calculation methods moved to HasBillingCalculations trait

    /**
     * Apply calculation formulas.
     */
    protected function applyCalculationFormulas(array $usageData, array $calculation): array
    {
        // This would implement the formula engine
        // For now, just return the calculation as-is
        return $calculation;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope to get active templates.
     */
    public function scopeActive($query)
    {
        // For scopes, we'll use a default since we don't have a model instance
        // When actually called, it will use the first company's config or fallback
        return $query->where('status', 'active');
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
     * Scope for templates by billing model.
     */
    public function scopeByBillingModel($query, string $billingModel)
    {
        return $query->where('billing_model', $billingModel);
    }

    /**
     * Scope for programmable templates.
     */
    public function scopeProgrammable($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('calculation_formulas')
                ->orWhereNotNull('auto_assignment_rules')
                ->orWhereNotNull('billing_triggers')
                ->orWhereNotNull('workflow_automation')
                ->orWhere('billing_model', '!=', 'fixed'); // Use default fallback
        });
    }

    /**
     * Get validation rules for template creation.
     */
    public static function getValidationRules(?int $companyId = null): array
    {
        $instance = new static;
        $templateTypes = array_keys($instance->getAvailableContractTypes($companyId));
        $statuses = array_keys($instance->getConfigValue('template_statuses', [], $companyId));

        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:contract_templates,slug',
            'template_type' => 'required|in:'.implode(',', $templateTypes ?: ['default']),
            'template_content' => 'required|string',
            'status' => 'required|in:'.implode(',', $statuses ?: ['active']),
            'version' => 'required|string|max:20',
            'variable_fields' => 'nullable|array',
            'default_values' => 'nullable|array',
            'required_fields' => 'nullable|array',
        ];
    }

    /**
     * Get available template types for a company.
     */
    public static function getAvailableTypes(?int $companyId = null): array
    {
        $instance = new static;

        return $instance->getConfigValue('contract_types', [], $companyId);
    }

    /**
     * Get available template categories for a company.
     */
    public static function getAvailableCategories(?int $companyId = null): array
    {
        $instance = new static;

        return $instance->getConfigValue('template_categories', [], $companyId);
    }

    /**
     * Get available billing models for a company.
     */
    public static function getAvailableBillingModels(?int $companyId = null): array
    {
        $instance = new static;

        return $instance->getConfigValue('billing_models', [], $companyId);
    }

    /**
     * Get available template statuses for a company.
     */
    public static function getAvailableStatuses(?int $companyId = null): array
    {
        $instance = new static;

        return $instance->getConfigValue('template_statuses', [], $companyId);
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
            if (! $template->slug) {
                $template->slug = \Str::slug($template->name);
            }

            if (! $template->version) {
                $template->version = '1.0';
            }

            if (! $template->created_by) {
                $template->created_by = auth()->id();
            }
        });

        // Update slug when name changes
        static::updating(function ($template) {
            if ($template->isDirty('name') && ! $template->isDirty('slug')) {
                $template->slug = \Str::slug($template->name);
            }

            $template->updated_by = auth()->id();
        });
    }
}
