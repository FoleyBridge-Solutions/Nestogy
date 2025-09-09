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
 * Tax Exemption Model
 * 
 * Stores client-specific tax exemptions and certificates.
 * Handles various types of tax exemptions for VoIP services.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property int|null $tax_jurisdiction_id
 * @property int|null $tax_category_id
 * @property string $exemption_type
 * @property string $exemption_name
 * @property string|null $certificate_number
 * @property string|null $issuing_authority
 * @property string|null $issuing_state
 * @property \Illuminate\Support\Carbon|null $issue_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property bool $is_blanket_exemption
 * @property array|null $applicable_tax_types
 * @property array|null $applicable_services
 * @property array|null $exemption_conditions
 * @property float|null $exemption_percentage
 * @property float|null $maximum_exemption_amount
 * @property string $status
 * @property string|null $verification_status
 * @property \Illuminate\Support\Carbon|null $last_verified_at
 * @property string|null $verification_notes
 * @property string|null $certificate_file_path
 * @property array|null $supporting_documents
 * @property bool $auto_apply
 * @property int $priority
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $verified_by
 */
class TaxExemption extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'tax_exemptions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'tax_jurisdiction_id',
        'tax_category_id',
        'exemption_type',
        'exemption_name',
        'certificate_number',
        'issuing_authority',
        'issuing_state',
        'issue_date',
        'expiry_date',
        'is_blanket_exemption',
        'applicable_tax_types',
        'applicable_services',
        'exemption_conditions',
        'exemption_percentage',
        'maximum_exemption_amount',
        'status',
        'verification_status',
        'last_verified_at',
        'verification_notes',
        'certificate_file_path',
        'supporting_documents',
        'auto_apply',
        'priority',
        'metadata',
        'created_by',
        'verified_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'tax_jurisdiction_id' => 'integer',
        'tax_category_id' => 'integer',
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_blanket_exemption' => 'boolean',
        'applicable_tax_types' => 'array',
        'applicable_services' => 'array',
        'exemption_conditions' => 'array',
        'exemption_percentage' => 'decimal:2',
        'maximum_exemption_amount' => 'decimal:2',
        'last_verified_at' => 'datetime',
        'supporting_documents' => 'array',
        'auto_apply' => 'boolean',
        'priority' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_by' => 'integer',
        'verified_by' => 'integer',
    ];

    /**
     * Exemption type enumeration
     */
    const TYPE_RESALE = 'resale';
    const TYPE_NON_PROFIT = 'non_profit';
    const TYPE_GOVERNMENT = 'government';
    const TYPE_EDUCATIONAL = 'educational';
    const TYPE_RELIGIOUS = 'religious';
    const TYPE_AGRICULTURAL = 'agricultural';
    const TYPE_MANUFACTURING = 'manufacturing';
    const TYPE_MEDICAL = 'medical';
    const TYPE_DISABILITY = 'disability';
    const TYPE_INTERSTATE = 'interstate';
    const TYPE_INTERNATIONAL = 'international';
    const TYPE_WHOLESALE = 'wholesale';
    const TYPE_CARRIER_ACCESS = 'carrier_access';
    const TYPE_CUSTOM = 'custom';

    /**
     * Status enumeration
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_REVOKED = 'revoked';
    const STATUS_PENDING = 'pending';

    /**
     * Verification status enumeration
     */
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_REJECTED = 'rejected';
    const VERIFICATION_EXPIRED = 'expired';
    const VERIFICATION_NEEDS_RENEWAL = 'needs_renewal';

    /**
     * Get the client this exemption belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the jurisdiction this exemption applies to.
     */
    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }

    /**
     * Get the category this exemption applies to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class, 'tax_category_id');
    }

    /**
     * Get the user who created this exemption.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who verified this exemption.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get usage records for this exemption.
     */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(TaxExemptionUsage::class);
    }

    /**
     * Check if the exemption is currently valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->verification_status !== self::VERIFICATION_VERIFIED) {
            return false;
        }

        if ($this->expiry_date && Carbon::now()->gt($this->expiry_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the exemption is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && Carbon::now()->gt($this->expiry_date);
    }

    /**
     * Check if the exemption is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        $warningDate = Carbon::now()->addDays($days);
        return $this->expiry_date->lte($warningDate) && !$this->isExpired();
    }

    /**
     * Check if the exemption applies to a specific tax type.
     */
    public function appliesToTaxType(string $taxType): bool
    {
        if ($this->is_blanket_exemption) {
            return true;
        }

        if (empty($this->applicable_tax_types)) {
            return true;
        }

        return in_array($taxType, $this->applicable_tax_types);
    }

    /**
     * Check if the exemption applies to a specific service.
     */
    public function appliesToService(string $serviceType): bool
    {
        if ($this->is_blanket_exemption) {
            return true;
        }

        if (empty($this->applicable_services)) {
            return true;
        }

        return in_array($serviceType, $this->applicable_services);
    }

    /**
     * Check if the exemption applies to a specific jurisdiction.
     */
    public function appliesToJurisdiction(int $jurisdictionId): bool
    {
        if ($this->is_blanket_exemption && !$this->tax_jurisdiction_id) {
            return true;
        }

        return $this->tax_jurisdiction_id === $jurisdictionId;
    }

    /**
     * Calculate exemption amount for a given tax amount.
     */
    public function calculateExemptionAmount(float $taxAmount, array $context = []): float
    {
        if (!$this->isValid()) {
            return 0.0;
        }

        // Check if conditions are met
        if (!$this->meetsConditions($context)) {
            return 0.0;
        }

        $exemptionAmount = 0.0;

        if ($this->exemption_percentage !== null) {
            $exemptionAmount = $taxAmount * ($this->exemption_percentage / 100);
        } else {
            // Full exemption
            $exemptionAmount = $taxAmount;
        }

        // Apply maximum exemption limit if set
        if ($this->maximum_exemption_amount !== null) {
            $exemptionAmount = min($exemptionAmount, $this->maximum_exemption_amount);
        }

        return round($exemptionAmount, 4);
    }

    /**
     * Check if exemption conditions are met.
     */
    protected function meetsConditions(array $context): bool
    {
        if (empty($this->exemption_conditions)) {
            return true;
        }

        foreach ($this->exemption_conditions as $condition) {
            $conditionType = $condition['type'] ?? '';
            $conditionValue = $condition['value'] ?? '';
            $operator = $condition['operator'] ?? '=';

            switch ($conditionType) {
                case 'minimum_amount':
                    $amount = $context['amount'] ?? 0;
                    if (!$this->compareValues($amount, $conditionValue, $operator)) {
                        return false;
                    }
                    break;

                case 'service_type':
                    $serviceType = $context['service_type'] ?? '';
                    if (!in_array($serviceType, (array)$conditionValue)) {
                        return false;
                    }
                    break;

                case 'usage_limit':
                    $currentUsage = $this->getCurrentMonthUsage();
                    if ($currentUsage >= $conditionValue) {
                        return false;
                    }
                    break;

                case 'date_range':
                    if (!$this->isWithinDateRange($condition)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Compare values with different operators.
     */
    protected function compareValues($actual, $expected, string $operator): bool
    {
        switch ($operator) {
            case '=':
            case '==':
                return $actual == $expected;
            case '>':
                return $actual > $expected;
            case '>=':
                return $actual >= $expected;
            case '<':
                return $actual < $expected;
            case '<=':
                return $actual <= $expected;
            case '!=':
                return $actual != $expected;
            default:
                return false;
        }
    }

    /**
     * Check if current date is within specified date range.
     */
    protected function isWithinDateRange(array $condition): bool
    {
        $now = Carbon::now();
        $startDate = isset($condition['start_date']) ? Carbon::parse($condition['start_date']) : null;
        $endDate = isset($condition['end_date']) ? Carbon::parse($condition['end_date']) : null;

        if ($startDate && $now->lt($startDate)) {
            return false;
        }

        if ($endDate && $now->gt($endDate)) {
            return false;
        }

        return true;
    }

    /**
     * Get current month usage count.
     */
    protected function getCurrentMonthUsage(): int
    {
        return $this->usageRecords()
                   ->whereYear('created_at', Carbon::now()->year)
                   ->whereMonth('created_at', Carbon::now()->month)
                   ->count();
    }

    /**
     * Record usage of this exemption.
     */
    public function recordUsage(array $data): void
    {
        $this->usageRecords()->create(array_merge($data, [
            'company_id' => $this->company_id,
            'used_at' => now(),
        ]));
    }

    /**
     * Mark exemption as verified.
     */
    public function markAsVerified(int $verifiedBy, string $notes = null): void
    {
        $this->update([
            'verification_status' => self::VERIFICATION_VERIFIED,
            'last_verified_at' => now(),
            'verification_notes' => $notes,
            'verified_by' => $verifiedBy,
        ]);
    }

    /**
     * Mark exemption as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
            'verification_status' => self::VERIFICATION_EXPIRED,
        ]);
    }

    /**
     * Get exemption type label.
     */
    public function getExemptionTypeLabel(): string
    {
        $labels = [
            self::TYPE_RESALE => 'Resale Certificate',
            self::TYPE_NON_PROFIT => 'Non-Profit Organization',
            self::TYPE_GOVERNMENT => 'Government Entity',
            self::TYPE_EDUCATIONAL => 'Educational Institution',
            self::TYPE_RELIGIOUS => 'Religious Organization',
            self::TYPE_AGRICULTURAL => 'Agricultural Use',
            self::TYPE_MANUFACTURING => 'Manufacturing',
            self::TYPE_MEDICAL => 'Medical/Healthcare',
            self::TYPE_DISABILITY => 'Disability Exemption',
            self::TYPE_INTERSTATE => 'Interstate Commerce',
            self::TYPE_INTERNATIONAL => 'International Commerce',
            self::TYPE_WHOLESALE => 'Wholesale',
            self::TYPE_CARRIER_ACCESS => 'Carrier Access',
            self::TYPE_CUSTOM => 'Custom Exemption',
        ];

        return $labels[$this->exemption_type] ?? ucfirst(str_replace('_', ' ', $this->exemption_type));
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_REVOKED => 'Revoked',
            self::STATUS_PENDING => 'Pending',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get verification status label.
     */
    public function getVerificationStatusLabel(): string
    {
        $labels = [
            self::VERIFICATION_PENDING => 'Pending Verification',
            self::VERIFICATION_VERIFIED => 'Verified',
            self::VERIFICATION_REJECTED => 'Rejected',
            self::VERIFICATION_EXPIRED => 'Expired',
            self::VERIFICATION_NEEDS_RENEWAL => 'Needs Renewal',
        ];

        return $labels[$this->verification_status] ?? ucfirst(str_replace('_', ' ', $this->verification_status));
    }

    /**
     * Scope to get active exemptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get verified exemptions.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    /**
     * Scope to get valid exemptions.
     */
    public function scopeValid($query)
    {
        return $query->active()
                    ->verified()
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>', Carbon::now());
                    });
    }

    /**
     * Scope to get expired exemptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', Carbon::now());
    }

    /**
     * Scope to get exemptions expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        $warningDate = Carbon::now()->addDays($days);
        
        return $query->whereBetween('expiry_date', [Carbon::now(), $warningDate]);
    }

    /**
     * Scope to get exemptions by client.
     */
    public function scopeByClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get exemptions by jurisdiction.
     */
    public function scopeByJurisdiction($query, int $jurisdictionId)
    {
        return $query->where(function ($q) use ($jurisdictionId) {
            $q->where('tax_jurisdiction_id', $jurisdictionId)
              ->orWhere('is_blanket_exemption', true);
        });
    }

    /**
     * Scope to get exemptions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('exemption_type', $type);
    }

    /**
     * Scope to get auto-apply exemptions.
     */
    public function scopeAutoApply($query)
    {
        return $query->where('auto_apply', true);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrderByPriority($query, string $direction = 'asc')
    {
        return $query->orderBy('priority', $direction);
    }

    /**
     * Get validation rules for exemption creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'client_id' => 'required|integer|exists:clients,id',
            'tax_jurisdiction_id' => 'nullable|integer|exists:tax_jurisdictions,id',
            'tax_category_id' => 'nullable|integer|exists:tax_categories,id',
            'exemption_type' => 'required|in:resale,non_profit,government,educational,religious,agricultural,manufacturing,medical,disability,interstate,international,wholesale,carrier_access,custom',
            'exemption_name' => 'required|string|max:255',
            'certificate_number' => 'nullable|string|max:100',
            'issuing_authority' => 'nullable|string|max:255',
            'issuing_state' => 'nullable|string|size:2',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'is_blanket_exemption' => 'boolean',
            'applicable_tax_types' => 'nullable|array',
            'applicable_services' => 'nullable|array',
            'exemption_conditions' => 'nullable|array',
            'exemption_percentage' => 'nullable|numeric|min:0|max:100',
            'maximum_exemption_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,expired,suspended,revoked,pending',
            'verification_status' => 'required|in:pending,verified,rejected,expired,needs_renewal',
            'verification_notes' => 'nullable|string',
            'certificate_file_path' => 'nullable|string|max:255',
            'supporting_documents' => 'nullable|array',
            'auto_apply' => 'boolean',
            'priority' => 'integer|min:0|max:999',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get available exemption types.
     */
    public static function getAvailableExemptionTypes(): array
    {
        return [
            self::TYPE_RESALE => 'Resale Certificate',
            self::TYPE_NON_PROFIT => 'Non-Profit Organization',
            self::TYPE_GOVERNMENT => 'Government Entity',
            self::TYPE_EDUCATIONAL => 'Educational Institution',
            self::TYPE_RELIGIOUS => 'Religious Organization',
            self::TYPE_AGRICULTURAL => 'Agricultural Use',
            self::TYPE_MANUFACTURING => 'Manufacturing',
            self::TYPE_MEDICAL => 'Medical/Healthcare',
            self::TYPE_DISABILITY => 'Disability Exemption',
            self::TYPE_INTERSTATE => 'Interstate Commerce',
            self::TYPE_INTERNATIONAL => 'International Commerce',
            self::TYPE_WHOLESALE => 'Wholesale',
            self::TYPE_CARRIER_ACCESS => 'Carrier Access',
            self::TYPE_CUSTOM => 'Custom Exemption',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($exemption) {
            if (!isset($exemption->priority)) {
                $exemption->priority = 100;
            }

            if (!isset($exemption->created_by)) {
                $exemption->created_by = auth()->id();
            }
        });

        // Auto-expire exemptions
        static::retrieved(function ($exemption) {
            if ($exemption->isExpired() && $exemption->status === self::STATUS_ACTIVE) {
                $exemption->markAsExpired();
            }
        });
    }
}