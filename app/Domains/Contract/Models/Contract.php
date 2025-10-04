<?php

namespace App\Domains\Contract\Models;

use App\Domains\Contract\Traits\HasCompanyConfiguration;
use App\Domains\Contract\Traits\HasStatusWorkflow;
use App\Models\Asset;
use App\Models\Client;
use App\Models\ComplianceRecord;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contract Model
 *
 * Enterprise-grade contract management with digital signature integration,
 * multi-party support, versioning, compliance tracking, and VoIP-specific features.
 *
 * @property int $id
 * @property int $company_id
 * @property string $contract_number
 * @property string $contract_type
 * @property string $status
 * @property string $signature_status
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int|null $term_months
 * @property string|null $renewal_type
 * @property int|null $renewal_notice_days
 * @property bool $auto_renewal
 * @property float $contract_value
 * @property string $currency_code
 * @property string|null $payment_terms
 * @property array|null $pricing_structure
 * @property array|null $sla_terms
 * @property array|null $voip_specifications
 * @property array|null $compliance_requirements
 * @property string|null $terms_and_conditions
 * @property array|null $custom_clauses
 * @property string|null $termination_clause
 * @property string|null $liability_clause
 * @property string|null $confidentiality_clause
 * @property string|null $dispute_resolution
 * @property array|null $milestones
 * @property array|null $deliverables
 * @property array|null $penalties
 * @property string|null $governing_law
 * @property string|null $jurisdiction
 * @property string|null $template_type
 * @property int|null $template_id
 * @property string|null $url_key
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property \Illuminate\Support\Carbon|null $executed_at
 * @property \Illuminate\Support\Carbon|null $terminated_at
 * @property string|null $termination_reason
 * @property \Illuminate\Support\Carbon|null $last_reviewed_at
 * @property \Illuminate\Support\Carbon|null $next_review_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property int $client_id
 * @property int|null $quote_id
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property int|null $signed_by
 */
class Contract extends Model
{
    use BelongsToCompany, HasCompanyConfiguration, HasFactory, HasStatusWorkflow, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'contracts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'contract_number',
        'contract_type',
        'status',
        'signature_status',
        'title',
        'description',
        'start_date',
        'end_date',
        'term_months',
        'auto_renew',
        'contract_value',
        'billing_model',
        'currency_code',
        'payment_terms',
        'pricing_structure',
        'sla_terms',
        'voip_specifications',
        'compliance_requirements',
        'terms_and_conditions',
        'custom_clauses',
        'termination_clause',
        'liability_clause',
        'confidentiality_clause',
        'dispute_resolution',
        'milestones',
        'deliverables',
        'penalties',
        'governing_law',
        'jurisdiction',
        'template_type',
        'template_id',
        'metadata',
        'signed_at',
        'executed_at',
        'terminated_at',
        'termination_reason',
        'last_reviewed_at',
        'next_review_date',
        'client_id',
        'quote_id',
        'created_by',
        'approved_by',
        'signed_by',
        'content',
        'variables',
        'is_programmable',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'term_months' => 'integer',
        'renewal_notice_days' => 'integer',
        'auto_renewal' => 'boolean',
        'contract_value' => 'decimal:2',
        'pricing_structure' => 'array',
        'sla_terms' => 'array',
        'voip_specifications' => 'array',
        'compliance_requirements' => 'array',
        'custom_clauses' => 'array',
        'milestones' => 'array',
        'deliverables' => 'array',
        'penalties' => 'array',
        'metadata' => 'array',
        'variables' => 'array',
        'signed_at' => 'datetime',
        'executed_at' => 'datetime',
        'terminated_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'next_review_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'client_id' => 'integer',
        'quote_id' => 'integer',
        'template_id' => 'integer',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'signed_by' => 'integer',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING_REVIEW = 'pending_review';

    const STATUS_UNDER_NEGOTIATION = 'under_negotiation';

    const STATUS_PENDING_SIGNATURE = 'pending_signature';

    const STATUS_SIGNED = 'signed';

    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_TERMINATED = 'terminated';

    const STATUS_EXPIRED = 'expired';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Signature Status constants
     */
    const SIGNATURE_NOT_REQUIRED = 'not_required';

    const SIGNATURE_PENDING = 'pending';

    const SIGNATURE_SIGNED = 'signed';

    const SIGNATURE_DECLINED = 'declined';

    // Configuration cache is now handled by HasCompanyConfiguration trait

