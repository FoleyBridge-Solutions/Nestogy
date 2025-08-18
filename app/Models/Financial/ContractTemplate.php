<?php

namespace App\Models\Financial;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * ContractTemplate Model
 * 
 * Represents programmable contract templates with automation features.
 * Supports complex billing models, asset/contact-based pricing, and workflow automation.
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
 * @property string $billing_model
 * @property array|null $pricing_structure
 * @property array|null $asset_billing_rules
 * @property array|null $supported_asset_types
 * @property array|null $asset_service_matrix
 * @property float|null $default_per_asset_rate
 * @property array|null $contact_billing_rules
 * @property array|null $contact_access_tiers
 * @property float|null $default_per_contact_rate
 * @property array|null $voip_service_types
 * @property array|null $default_sla_terms
 * @property array|null $default_pricing_structure
 * @property array|null $compliance_templates
 * @property array|null $jurisdictions
 * @property array|null $regulatory_requirements
 * @property string|null $legal_disclaimers
 * @property array|null $calculation_formulas
 * @property array|null $auto_assignment_rules
 * @property array|null $billing_triggers
 * @property array|null $workflow_automation
 * @property array|null $notification_triggers
 * @property array|null $integration_hooks
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
        'billing_model',
        'pricing_structure',
        'asset_billing_rules',
        'supported_asset_types',
        'asset_service_matrix',
        'default_per_asset_rate',
        'contact_billing_rules',
        'contact_access_tiers',
        'default_per_contact_rate',
        'voip_service_types',
        'default_sla_terms',
        'default_pricing_structure',
        'compliance_templates',
        'jurisdictions',
        'regulatory_requirements',
        'legal_disclaimers',
        'calculation_formulas',
        'auto_assignment_rules',
        'billing_triggers',
        'workflow_automation',
        'notification_triggers',
        'integration_hooks',
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
        'pricing_structure' => 'array',
        'asset_billing_rules' => 'array',
        'supported_asset_types' => 'array',
        'asset_service_matrix' => 'array',
        'default_per_asset_rate' => 'decimal:2',
        'contact_billing_rules' => 'array',
        'contact_access_tiers' => 'array',
        'default_per_contact_rate' => 'decimal:2',
        'voip_service_types' => 'array',
        'default_sla_terms' => 'array',
        'default_pricing_structure' => 'array',
        'compliance_templates' => 'array',
        'jurisdictions' => 'array',
        'regulatory_requirements' => 'array',
        'calculation_formulas' => 'array',
        'auto_assignment_rules' => 'array',
        'billing_triggers' => 'array',
        'workflow_automation' => 'array',
        'notification_triggers' => 'array',
        'integration_hooks' => 'array',
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
     * The attributes that should be appended.
     */
    protected $appends = [
        'is_programmable',
        'is_archived',
        'formatted_usage_count',
        'last_usage_description',
    ];

    /**
     * Use archived_at instead of deleted_at for soft deletes
     */
    protected $dates = ['archived_at'];

    /**
     * The "deleted at" column name.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Billing model constants
     */
    const BILLING_MODEL_FIXED = 'fixed';
    const BILLING_MODEL_PER_ASSET = 'per_asset';
    const BILLING_MODEL_PER_CONTACT = 'per_contact';
    const BILLING_MODEL_TIERED = 'tiered';
    const BILLING_MODEL_HYBRID = 'hybrid';

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Template type constants
     */
    const TYPE_SERVICE_AGREEMENT = 'service_agreement';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_SUPPORT = 'support';
    const TYPE_MSP_CONTRACT = 'msp_contract';
    const TYPE_VOIP_SERVICE = 'voip_service';
    const TYPE_SECURITY = 'security';
    const TYPE_BACKUP = 'backup';
    const TYPE_MONITORING = 'monitoring';

    /**
     * Get the parent template.
     */
    public function parentTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'parent_template_id');
    }

    /**
     * Get child templates.
     */
    public function childTemplates(): HasMany
    {
        return $this->hasMany(ContractTemplate::class, 'parent_template_id');
    }

    /**
     * Get contracts using this template.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contract_template_id');
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
     * Check if template is programmable (has automation features).
     */
    public function getIsProgrammableAttribute(): bool
    {
        return !is_null($this->calculation_formulas) ||
               !is_null($this->auto_assignment_rules) ||
               !is_null($this->billing_triggers) ||
               !is_null($this->workflow_automation) ||
               $this->billing_model !== self::BILLING_MODEL_FIXED;
    }

    /**
     * Check if template is archived.
     */
    public function getIsArchivedAttribute(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Get formatted usage count.
     */
    public function getFormattedUsageCountAttribute(): string
    {
        if ($this->usage_count === 0) {
            return 'Never used';
        }

        if ($this->usage_count === 1) {
            return 'Used once';
        }

        return "Used {$this->usage_count} times";
    }

    /**
     * Get last usage description.
     */
    public function getLastUsageDescriptionAttribute(): string
    {
        if (!$this->last_used_at) {
            return 'Never used';
        }

        return $this->last_used_at->diffForHumans();
    }

    /**
     * Check if template supports asset-based billing.
     */
    public function supportsAssetBilling(): bool
    {
        return in_array($this->billing_model, [
            self::BILLING_MODEL_PER_ASSET,
            self::BILLING_MODEL_HYBRID
        ]);
    }

    /**
     * Check if template supports contact-based billing.
     */
    public function supportsContactBilling(): bool
    {
        return in_array($this->billing_model, [
            self::BILLING_MODEL_PER_CONTACT,
            self::BILLING_MODEL_HYBRID
        ]);
    }

    /**
     * Check if template supports tiered billing.
     */
    public function supportsTieredBilling(): bool
    {
        return in_array($this->billing_model, [
            self::BILLING_MODEL_TIERED,
            self::BILLING_MODEL_HYBRID
        ]);
    }

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

    /**
     * Get billing rate for asset type.
     */
    public function getAssetBillingRate(string $assetType): ?float
    {
        $rules = $this->asset_billing_rules ?? [];
        return $rules[$assetType]['rate'] ?? $this->default_per_asset_rate;
    }

    /**
     * Get billing rate for contact tier.
     */
    public function getContactBillingRate(string $tier): ?float
    {
        $rules = $this->contact_billing_rules ?? [];
        return $rules[$tier]['rate'] ?? $this->default_per_contact_rate;
    }

    /**
     * Get services assigned to asset type.
     */
    public function getAssetServices(string $assetType): array
    {
        $matrix = $this->asset_service_matrix ?? [];
        return $matrix[$assetType] ?? [];
    }

    /**
     * Get variable fields for contract generation.
     */
    public function getVariableFields(): array
    {
        return $this->variable_fields ?? [];
    }

    /**
     * Get required fields for contract generation.
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
     * Generate contract content with variables replaced.
     */
    public function generateContractContent(array $variables = []): string
    {
        $content = $this->template_content;
        $defaults = $this->getDefaultValues();
        
        // Merge defaults with provided variables
        $allVariables = array_merge($defaults, $variables);
        
        // Replace variables in content
        foreach ($allVariables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }
        
        return $content;
    }

    /**
     * Validate required fields are provided.
     */
    public function validateVariables(array $variables): array
    {
        $errors = [];
        $required = $this->getRequiredFields();
        
        foreach ($required as $field) {
            if (!array_key_exists($field, $variables) || empty($variables[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }
        
        return $errors;
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

    /**
     * Calculate asset-based charges.
     */
    protected function calculateAssetCharges(array $assets): float
    {
        $total = 0;
        $rules = $this->asset_billing_rules ?? [];

        foreach ($assets as $assetType => $count) {
            $rate = $rules[$assetType]['rate'] ?? $this->default_per_asset_rate ?? 0;
            $total += $rate * $count;
        }

        return $total;
    }

    /**
     * Calculate contact-based charges.
     */
    protected function calculateContactCharges(array $contacts): float
    {
        $total = 0;
        $rules = $this->contact_billing_rules ?? [];

        foreach ($contacts as $tier => $count) {
            $rate = $rules[$tier]['rate'] ?? $this->default_per_contact_rate ?? 0;
            $total += $rate * $count;
        }

        return $total;
    }

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
     * Check if template needs review.
     */
    public function needsReview(): bool
    {
        return $this->next_review_date && $this->next_review_date->isPast();
    }

    /**
     * Get approval workflow steps.
     */
    public function getApprovalWorkflow(): array
    {
        return $this->approval_workflow ?? [];
    }

    /**
     * Check if template requires approval.
     */
    public function requiresApproval(): bool
    {
        return $this->requires_approval;
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
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for draft templates.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for templates by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('template_type', $type);
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
              ->orWhere('billing_model', '!=', self::BILLING_MODEL_FIXED);
        });
    }

    /**
     * Scope for default templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for templates needing review.
     */
    public function scopeNeedsReview($query)
    {
        return $query->whereNotNull('next_review_date')
                    ->whereDate('next_review_date', '<=', now());
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name
        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = \Str::slug($template->name);
            }
        });

        // Prevent deletion of templates in use
        static::deleting(function ($template) {
            if ($template->contracts()->count() > 0) {
                throw new \Exception('Cannot delete template that is used by contracts');
            }
        });
    }
}