<?php

namespace App\Domains\Contract\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ContractApproval Model
 *
 * Manages contract approval workflow tracking with multi-level approvals,
 * role-based routing, escalation, and comprehensive audit trails.
 *
 * @property int $id
 * @property int $company_id
 * @property int $contract_id
 * @property string $approval_level
 * @property int|null $approver_user_id
 * @property string|null $approver_role
 * @property Carbon|null $required_by
 * @property int $approval_order
 * @property bool $is_required
 * @property string $status
 * @property Carbon|null $submitted_at
 * @property int|null $submitted_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $requested_at
 * @property Carbon|null $escalated_at
 * @property int|null $escalated_to
 * @property int|null $escalated_from
 * @property string|null $comments
 * @property array|null $conditions
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class ContractApproval extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'contract_id',
        'approval_level',
        'approver_user_id',
        'approver_role',
        'required_by',
        'approval_order',
        'is_required',
        'status',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'rejected_at',
        'requested_at',
        'escalated_at',
        'escalated_to',
        'escalated_from',
        'comments',
        'conditions',
        'metadata',
    ];

    protected $casts = [
        'required_by' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'requested_at' => 'datetime',
        'escalated_at' => 'datetime',
        'is_required' => 'boolean',
        'conditions' => 'array',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_CHANGES_REQUESTED = 'changes_requested';

    const STATUS_ESCALATED = 'escalated';

    const STATUS_EXPIRED = 'expired';

    // Approval level constants
    const LEVEL_MANAGER = 'manager';

    const LEVEL_DIRECTOR = 'director';

    const LEVEL_EXECUTIVE = 'executive';

    const LEVEL_LEGAL = 'legal';

    const LEVEL_FINANCE = 'finance';

    const LEVEL_TECHNICAL = 'technical';

    const LEVEL_ESCALATED = 'escalated';

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

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function escalatedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function escalatedFromApproval(): BelongsTo
    {
        return $this->belongsTo(ContractApproval::class, 'escalated_from');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('required_by', '<', now());
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('approver_user_id', $user->id)
                ->orWhere('approver_role', $user->role);
        });
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('approval_level', $level);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Accessors & Mutators
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CHANGES_REQUESTED => 'Changes Requested',
            self::STATUS_ESCALATED => 'Escalated',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown Status'
        };
    }

    public function getApprovalLevelLabelAttribute(): string
    {
        return match ($this->approval_level) {
            self::LEVEL_MANAGER => 'Manager Approval',
            self::LEVEL_DIRECTOR => 'Director Approval',
            self::LEVEL_EXECUTIVE => 'Executive Approval',
            self::LEVEL_LEGAL => 'Legal Review',
            self::LEVEL_FINANCE => 'Finance Approval',
            self::LEVEL_TECHNICAL => 'Technical Review',
            self::LEVEL_ESCALATED => 'Escalated Approval',
            default => ucwords(str_replace('_', ' ', $this->approval_level))
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING &&
               $this->required_by &&
               $this->required_by->isPast();
    }

    public function getApprovalTimeAttribute(): ?int
    {
        if (! $this->submitted_at) {
            return null;
        }

        $completedAt = $this->approved_at ?? $this->rejected_at ?? $this->requested_at;

        return $completedAt ? $this->submitted_at->diffInHours($completedAt) : null;
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (! $this->required_by || $this->status !== self::STATUS_PENDING) {
            return null;
        }

        return now()->diffInDays($this->required_by, false);
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if approval can be processed by user
     */
    public function canBeProcessedBy(User $user): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        return $this->approver_user_id === $user->id ||
               $this->approver_role === $user->role;
    }

    /**
     * Mark approval as approved
     */
    public function approve(?string $comments = null, array $conditions = []): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'comments' => $comments,
            'conditions' => $conditions,
        ]);

        $this->logApprovalAction('approved', $comments);

        return true;
    }

    /**
     * Mark approval as rejected
     */
    public function reject(?string $comments = null): bool
    {
        if (! $this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'comments' => $comments,
        ]);

        $this->logApprovalAction('rejected', $comments);

        return true;
    }

    /**
     * Request changes to contract
     */
    public function requestChanges(?string $comments = null, array $conditions = []): bool
    {
        if (! $this->canRequestChanges()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CHANGES_REQUESTED,
            'requested_at' => now(),
            'comments' => $comments,
            'conditions' => $conditions,
        ]);

        $this->logApprovalAction('requested_changes', $comments);

        return true;
    }

    /**
     * Escalate approval to higher level
     */
    public function escalate(int $escalatedToUserId, ?string $comments = null): bool
    {
        if (! $this->canBeEscalated()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_ESCALATED,
            'escalated_at' => now(),
            'escalated_to' => $escalatedToUserId,
            'comments' => $comments,
        ]);

        $this->logApprovalAction('escalated', $comments);

        return true;
    }

    /**
     * Mark approval as expired
     */
    public function markAsExpired(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        $this->logApprovalAction('expired');

        return true;
    }

    /**
     * Reset approval to pending status
     */
    public function reset(): bool
    {
        if ($this->status === self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PENDING,
            'approved_at' => null,
            'rejected_at' => null,
            'requested_at' => null,
            'escalated_at' => null,
            'escalated_to' => null,
            'comments' => null,
            'conditions' => null,
        ]);

        $this->logApprovalAction('reset');

        return true;
    }

    /**
     * Validation methods
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeRejected(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canRequestChanges(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeEscalated(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get approval priority based on level and requirements
     */
    public function getPriority(): int
    {
        $levelPriorities = [
            self::LEVEL_EXECUTIVE => 1,
            self::LEVEL_DIRECTOR => 2,
            self::LEVEL_MANAGER => 3,
            self::LEVEL_LEGAL => 4,
            self::LEVEL_FINANCE => 5,
            self::LEVEL_TECHNICAL => 6,
            self::LEVEL_ESCALATED => 0, // Highest priority
        ];

        $basePriority = $levelPriorities[$this->approval_level] ?? 10;

        // Adjust priority based on overdue status
        if ($this->is_overdue) {
            $basePriority -= 5;
        }

        // Adjust priority based on required status
        if ($this->is_required) {
            $basePriority -= 2;
        }

        return max(1, $basePriority);
    }

    /**
     * Get approval workflow statistics
     */
    public static function getWorkflowStatistics(int $companyId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = static::where('company_id', $companyId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $approvals = $query->get();

        $stats = [
            'total_approvals' => $approvals->count(),
            'pending_approvals' => $approvals->where('status', self::STATUS_PENDING)->count(),
            'approved_count' => $approvals->where('status', self::STATUS_APPROVED)->count(),
            'rejected_count' => $approvals->where('status', self::STATUS_REJECTED)->count(),
            'overdue_count' => $approvals->where('is_overdue', true)->count(),
            'average_approval_time' => 0,
            'approval_rate' => 0,
            'by_level' => [],
            'by_status' => [],
        ];

        // Calculate average approval time
        $completedApprovals = $approvals->whereNotNull('approval_time');
        if ($completedApprovals->count() > 0) {
            $stats['average_approval_time'] = $completedApprovals->avg('approval_time');
        }

        // Calculate approval rate
        if ($stats['total_approvals'] > 0) {
            $stats['approval_rate'] = ($stats['approved_count'] / $stats['total_approvals']) * 100;
        }

        // Group by level
        $stats['by_level'] = $approvals->groupBy('approval_level')->map->count()->toArray();

        // Group by status
        $stats['by_status'] = $approvals->groupBy('status')->map->count()->toArray();

        return $stats;
    }

    /**
     * Log approval actions for audit trail
     */
    protected function logApprovalAction(string $action, ?string $comments = null): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['audit_trail'][] = [
            'action' => $action,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'comments' => $comments,
        ];

        $this->update(['metadata' => $metadata]);
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($approval) {
            if (! $approval->company_id && auth()->user()) {
                $approval->company_id = auth()->user()->company_id;
            }
        });

        static::created(function ($approval) {
            $approval->logApprovalAction('created');
        });
    }
}