    /**
     * Get the client this contract belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the quote this contract was generated from.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the contract template used.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    /**
     * Get the contract schedules for this contract.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ContractSchedule::class);
    }

    /**
     * Get active contract schedules.
     */
    public function activeSchedules(): HasMany
    {
        return $this->schedules()->active();
    }

    /**
     * Get effective contract schedules.
     */
    public function effectiveSchedules(): HasMany
    {
        return $this->schedules()->effective();
    }

    /**
     * Get infrastructure schedules (Schedule A).
     */
    public function infrastructureSchedules(): HasMany
    {
        return $this->schedules()->infrastructure();
    }

    /**
     * Get pricing schedules (Schedule B).
     */
    public function pricingSchedules(): HasMany
    {
        return $this->schedules()->pricing();
    }

    /**
     * Get assets supported by this contract.
     */
    public function supportedAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'supporting_contract_id');
    }

    public function componentAssignments(): HasMany
    {
        return $this->hasMany(\App\Domains\Contract\Models\ContractComponentAssignment::class);
    }

    public function activeComponentAssignments(): HasMany
    {
        return $this->componentAssignments()->where('status', 'active');
    }

    /**
     * Get asset assignments for this contract.
     */
    public function assetAssignments(): HasMany
    {
        return $this->hasMany(ContractAssetAssignment::class);
    }

    /**
     * Get active asset assignments for this contract.
     */
    public function activeAssetAssignments(): HasMany
    {
        return $this->assetAssignments()->where('status', ContractAssetAssignment::STATUS_ACTIVE);
    }

    /**
     * Get contact assignments for this contract.
     */
    public function contactAssignments(): HasMany
    {
        return $this->hasMany(ContractContactAssignment::class);
    }

    /**
     * Get active contact assignments for this contract.
     */
    public function activeContactAssignments(): HasMany
    {
        return $this->contactAssignments()->where('status', ContractContactAssignment::STATUS_ACTIVE);
    }

    /**
     * Get the user who created this contract.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this contract.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who signed this contract.
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /**
     * Get contract signatures.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }

    /**
     * Get active contract signatures (excludes declined, expired, voided).
     */
    public function activeSignatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class)
            ->whereNotIn('status', [
                ContractSignature::STATUS_DECLINED,
                ContractSignature::STATUS_EXPIRED,
                ContractSignature::STATUS_VOIDED,
            ]);
    }

    /**
     * Get contract approvals.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(ContractApproval::class);
    }

    /**
     * Get contract amendments.
     */
    public function amendments(): HasMany
    {
        return $this->hasMany(ContractAmendment::class);
    }

    /**
     * Get contract milestones.
     */
    public function contractMilestones(): HasMany
    {
        return $this->hasMany(ContractMilestone::class);
    }

    /**
     * Get invoices generated from this contract.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get recurring invoices setup for this contract.
     */
    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    /**
     * Get contract billing calculations.
     */
    public function billingCalculations(): HasMany
    {
        return $this->hasMany(ContractBillingCalculation::class);
    }

    /**
     * Get the latest billing calculation.
     */
    public function latestBillingCalculation(): HasMany
    {
        return $this->billingCalculations()->orderBy('billing_period_end', 'desc')->limit(1);
    }

    /**
     * Get pending billing calculations.
     */
    public function pendingBillingCalculations(): HasMany
    {
        return $this->billingCalculations()->where('status', ContractBillingCalculation::STATUS_CALCULATED);
    }

    /**
     * Get compliance tracking records.
     */
    public function complianceRecords(): HasMany
    {
        return $this->hasMany(ComplianceRecord::class);
    }

    /**
     * Get performance metrics data from the contract's metadata.
     */
    public function getPerformanceMetrics(): array
    {
        return $this->performance_metrics ?? [];
    }

    /**
     * Update performance metrics with new data.
     */
    public function updatePerformanceMetrics(array $metrics): void
    {
        $currentMetrics = $this->getPerformanceMetrics();
        $updatedMetrics = array_merge($currentMetrics, $metrics);

        $this->update(['performance_metrics' => $updatedMetrics]);
    }

    /**
     * Get the contract's full number.
     */
    public function getFullNumber(): string
    {
        return $this->contract_number;
    }

    // Company configuration methods moved to HasCompanyConfiguration trait

    // Available types and statuses methods moved to HasCompanyConfiguration trait

    /**
     * Check if contract is active.
     */
    public function isActive(): bool
    {
        return $this->hasStatus('active_statuses');
    }

    /**
     * Check if contract is signed.
     */
    public function isSigned(): bool
    {
        return $this->isStatusInList($this->signature_status, 'signed_signature_statuses', ['fully_executed']);
    }

    /**
     * Check if contract can be edited.
     */
    public function canBeEdited(): bool
    {
        $editableStatuses = ['draft', 'pending_review', 'under_negotiation', 'pending_signature', 'active'];

        return ! $this->isSigned() && $this->isStatusInList($this->status, 'editable_statuses', $editableStatuses);
    }

    /**
     * Check if contract can be terminated.
     */
    public function canBeTerminated(): bool
    {
        return $this->isStatusInList($this->status, 'terminable_statuses', ['active', 'signed', 'suspended']);
    }

    /**
     * Check if contract is expired.
     */
    public function isExpired(): bool
    {
        if (! $this->end_date) {
            return false;
        }

        return Carbon::now()->gt($this->end_date);
    }

    /**
     * Check if contract is due for renewal.
     */
    public function isDueForRenewal(int $daysBefore = 30): bool
    {
        if (! $this->end_date) {
            return false;
        }

        $nonRenewableTypes = $this->getCompanyConfig()['non_renewable_types'] ?? ['none'];
        if (in_array($this->renewal_type, $nonRenewableTypes)) {
            return false;
        }

        return Carbon::now()->addDays($daysBefore)->gte($this->end_date);
    }

    /**
     * Check if contract needs review.
     */
    public function needsReview(): bool
    {
        if (! $this->next_review_date) {
            return false;
        }

        return Carbon::now()->gte($this->next_review_date);
    }

    /**
     * Get contract duration in months.
     */
    public function getDurationMonths(): ?int
    {
        if (! $this->start_date || ! $this->end_date) {
            return $this->term_months;
        }

        return $this->start_date->diffInMonths($this->end_date);
    }

    /**
     * Get remaining term in days.
     */
    public function getRemainingDays(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return max(0, Carbon::now()->diffInDays($this->end_date, false));
    }

    /**
     * Calculate monthly recurring revenue.
     */
    public function getMonthlyRecurringRevenue(): float
    {
        if (! $this->pricing_structure) {
            return 0;
        }

        $monthlyTotal = 0;
        $pricing = $this->pricing_structure;

        // Base monthly recurring revenue
        $monthlyTotal += (float) ($pricing['recurring_monthly'] ?? 0);

        // Per-user pricing (if applicable)
        $perUser = (float) ($pricing['per_user'] ?? 0);
        if ($perUser > 0) {
            // Could multiply by user count if available
            $monthlyTotal += $perUser;
        }

        // Asset-based pricing
        if (isset($pricing['asset_pricing']) && is_array($pricing['asset_pricing'])) {
            foreach ($pricing['asset_pricing'] as $assetType => $config) {
                if (! empty($config['enabled']) && ! empty($config['price']) && $config['price'] !== '') {
                    $assetCount = $this->supportedAssets()->where('type', $assetType)->count();
                    $monthlyTotal += (float) $config['price'] * $assetCount;
                }
            }
        }

        // Template-specific recurring pricing
        if (isset($pricing['telecom_pricing'])) {
            $perChannel = $pricing['telecom_pricing']['perChannel'] ?? '';
            $callingPlan = $pricing['telecom_pricing']['callingPlan'] ?? '';
            $e911 = $pricing['telecom_pricing']['e911'] ?? '';

            $monthlyTotal += $perChannel !== '' ? (float) $perChannel : 0;
            $monthlyTotal += $callingPlan !== '' ? (float) $callingPlan : 0;
            $monthlyTotal += $e911 !== '' ? (float) $e911 : 0;
        }

        if (isset($pricing['compliance_pricing']['frameworkMonthly'])) {
            foreach ($pricing['compliance_pricing']['frameworkMonthly'] as $framework => $monthlyFee) {
                if ($monthlyFee !== '') {
                    $monthlyTotal += (float) $monthlyFee;
                }
            }
        }

        return $monthlyTotal;
    }

    /**
     * Get annual contract value.
     */
    public function getAnnualValue(): float
    {
        $monthlyRevenue = $this->getMonthlyRecurringRevenue();
        $oneTimeRevenue = (float) ($this->pricing_structure['one_time'] ?? 0);

        return ($monthlyRevenue * 12) + $oneTimeRevenue;
    }

    /**
     * Get count of assigned assets for this contract.
     */
    public function getAssignedAssetCount(): int
    {
        return $this->supportedAssets()->count();
    }

    /**
     * Check if auto-assignment is enabled.
     */
    public function hasAutoAssignmentEnabled(): bool
    {
        return $this->sla_terms['auto_assign_new_assets'] ?? false;
    }

    /**
     * Get supported asset types from SLA terms.
     */
    public function getSupportedAssetTypes(): array
    {
        return $this->sla_terms['supported_asset_types'] ?? [];
    }

    /**
     * Check SLA compliance.
     */
    public function checkSLACompliance(): array
    {
        if (! $this->sla_terms) {
            return [];
        }

        $compliance = [];
        foreach ($this->sla_terms as $metric => $target) {
            // This would integrate with monitoring systems
            $compliance[$metric] = [
                'target' => $target,
                'actual' => 0, // Would be calculated from performance data
                'compliant' => true,
                'last_checked' => now(),
            ];
        }

        return $compliance;
    }

    /**
     * Get VoIP service configuration.
     */
    public function getVoipServices(): array
    {
        return $this->voip_specifications['services'] ?? [];
    }

    /**
     * Get equipment included in contract.
     */
    public function getEquipmentList(): array
    {
        return $this->voip_specifications['equipment'] ?? [];
    }

    /**
     * Get compliance requirements.
     */
    public function getComplianceRequirements(): array
    {
        return $this->compliance_requirements ?? [];
    }

    /**
     * Get next milestone.
     */
    public function getNextMilestone(): ?array
    {
        if (! $this->milestones) {
            return null;
        }

        $now = Carbon::now();
        foreach ($this->milestones as $milestone) {
            $dueDate = Carbon::parse($milestone['due_date']);
            if ($dueDate->gt($now) && ! ($milestone['completed'] ?? false)) {
                return $milestone;
            }
        }

        return null;
    }

    /**
     * Get contract performance score.
     */
    public function getPerformanceScore(): float
    {
        // For now, return a default score since ContractPerformance model doesn't exist yet
        // This would be integrated with actual performance monitoring systems
        return 95.5;
    }

    /**
     * Generate public URL for client access.
     */
    public function getPublicUrl(): string
    {
        if (! $this->url_key) {
            $this->generateUrlKey();
        }

        return url('/contract/'.$this->url_key);
    }

    /**
     * Generate URL key for public access.
     */
    public function generateUrlKey(): void
    {
        $this->update(['url_key' => bin2hex(random_bytes(16))]);
    }

    // Status workflow methods moved to HasStatusWorkflow trait

    /**
     * Create amendment.
     */
    public function createAmendment(array $changes, string $reason): ContractAmendment
    {
        return ContractAmendment::create([
            'contract_id' => $this->id,
            'company_id' => $this->company_id,
            'amendment_number' => $this->amendments()->count() + 1,
            'changes' => $changes,
            'reason' => $reason,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Calculate renewal terms.
     */
    public function calculateRenewalTerms(): array
    {
        $renewalTerms = [
            'start_date' => $this->end_date,
            'end_date' => $this->end_date ? $this->end_date->copy()->addMonths($this->term_months ?? 12) : null,
            'contract_value' => $this->contract_value,
            'pricing_structure' => $this->pricing_structure,
        ];

        // Apply any automatic pricing adjustments
        if (isset($this->pricing_structure['renewal_adjustment'])) {
            $adjustment = $this->pricing_structure['renewal_adjustment'];
            if (isset($adjustment['type']) && isset($adjustment['value'])) {
                if ($adjustment['type'] === 'percentage') {
                    $renewalTerms['contract_value'] *= (1 + ($adjustment['value'] / 100));
                } else {
                    $renewalTerms['contract_value'] += $adjustment['value'];
                }
            }
        }

        return $renewalTerms;
    }

    /**
     * Format contract value with currency.
     */
    public function getFormattedValue(): string
    {
        return $this->formatCurrency($this->contract_value);
    }

    /**
     * Format amount with currency.
     */
    public function formatCurrency(float $amount): string
    {
        $symbol = $this->getCurrencySymbol();

        return $symbol.number_format($amount, 2);
    }

    /**
     * Get currency symbol.
     */
    public function getCurrencySymbol(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
        ];

        return $symbols[$this->currency_code] ?? $this->currency_code;
    }

    /**
     * Scope to get contracts by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active contracts.
     */
    public function scopeActive($query)
    {
        // This would need to be company-aware, but for now use a default
        $activeStatuses = [self::STATUS_ACTIVE, self::STATUS_SIGNED];

        return $query->whereIn('status', $activeStatuses);
    }

    /**
     * Scope to get expired contracts.
     */
    public function scopeExpired($query)
    {
        $expirableStatuses = [self::STATUS_ACTIVE, self::STATUS_SIGNED];

        return $query->where('end_date', '<', Carbon::now())
            ->whereIn('status', $expirableStatuses);
    }

    /**
     * Scope to get contracts expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        $futureDate = Carbon::now()->addDays($days);
        $expirableStatuses = [self::STATUS_ACTIVE, self::STATUS_SIGNED];

        return $query->whereBetween('end_date', [Carbon::now(), $futureDate])
            ->whereIn('status', $expirableStatuses);
    }

    /**
     * Scope to get contracts due for renewal.
     */
    public function scopeDueForRenewal($query, int $daysBefore = 30)
    {
        $renewableTypes = ['manual', 'automatic', 'negotiated'];

        return $query->expiringSoon($daysBefore)
            ->whereIn('renewal_type', $renewableTypes);
    }

    /**
     * Scope to search contracts.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('contract_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Get validation rules for contract creation.
     */
    public function getValidationRules(?int $companyId = null): array
    {
        $contractTypes = array_keys($this->getAvailableContractTypes($companyId));
        $statuses = $this->getAvailableStatusValues($companyId);
        $renewalTypes = array_keys($this->getAvailableRenewalTypes($companyId));

        return [
            'contract_type' => 'required|in:'.implode(',', $contractTypes ?: ['default']),
            'status' => 'required|in:'.implode(',', $statuses ?: ['draft']),
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'term_months' => 'nullable|integer|min:1|max:120',
            'renewal_type' => 'required|in:'.implode(',', $renewalTypes ?: ['none']),
            'contract_value' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'client_id' => 'required|integer|exists:clients,id',
            'quote_id' => 'nullable|integer|exists:quotes,id',
        ];
    }

    // Configuration getter methods moved to HasCompanyConfiguration trait

    /**
     * Get audit history for this contract.
     */
    public function getAuditHistory(): array
    {
        $history = [];

        // Contract creation
        $history[] = [
            'description' => 'Contract created',
            'icon' => 'plus',
            'date' => $this->created_at,
            'user' => $this->creator?->name ?? 'System',
            'type' => 'creation',
        ];

        // Status changes (could be tracked via activity log if available)
        if ($this->signed_at) {
            $history[] = [
                'description' => 'Contract signed',
                'icon' => 'signature',
                'date' => $this->signed_at,
                'user' => $this->signer?->name ?? 'Client',
                'type' => 'signature',
            ];
        }

        if ($this->executed_at) {
            $history[] = [
                'description' => 'Contract executed',
                'icon' => 'check',
                'date' => $this->executed_at,
                'user' => $this->approver?->name ?? 'System',
                'type' => 'execution',
            ];
        }

        if ($this->terminated_at) {
            $history[] = [
                'description' => 'Contract terminated',
                'icon' => 'times',
                'date' => $this->terminated_at,
                'user' => 'System',
                'type' => 'termination',
                'reason' => $this->termination_reason,
            ];
        }

        // Add amendments
        foreach ($this->amendments ?? [] as $amendment) {
            $history[] = [
                'description' => 'Amendment added',
                'icon' => 'edit',
                'date' => $amendment->created_at,
                'user' => $amendment->creator?->name ?? 'System',
                'type' => 'amendment',
                'details' => $amendment->reason ?? 'Contract modification',
            ];
        }

        // Add signature events
        foreach ($this->signatures ?? [] as $signature) {
            if ($signature->signed_at) {
                $history[] = [
                    'description' => "Signature by {$signature->signatory_name}",
                    'icon' => 'signature',
                    'date' => $signature->signed_at,
                    'user' => $signature->signatory_name,
                    'type' => 'signature',
                ];
            }
        }

        // Sort by date descending
        usort($history, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $history;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Contract number generation is handled by ContractService to avoid race conditions
        static::creating(function ($contract) {
            // Contract number should already be set by the service layer

            if (! $contract->currency_code) {
                $contract->currency_code = 'USD';
            }

            if (! $contract->signature_status) {
                // Get default signature status from company config if available
                $defaultSignatureStatus = 'pending';
                if (isset($contract->company_id)) {
                    $config = app('contract.config.registry')
                        ->getCompanyConfiguration($contract->company_id);
                    $defaultSignatureStatus = $config['default_signature_status'] ?? 'pending';
                }
                $contract->signature_status = $defaultSignatureStatus;
            }
        });

        // Update status based on dates
        static::retrieved(function ($contract) {
            if ($contract->isExpired() && $contract->isActive()) {
                $config = $contract->getCompanyConfig();
                $expiredStatus = $config['default_expired_status'] ?? 'expired';
                $contract->update(['status' => $expiredStatus]);
            }
        });
    }
}
