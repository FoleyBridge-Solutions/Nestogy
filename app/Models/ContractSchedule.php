<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * ContractSchedule Model
 * 
 * Manages contract schedules (A, B, C, etc.) that define different aspects
 * of MSP contracts like infrastructure coverage, pricing, and additional terms.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $contract_id
 * @property string $schedule_type
 * @property string $schedule_letter
 * @property string $title
 * @property string|null $description
 * @property string $content
 * @property array|null $variables
 * @property array|null $variable_values
 * @property array|null $required_fields
 * @property array|null $supported_asset_types
 * @property array|null $service_levels
 * @property array|null $coverage_rules
 * @property array|null $sla_terms
 * @property array|null $response_times
 * @property array|null $coverage_hours
 * @property array|null $escalation_procedures
 * @property array|null $pricing_structure
 * @property array|null $billing_rules
 * @property array|null $rate_tables
 * @property array|null $discount_structures
 * @property array|null $penalty_structures
 * @property array|null $asset_inclusion_rules
 * @property array|null $asset_exclusion_rules
 * @property array|null $location_coverage
 * @property array|null $client_tier_requirements
 * @property bool $auto_assign_assets
 * @property bool $require_manual_approval
 * @property array|null $automation_rules
 * @property array|null $assignment_triggers
 * @property string $status
 * @property string $approval_status
 * @property string|null $approval_notes
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property string $version
 * @property int|null $parent_schedule_id
 * @property int|null $template_id
 * @property bool $is_template
 * @property int $asset_count
 * @property int $usage_count
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property float|null $effectiveness_score
 * @property \Illuminate\Support\Carbon|null $effective_date
 * @property \Illuminate\Support\Carbon|null $expiration_date
 * @property \Illuminate\Support\Carbon|null $last_reviewed_at
 * @property \Illuminate\Support\Carbon|null $next_review_date
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property array|null $metadata
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class ContractSchedule extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_schedules';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'contract_id',
        'schedule_type',
        'schedule_letter',
        'title',
        'description',
        'content',
        'variables',
        'variable_values',
        'required_fields',
        'supported_asset_types',
        'service_levels',
        'coverage_rules',
        'sla_terms',
        'response_times',
        'coverage_hours',
        'escalation_procedures',
        'pricing_structure',
        'billing_rules',
        'rate_tables',
        'discount_structures',
        'penalty_structures',
        'asset_inclusion_rules',
        'asset_exclusion_rules',
        'location_coverage',
        'client_tier_requirements',
        'auto_assign_assets',
        'require_manual_approval',
        'automation_rules',
        'assignment_triggers',
        'status',
        'approval_status',
        'approval_notes',
        'approved_at',
        'approved_by',
        'version',
        'parent_schedule_id',
        'template_id',
        'is_template',
        'asset_count',
        'usage_count',
        'last_used_at',
        'effectiveness_score',
        'effective_date',
        'expiration_date',
        'last_reviewed_at',
        'next_review_date',
        'created_by',
        'updated_by',
        'metadata',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'contract_id' => 'integer',
        'variables' => 'array',
        'variable_values' => 'array',
        'required_fields' => 'array',
        'supported_asset_types' => 'array',
        'service_levels' => 'array',
        'coverage_rules' => 'array',
        'sla_terms' => 'array',
        'response_times' => 'array',
        'coverage_hours' => 'array',
        'escalation_procedures' => 'array',
        'pricing_structure' => 'array',
        'billing_rules' => 'array',
        'rate_tables' => 'array',
        'discount_structures' => 'array',
        'penalty_structures' => 'array',
        'asset_inclusion_rules' => 'array',
        'asset_exclusion_rules' => 'array',
        'location_coverage' => 'array',
        'client_tier_requirements' => 'array',
        'auto_assign_assets' => 'boolean',
        'require_manual_approval' => 'boolean',
        'automation_rules' => 'array',
        'assignment_triggers' => 'array',
        'approved_at' => 'datetime',
        'approved_by' => 'integer',
        'parent_schedule_id' => 'integer',
        'template_id' => 'integer',
        'is_template' => 'boolean',
        'asset_count' => 'integer',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'effectiveness_score' => 'decimal:2',
        'effective_date' => 'date',
        'expiration_date' => 'date',
        'last_reviewed_at' => 'datetime',
        'next_review_date' => 'date',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Schedule type enumeration
     */
    const TYPE_INFRASTRUCTURE = 'A'; // Infrastructure and SLA
    const TYPE_PRICING = 'B'; // Pricing and fees
    const TYPE_ADDITIONAL = 'C'; // Additional terms
    const TYPE_COMPLIANCE = 'D'; // Compliance requirements
    const TYPE_CUSTOM = 'E'; // Custom schedules

    /**
     * Status enumeration
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Approval status enumeration
     */
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';
    const APPROVAL_CHANGES_REQUESTED = 'changes_requested';

    /**
     * Get the contract this schedule belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the parent schedule (for versioning).
     */
    public function parentSchedule(): BelongsTo
    {
        return $this->belongsTo(ContractSchedule::class, 'parent_schedule_id');
    }

    /**
     * Get child schedule versions.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ContractSchedule::class, 'parent_schedule_id');
    }

    /**
     * Get the user who created this schedule.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this schedule.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved this schedule.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get assets that are covered by this schedule.
     */
    public function coveredAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'supporting_schedule_id');
    }

    /**
     * Check if schedule is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if schedule is approved.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    /**
     * Check if schedule is effective (active and within date range).
     */
    public function isEffective(): bool
    {
        if (!$this->isActive() || !$this->isApproved()) {
            return false;
        }

        $now = Carbon::now();
        
        if ($this->effective_date && $now->lt($this->effective_date)) {
            return false;
        }

        if ($this->expiration_date && $now->gt($this->expiration_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if schedule needs review.
     */
    public function needsReview(): bool
    {
        if (!$this->next_review_date) {
            return false;
        }

        return Carbon::now()->gte($this->next_review_date);
    }

    /**
     * Check if this is an infrastructure schedule (Schedule A).
     */
    public function isInfrastructureSchedule(): bool
    {
        return $this->schedule_type === self::TYPE_INFRASTRUCTURE;
    }

    /**
     * Check if this is a pricing schedule (Schedule B).
     */
    public function isPricingSchedule(): bool
    {
        return $this->schedule_type === self::TYPE_PRICING;
    }

    /**
     * Get supported asset types for this schedule.
     */
    public function getSupportedAssetTypes(): array
    {
        return $this->supported_asset_types ?? [];
    }

    /**
     * Check if an asset type is supported by this schedule.
     */
    public function supportsAssetType(string $assetType): bool
    {
        $supportedTypes = $this->getSupportedAssetTypes();
        
        // If no specific types defined, support all
        if (empty($supportedTypes)) {
            return true;
        }

        return in_array($assetType, $supportedTypes);
    }

    /**
     * Evaluate if an asset should be covered by this schedule.
     */
    public function shouldCoverAsset(Asset $asset): bool
    {
        // Must be an infrastructure schedule to cover assets
        if (!$this->isInfrastructureSchedule()) {
            return false;
        }

        // Must be effective
        if (!$this->isEffective()) {
            return false;
        }

        // Check asset type support
        if (!$this->supportsAssetType($asset->type)) {
            return false;
        }

        // Apply inclusion rules
        if (!$this->passesInclusionRules($asset)) {
            return false;
        }

        // Apply exclusion rules
        if ($this->failsExclusionRules($asset)) {
            return false;
        }

        // Check location coverage
        if (!$this->coversLocation($asset)) {
            return false;
        }

        return true;
    }

    /**
     * Check if asset passes inclusion rules.
     */
    protected function passesInclusionRules(Asset $asset): bool
    {
        $rules = $this->asset_inclusion_rules;
        
        if (empty($rules)) {
            return true; // No specific inclusion rules = include all
        }

        foreach ($rules as $rule) {
            if ($this->evaluateAssetRule($asset, $rule)) {
                return true; // Asset matches at least one inclusion rule
            }
        }

        return false;
    }

    /**
     * Check if asset fails exclusion rules.
     */
    protected function failsExclusionRules(Asset $asset): bool
    {
        $rules = $this->asset_exclusion_rules;
        
        if (empty($rules)) {
            return false; // No exclusion rules = don't exclude
        }

        foreach ($rules as $rule) {
            if ($this->evaluateAssetRule($asset, $rule)) {
                return true; // Asset matches exclusion rule = exclude it
            }
        }

        return false;
    }

    /**
     * Check if asset location is covered.
     */
    protected function coversLocation(Asset $asset): bool
    {
        $coverage = $this->location_coverage;
        
        if (empty($coverage)) {
            return true; // No location restrictions = cover all locations
        }

        // If asset has no location, check if null locations are covered
        if (!$asset->location_id) {
            return $coverage['include_null_locations'] ?? false;
        }

        $includedLocations = $coverage['included_locations'] ?? [];
        $excludedLocations = $coverage['excluded_locations'] ?? [];

        // Check if location is explicitly excluded
        if (in_array($asset->location_id, $excludedLocations)) {
            return false;
        }

        // Check if location is explicitly included (or if no specific inclusions)
        if (empty($includedLocations) || in_array($asset->location_id, $includedLocations)) {
            return true;
        }

        return false;
    }

    /**
     * Evaluate a single asset rule.
     */
    protected function evaluateAssetRule(Asset $asset, array $rule): bool
    {
        $field = $rule['field'] ?? '';
        $operator = $rule['operator'] ?? '=';
        $value = $rule['value'] ?? '';

        $assetValue = $this->getAssetFieldValue($asset, $field);

        return match ($operator) {
            '=' => $assetValue == $value,
            '!=' => $assetValue != $value,
            'in' => in_array($assetValue, (array) $value),
            'not_in' => !in_array($assetValue, (array) $value),
            'contains' => str_contains((string) $assetValue, (string) $value),
            'starts_with' => str_starts_with((string) $assetValue, (string) $value),
            'ends_with' => str_ends_with((string) $assetValue, (string) $value),
            '>' => $assetValue > $value,
            '<' => $assetValue < $value,
            '>=' => $assetValue >= $value,
            '<=' => $assetValue <= $value,
            default => false,
        };
    }

    /**
     * Get asset field value for rule evaluation.
     */
    protected function getAssetFieldValue(Asset $asset, string $field): mixed
    {
        return match ($field) {
            'type' => $asset->type,
            'status' => $asset->status,
            'location_id' => $asset->location_id,
            'vendor_id' => $asset->vendor_id,
            'make' => $asset->make,
            'model' => $asset->model,
            'os' => $asset->os,
            'name' => $asset->name,
            'serial' => $asset->serial,
            'ip' => $asset->ip,
            'mac' => $asset->mac,
            default => null,
        };
    }

    /**
     * Get service level for an asset type.
     */
    public function getServiceLevel(string $assetType): ?array
    {
        $serviceLevels = $this->service_levels ?? [];
        
        return $serviceLevels[$assetType] ?? $serviceLevels['default'] ?? null;
    }

    /**
     * Get response time for a priority level.
     */
    public function getResponseTime(string $priority): ?string
    {
        $responseTimes = $this->response_times ?? [];
        
        return $responseTimes[$priority] ?? null;
    }

    /**
     * Process schedule content with variables.
     */
    public function processContent(array $additionalVariables = []): string
    {
        $content = $this->content;
        $variables = array_merge($this->variable_values ?? [], $additionalVariables);
        
        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }

        return $content;
    }

    /**
     * Mark schedule as approved.
     */
    public function approve(?Carbon $approvedAt = null, ?int $approvedBy = null): void
    {
        $this->update([
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_at' => $approvedAt ?? now(),
            'approved_by' => $approvedBy ?? auth()->id(),
        ]);
    }

    /**
     * Activate the schedule.
     */
    public function activate(?Carbon $effectiveDate = null): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'effective_date' => $effectiveDate ?? now(),
        ]);

        // Fire event to trigger asset re-evaluation
        event(new \App\Events\ContractScheduleActivated($this));
    }

    /**
     * Create new version of this schedule.
     */
    public function createVersion(array $changes = []): ContractSchedule
    {
        $newSchedule = $this->replicate();
        $newSchedule->parent_schedule_id = $this->id;
        $newSchedule->version = $this->getNextVersion();
        $newSchedule->status = self::STATUS_DRAFT;
        $newSchedule->approval_status = self::APPROVAL_PENDING;
        $newSchedule->approved_at = null;
        $newSchedule->approved_by = null;
        $newSchedule->is_template = false;
        $newSchedule->usage_count = 0;
        $newSchedule->last_used_at = null;
        $newSchedule->created_by = auth()->id();

        // Apply changes
        foreach ($changes as $key => $value) {
            $newSchedule->$key = $value;
        }

        $newSchedule->save();

        return $newSchedule;
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
     * Update asset count.
     */
    public function updateAssetCount(): void
    {
        $count = $this->coveredAssets()->count();
        $this->update(['asset_count' => $count]);
    }

    /**
     * Scope to get active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get approved schedules.
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    /**
     * Scope to get effective schedules.
     */
    public function scopeEffective($query)
    {
        $now = now();
        
        return $query->active()
            ->approved()
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_date')
                  ->orWhere('effective_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expiration_date')
                  ->orWhere('expiration_date', '>', $now);
            });
    }

    /**
     * Scope to get infrastructure schedules.
     */
    public function scopeInfrastructure($query)
    {
        return $query->where('schedule_type', self::TYPE_INFRASTRUCTURE);
    }

    /**
     * Scope to get pricing schedules.
     */
    public function scopePricing($query)
    {
        return $query->where('schedule_type', self::TYPE_PRICING);
    }

    /**
     * Scope to get schedules by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('schedule_type', $type);
    }

    /**
     * Scope to get schedules that auto-assign assets.
     */
    public function scopeAutoAssign($query)
    {
        return $query->where('auto_assign_assets', true);
    }

    /**
     * Scope to get template schedules.
     */
    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    /**
     * Scope to get schedules needing review.
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('next_review_date', '<=', now());
    }

    /**
     * Get available schedule types.
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_INFRASTRUCTURE => 'Schedule A - Infrastructure & SLA',
            self::TYPE_PRICING => 'Schedule B - Pricing & Fees',
            self::TYPE_ADDITIONAL => 'Schedule C - Additional Terms',
            self::TYPE_COMPLIANCE => 'Schedule D - Compliance Requirements',
            self::TYPE_CUSTOM => 'Schedule E - Custom Terms',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set defaults when creating
        static::creating(function ($schedule) {
            if (!$schedule->version) {
                $schedule->version = '1.0';
            }

            if (!$schedule->created_by) {
                $schedule->created_by = auth()->id();
            }

            if (!$schedule->schedule_letter) {
                $schedule->schedule_letter = $schedule->schedule_type;
            }
        });

        // Update modified by when updating
        static::updating(function ($schedule) {
            $schedule->updated_by = auth()->id();
        });
    }
}