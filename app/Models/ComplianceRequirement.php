<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * ComplianceRequirement Model
 * 
 * Tracks compliance requirements for contracts including legal,
 * regulatory, and business requirements with status monitoring.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $contract_id
 * @property string $requirement_type
 * @property string $title
 * @property string|null $description
 * @property string $category
 * @property string $priority
 * @property Carbon|null $due_date
 * @property string $status
 * @property Carbon|null $last_checked_at
 * @property array|null $requirements_data
 * @property array|null $compliance_criteria
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class ComplianceRequirement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'contract_id',
        'requirement_type',
        'title',
        'description',
        'category',
        'priority',
        'due_date',
        'status',
        'last_checked_at',
        'requirements_data',
        'compliance_criteria',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'last_checked_at' => 'datetime',
        'requirements_data' => 'array',
        'compliance_criteria' => 'array',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLIANT = 'compliant';
    const STATUS_NON_COMPLIANT = 'non_compliant';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_EXEMPT = 'exempt';

    // Category constants
    const CATEGORY_LEGAL = 'legal';
    const CATEGORY_REGULATORY = 'regulatory';
    const CATEGORY_BUSINESS = 'business';
    const CATEGORY_TECHNICAL = 'technical';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_DATA_PROTECTION = 'data_protection';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Requirement type constants
    const TYPE_GDPR_COMPLIANCE = 'gdpr_compliance';
    const TYPE_SOX_COMPLIANCE = 'sox_compliance';
    const TYPE_DATA_RETENTION = 'data_retention';
    const TYPE_SIGNATURE_VALIDATION = 'signature_validation';
    const TYPE_DOCUMENT_ARCHIVAL = 'document_archival';
    const TYPE_AUDIT_TRAIL = 'audit_trail';

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(ComplianceCheck::class);
    }

    public function latestCheck(): HasOne
    {
        return $this->hasOne(ComplianceCheck::class)->latestOfMany('checked_at');
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

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', [self::STATUS_COMPLIANT, self::STATUS_EXEMPT]);
    }

    public function scopeDueSoon($query, int $days = 30)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
                    ->whereNotIn('status', [self::STATUS_COMPLIANT, self::STATUS_EXEMPT]);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    /**
     * Accessors & Mutators
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLIANT => 'Compliant',
            self::STATUS_NON_COMPLIANT => 'Non-Compliant',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_EXEMPT => 'Exempt',
            default => 'Unknown'
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_LEGAL => 'Legal',
            self::CATEGORY_REGULATORY => 'Regulatory',
            self::CATEGORY_BUSINESS => 'Business',
            self::CATEGORY_TECHNICAL => 'Technical',
            self::CATEGORY_FINANCIAL => 'Financial',
            self::CATEGORY_DATA_PROTECTION => 'Data Protection',
            default => ucwords(str_replace('_', ' ', $this->category))
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
            default => ucfirst($this->priority)
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, [self::STATUS_COMPLIANT, self::STATUS_EXEMPT]);
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    public function getRiskLevelAttribute(): string
    {
        if ($this->status === self::STATUS_NON_COMPLIANT) {
            return match($this->priority) {
                self::PRIORITY_CRITICAL => 'critical',
                self::PRIORITY_HIGH => 'high',
                default => 'medium'
            };
        }

        if ($this->is_overdue) {
            return 'high';
        }

        if ($this->days_until_due !== null && $this->days_until_due <= 7) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if requirement needs attention
     */
    public function needsAttention(): bool
    {
        return $this->status === self::STATUS_NON_COMPLIANT || 
               $this->is_overdue ||
               ($this->days_until_due !== null && $this->days_until_due <= 7);
    }

    /**
     * Get compliance percentage
     */
    public function getCompliancePercentage(): float
    {
        $latestCheck = $this->latestCheck;
        
        if (!$latestCheck) {
            return 0;
        }

        return match($latestCheck->status) {
            'compliant' => 100,
            'partial_compliant' => $latestCheck->compliance_score ?? 50,
            'non_compliant' => 0,
            default => 25
        };
    }

    /**
     * Mark as compliant
     */
    public function markCompliant(string $notes = null): bool
    {
        $this->update([
            'status' => self::STATUS_COMPLIANT,
            'last_checked_at' => now(),
        ]);

        // Create compliance check record
        $this->checks()->create([
            'company_id' => $this->company_id,
            'contract_id' => $this->contract_id,
            'check_type' => 'manual',
            'status' => 'compliant',
            'findings' => $notes,
            'checked_by' => auth()->id(),
            'checked_at' => now(),
            'compliance_score' => 100,
        ]);

        return true;
    }

    /**
     * Mark as non-compliant
     */
    public function markNonCompliant(string $findings, array $recommendations = []): bool
    {
        $this->update([
            'status' => self::STATUS_NON_COMPLIANT,
            'last_checked_at' => now(),
        ]);

        // Create compliance check record
        $this->checks()->create([
            'company_id' => $this->company_id,
            'contract_id' => $this->contract_id,
            'check_type' => 'manual',
            'status' => 'non_compliant',
            'findings' => $findings,
            'recommendations' => $recommendations,
            'checked_by' => auth()->id(),
            'checked_at' => now(),
            'compliance_score' => 0,
            'risk_level' => 'high',
        ]);

        return true;
    }

    /**
     * Get requirement summary
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category_label,
            'priority' => $this->priority_label,
            'status' => $this->status_label,
            'risk_level' => $this->risk_level,
            'due_date' => $this->due_date,
            'is_overdue' => $this->is_overdue,
            'days_until_due' => $this->days_until_due,
            'compliance_percentage' => $this->getCompliancePercentage(),
            'needs_attention' => $this->needsAttention(),
            'last_checked' => $this->last_checked_at,
            'total_checks' => $this->checks()->count(),
        ];
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($requirement) {
            if (!$requirement->company_id && auth()->user()) {
                $requirement->company_id = auth()->user()->company_id;
            }
            
            if (!$requirement->created_by && auth()->user()) {
                $requirement->created_by = auth()->id();
            }
        });
    }
}