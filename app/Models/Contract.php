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
    use HasFactory, SoftDeletes, BelongsToCompany;

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
     * Contract type enumeration
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

    /**
     * Contract status enumeration
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
     * Signature status enumeration
     */
    const SIGNATURE_NOT_REQUIRED = 'not_required';
    const SIGNATURE_PENDING = 'pending';
    const SIGNATURE_CLIENT_SIGNED = 'client_signed';
    const SIGNATURE_COMPANY_SIGNED = 'company_signed';
    const SIGNATURE_FULLY_EXECUTED = 'fully_executed';
    const SIGNATURE_DECLINED = 'declined';
    const SIGNATURE_EXPIRED = 'expired';

    /**
     * Renewal type enumeration
     */
    const RENEWAL_NONE = 'none';
    const RENEWAL_MANUAL = 'manual';
    const RENEWAL_AUTOMATIC = 'automatic';
    const RENEWAL_NEGOTIATED = 'negotiated';

    /**
     * VoIP service types
     */
    const VOIP_HOSTED_PBX = 'hosted_pbx';
    const VOIP_SIP_TRUNKING = 'sip_trunking';
    const VOIP_CLOUD_CONTACT_CENTER = 'cloud_contact_center';
    const VOIP_UNIFIED_COMMUNICATIONS = 'unified_communications';
    const VOIP_INTERNATIONAL_CALLING = 'international_calling';
    const VOIP_PORTING_SERVICES = 'porting_services';
    const VOIP_E911_SERVICES = 'e911_services';

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
        return $this->hasMany(\App\Models\Financial\ContractComponentAssignment::class);
    }

    public function activeComponentAssignments(): HasMany
    {
        return $this->componentAssignments()->where('status', 'active');
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
                ContractSignature::STATUS_VOIDED
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
     * Get compliance tracking records.
     */
    public function complianceRecords(): HasMany
    {
        return $this->hasMany(ComplianceRecord::class);
    }

    /**
     * Get performance metrics.
     * Note: ContractPerformance model would be created for production use
     */
    public function performanceMetrics(): HasMany
    {
        // Placeholder - would link to actual performance tracking model
        return $this->hasMany(ContractMilestone::class); // Temporary placeholder
    }

    /**
     * Get the contract's full number.
     */
    public function getFullNumber(): string
    {
        return $this->contract_number;
    }

    /**
     * Check if contract is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if contract is signed.
     */
    public function isSigned(): bool
    {
        return $this->signature_status === self::SIGNATURE_FULLY_EXECUTED;
    }

    /**
     * Check if contract can be edited.
     */
    public function canBeEdited(): bool
    {
        // Contract can be edited if it's not fully executed/signed and in editable status
        return !$this->isSigned() && in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_UNDER_NEGOTIATION,
            self::STATUS_PENDING_SIGNATURE,
            self::STATUS_ACTIVE
        ]);
    }

    /**
     * Check if contract can be terminated.
     */
    public function canBeTerminated(): bool
    {
        // Contract can be terminated if it's active, signed, or suspended
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_SIGNED,
            self::STATUS_SUSPENDED
        ]);
    }

    /**
     * Check if contract is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->end_date) {
            return false;
        }

        return Carbon::now()->gt($this->end_date);
    }

    /**
     * Check if contract is due for renewal.
     */
    public function isDueForRenewal(int $daysBefore = 30): bool
    {
        if (!$this->end_date || $this->renewal_type === self::RENEWAL_NONE) {
            return false;
        }

        return Carbon::now()->addDays($daysBefore)->gte($this->end_date);
    }

    /**
     * Check if contract needs review.
     */
    public function needsReview(): bool
    {
        if (!$this->next_review_date) {
            return false;
        }

        return Carbon::now()->gte($this->next_review_date);
    }

    /**
     * Get contract duration in months.
     */
    public function getDurationMonths(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return $this->term_months;
        }

        return $this->start_date->diffInMonths($this->end_date);
    }

    /**
     * Get remaining term in days.
     */
    public function getRemainingDays(): ?int
    {
        if (!$this->end_date) {
            return null;
        }

        return max(0, Carbon::now()->diffInDays($this->end_date, false));
    }

    /**
     * Calculate monthly recurring revenue.
     */
    public function getMonthlyRecurringRevenue(): float
    {
        if (!$this->pricing_structure) {
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
                if (!empty($config['enabled']) && !empty($config['price']) && $config['price'] !== '') {
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
        if (!$this->sla_terms) {
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
        if (!$this->milestones) {
            return null;
        }

        $now = Carbon::now();
        foreach ($this->milestones as $milestone) {
            $dueDate = Carbon::parse($milestone['due_date']);
            if ($dueDate->gt($now) && !($milestone['completed'] ?? false)) {
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
        if (!$this->url_key) {
            $this->generateUrlKey();
        }

        return url('/contract/' . $this->url_key);
    }

    /**
     * Generate URL key for public access.
     */
    public function generateUrlKey(): void
    {
        $this->update(['url_key' => bin2hex(random_bytes(16))]);
    }

    /**
     * Mark contract as signed.
     */
    public function markAsSigned(?Carbon $signedAt = null): void
    {
        $this->update([
            'status' => self::STATUS_SIGNED,
            'signature_status' => self::SIGNATURE_FULLY_EXECUTED,
            'signed_at' => $signedAt ?? now(),
        ]);
    }

    /**
     * Mark contract as active.
     */
    public function markAsActive(?Carbon $executedAt = null): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'executed_at' => $executedAt ?? now(),
        ]);
    }

    /**
     * Terminate contract.
     */
    public function terminate(?string $reason = null, ?Carbon $terminationDate = null): void
    {
        $this->update([
            'status' => self::STATUS_TERMINATED,
            'terminated_at' => $terminationDate ?? now(),
            'termination_reason' => $reason,
        ]);
    }

    /**
     * Suspend contract.
     */
    public function suspend(?string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['suspension_reason'] = $reason;
        $metadata['suspended_at'] = now();

        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Reactivate suspended contract.
     */
    public function reactivate(): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['reactivated_at'] = now();
        unset($metadata['suspension_reason'], $metadata['suspended_at']);

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'metadata' => $metadata,
        ]);
    }

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
        return $symbol . number_format($amount, 2);
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
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get expired contracts.
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', Carbon::now())
                    ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_SIGNED]);
    }

    /**
     * Scope to get contracts expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        $futureDate = Carbon::now()->addDays($days);
        
        return $query->whereBetween('end_date', [Carbon::now(), $futureDate])
                    ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_SIGNED]);
    }

    /**
     * Scope to get contracts due for renewal.
     */
    public function scopeDueForRenewal($query, int $daysBefore = 30)
    {
        return $query->expiringSoon($daysBefore)
                    ->whereIn('renewal_type', [self::RENEWAL_MANUAL, self::RENEWAL_AUTOMATIC]);
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
    public static function getValidationRules(): array
    {
        return [
            'contract_type' => 'required|in:' . implode(',', [
                self::TYPE_SERVICE_AGREEMENT,
                self::TYPE_EQUIPMENT_LEASE,
                self::TYPE_INSTALLATION_CONTRACT,
                self::TYPE_MAINTENANCE_AGREEMENT,
                self::TYPE_SLA_CONTRACT,
                self::TYPE_INTERNATIONAL_SERVICE,
                self::TYPE_MASTER_SERVICE,
                self::TYPE_DATA_PROCESSING,
                self::TYPE_PROFESSIONAL_SERVICES,
                self::TYPE_SUPPORT_CONTRACT,
            ]),
            'status' => 'required|in:' . implode(',', [
                self::STATUS_DRAFT,
                self::STATUS_PENDING_REVIEW,
                self::STATUS_UNDER_NEGOTIATION,
                self::STATUS_PENDING_SIGNATURE,
                self::STATUS_SIGNED,
                self::STATUS_ACTIVE,
                self::STATUS_SUSPENDED,
                self::STATUS_TERMINATED,
                self::STATUS_EXPIRED,
                self::STATUS_CANCELLED,
            ]),
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'term_months' => 'nullable|integer|min:1|max:120',
            'renewal_type' => 'required|in:' . implode(',', [
                self::RENEWAL_NONE,
                self::RENEWAL_MANUAL,
                self::RENEWAL_AUTOMATIC,
                self::RENEWAL_NEGOTIATED,
            ]),
            'contract_value' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'client_id' => 'required|integer|exists:clients,id',
            'quote_id' => 'nullable|integer|exists:quotes,id',
        ];
    }

    /**
     * Get available contract types.
     */
    public static function getAvailableTypes(): array
    {
        return [
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
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_UNDER_NEGOTIATION => 'Under Negotiation',
            self::STATUS_PENDING_SIGNATURE => 'Pending Signature',
            self::STATUS_SIGNED => 'Signed',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_TERMINATED => 'Terminated',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

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
            'type' => 'creation'
        ];

        // Status changes (could be tracked via activity log if available)
        if ($this->signed_at) {
            $history[] = [
                'description' => 'Contract signed',
                'icon' => 'signature',
                'date' => $this->signed_at,
                'user' => $this->signer?->name ?? 'Client',
                'type' => 'signature'
            ];
        }

        if ($this->executed_at) {
            $history[] = [
                'description' => 'Contract executed',
                'icon' => 'check',
                'date' => $this->executed_at,
                'user' => $this->approver?->name ?? 'System',
                'type' => 'execution'
            ];
        }

        if ($this->terminated_at) {
            $history[] = [
                'description' => 'Contract terminated',
                'icon' => 'times',
                'date' => $this->terminated_at,
                'user' => 'System',
                'type' => 'termination',
                'reason' => $this->termination_reason
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
                'details' => $amendment->reason ?? 'Contract modification'
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
                    'type' => 'signature'
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

        // Auto-generate contract number if not provided
        static::creating(function ($contract) {
            if (!$contract->contract_number) {
                // Generate a simple incremental contract number
                $lastContract = static::where('company_id', $contract->company_id)
                    ->whereNotNull('contract_number')
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = 1;
                if ($lastContract && preg_match('/CNT-(\d+)/', $lastContract->contract_number, $matches)) {
                    $nextNumber = (int)$matches[1] + 1;
                }

                $contract->contract_number = 'CNT-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }


            if (!$contract->currency_code) {
                $contract->currency_code = 'USD';
            }


            if (!$contract->signature_status) {
                $contract->signature_status = self::SIGNATURE_PENDING;
            }
        });

        // Update status based on dates
        static::retrieved(function ($contract) {
            if ($contract->isExpired() && $contract->status === self::STATUS_ACTIVE) {
                $contract->update(['status' => self::STATUS_EXPIRED]);
            }
        });
    }
}