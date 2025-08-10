<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * ComplianceCheck Model
 * 
 * Records individual compliance checks performed on requirements.
 * Tracks findings, recommendations, and compliance scores.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $contract_id
 * @property int $compliance_requirement_id
 * @property string $check_type
 * @property string $status
 * @property string|null $findings
 * @property array|null $recommendations
 * @property array|null $evidence_documents
 * @property int|null $checked_by
 * @property Carbon $checked_at
 * @property Carbon|null $next_check_date
 * @property float|null $compliance_score
 * @property string $risk_level
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ComplianceCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'contract_id',
        'compliance_requirement_id',
        'check_type',
        'status',
        'findings',
        'recommendations',
        'evidence_documents',
        'checked_by',
        'checked_at',
        'next_check_date',
        'compliance_score',
        'risk_level',
        'metadata',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'next_check_date' => 'datetime',
        'recommendations' => 'array',
        'evidence_documents' => 'array',
        'compliance_score' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_COMPLIANT = 'compliant';
    const STATUS_NON_COMPLIANT = 'non_compliant';
    const STATUS_PARTIAL_COMPLIANT = 'partial_compliant';
    const STATUS_NEEDS_REVIEW = 'needs_review';
    const STATUS_PENDING = 'pending';

    // Check type constants
    const TYPE_MANUAL = 'manual';
    const TYPE_AUTOMATED = 'automated';
    const TYPE_SCHEDULED = 'scheduled';
    const TYPE_TRIGGERED = 'triggered';

    // Risk level constants
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function complianceRequirement(): BelongsTo
    {
        return $this->belongsTo(ComplianceRequirement::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByRiskLevel($query, string $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('check_type', $type);
    }

    public function scopeCompliant($query)
    {
        return $query->where('status', self::STATUS_COMPLIANT);
    }

    public function scopeNonCompliant($query)
    {
        return $query->where('status', self::STATUS_NON_COMPLIANT);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('checked_at', '>=', now()->subDays($days));
    }

    /**
     * Accessors & Mutators
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLIANT => 'Compliant',
            self::STATUS_NON_COMPLIANT => 'Non-Compliant',
            self::STATUS_PARTIAL_COMPLIANT => 'Partially Compliant',
            self::STATUS_NEEDS_REVIEW => 'Needs Review',
            self::STATUS_PENDING => 'Pending',
            default => 'Unknown'
        };
    }

    public function getCheckTypeLabelAttribute(): string
    {
        return match($this->check_type) {
            self::TYPE_MANUAL => 'Manual Check',
            self::TYPE_AUTOMATED => 'Automated Check',
            self::TYPE_SCHEDULED => 'Scheduled Check',
            self::TYPE_TRIGGERED => 'Triggered Check',
            default => ucfirst($this->check_type)
        };
    }

    public function getRiskLevelLabelAttribute(): string
    {
        return match($this->risk_level) {
            self::RISK_LOW => 'Low Risk',
            self::RISK_MEDIUM => 'Medium Risk',
            self::RISK_HIGH => 'High Risk',
            self::RISK_CRITICAL => 'Critical Risk',
            default => ucfirst($this->risk_level)
        };
    }

    public function getRiskColorAttribute(): string
    {
        return match($this->risk_level) {
            self::RISK_LOW => 'success',
            self::RISK_MEDIUM => 'warning',
            self::RISK_HIGH => 'danger',
            self::RISK_CRITICAL => 'dark',
            default => 'secondary'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLIANT => 'success',
            self::STATUS_NON_COMPLIANT => 'danger',
            self::STATUS_PARTIAL_COMPLIANT => 'warning',
            self::STATUS_NEEDS_REVIEW => 'info',
            self::STATUS_PENDING => 'secondary',
            default => 'secondary'
        };
    }

    public function getCompliancePercentageAttribute(): float
    {
        if ($this->compliance_score !== null) {
            return $this->compliance_score;
        }

        return match($this->status) {
            self::STATUS_COMPLIANT => 100,
            self::STATUS_PARTIAL_COMPLIANT => 50,
            self::STATUS_NON_COMPLIANT => 0,
            default => 25
        };
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if this is a passing check
     */
    public function isPassing(): bool
    {
        return $this->status === self::STATUS_COMPLIANT || 
               ($this->status === self::STATUS_PARTIAL_COMPLIANT && 
                $this->compliance_score >= 75);
    }

    /**
     * Check if this check indicates high risk
     */
    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, [self::RISK_HIGH, self::RISK_CRITICAL]) ||
               $this->status === self::STATUS_NON_COMPLIANT;
    }

    /**
     * Check if next check is overdue
     */
    public function isNextCheckOverdue(): bool
    {
        return $this->next_check_date && $this->next_check_date->isPast();
    }

    /**
     * Get summary of this check
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'requirement_title' => $this->complianceRequirement->title ?? 'Unknown',
            'check_type' => $this->check_type_label,
            'status' => $this->status_label,
            'risk_level' => $this->risk_level_label,
            'compliance_score' => $this->compliance_percentage,
            'checked_at' => $this->checked_at,
            'checked_by' => $this->checkedBy->name ?? 'System',
            'is_passing' => $this->isPassing(),
            'is_high_risk' => $this->isHighRisk(),
            'next_check_date' => $this->next_check_date,
            'is_next_check_overdue' => $this->isNextCheckOverdue(),
            'findings_count' => $this->findings ? str_word_count($this->findings) : 0,
            'recommendations_count' => count($this->recommendations ?? []),
        ];
    }

    /**
     * Create follow-up check
     */
    public function createFollowUp(array $checkData): self
    {
        return static::create(array_merge([
            'company_id' => $this->company_id,
            'contract_id' => $this->contract_id,
            'compliance_requirement_id' => $this->compliance_requirement_id,
            'check_type' => self::TYPE_SCHEDULED,
            'checked_by' => auth()->id(),
            'checked_at' => now(),
        ], $checkData));
    }

    /**
     * Export check details for reporting
     */
    public function exportDetails(): array
    {
        return [
            'check_id' => $this->id,
            'requirement' => $this->complianceRequirement->title ?? 'Unknown',
            'contract' => $this->contract->contract_number ?? 'Unknown',
            'check_type' => $this->check_type_label,
            'status' => $this->status_label,
            'risk_level' => $this->risk_level_label,
            'compliance_score' => $this->compliance_percentage,
            'checked_at' => $this->checked_at->format('Y-m-d H:i:s'),
            'checked_by' => $this->checkedBy->name ?? 'System',
            'findings' => $this->findings,
            'recommendations' => $this->recommendations,
            'evidence_count' => count($this->evidence_documents ?? []),
            'next_check_date' => $this->next_check_date?->format('Y-m-d'),
        ];
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($check) {
            if (!$check->company_id && auth()->user()) {
                $check->company_id = auth()->user()->company_id;
            }
            
            if (!$check->checked_at) {
                $check->checked_at = now();
            }

            if (!$check->checked_by && auth()->user()) {
                $check->checked_by = auth()->id();
            }
        });

        static::created(function ($check) {
            // Update the related requirement's last checked date
            if ($check->complianceRequirement) {
                $check->complianceRequirement->update([
                    'last_checked_at' => $check->checked_at,
                ]);
            }
        });
    }
}