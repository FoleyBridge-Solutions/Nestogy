<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Credit Note Approval Model
 *
 * Manages multi-level approval workflows for credit notes,
 * supporting delegation, escalation, emergency approvals,
 * and comprehensive SLA tracking.
 */
class CreditNoteApproval extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'credit_note_approvals';

    protected $fillable = [
        'company_id', 'credit_note_id', 'approver_id', 'requested_by', 'approval_level',
        'sequence_order', 'approval_threshold', 'approval_role', 'status', 'approval_type',
        'approval_reason', 'rejection_reason', 'comments', 'decision_factors',
        'approval_criteria', 'amount_based', 'risk_based', 'client_based', 'reason_based',
        'risk_score', 'risk_factors', 'risk_level', 'emergency_approval', 'approval_bypassed',
        'bypass_reason', 'bypassed_by', 'bypassed_at', 'escalated', 'escalated_to',
        'escalated_by', 'escalated_at', 'escalation_reason', 'delegated', 'delegated_from',
        'delegated_to', 'delegated_at', 'delegation_reason', 'delegation_expiry',
        'sla_hours', 'sla_deadline', 'sla_breached', 'response_time_hours',
        'auto_approved', 'auto_approval_rules', 'auto_approval_reason',
        'notification_sent', 'notification_sent_at', 'reminder_count', 'last_reminder_sent',
        'notification_history', 'requested_at', 'reviewed_at', 'approved_at', 'rejected_at',
        'expired_at', 'workflow_context', 'approval_conditions', 'conditional_approval',
        'conditions_met', 'supporting_documents', 'approval_evidence', 'compliance_checks',
        'external_approval_id', 'external_system', 'external_response', 'audit_trail',
        'requires_documentation', 'documentation_complete', 'compliance_verification',
        'policy_version', 'applicable_policies', 'rule_violations', 'policy_exception',
        'jurisdiction', 'regulatory_requirements', 'cross_border_approval',
        'approval_source', 'ip_address', 'user_agent', 'session_data', 'metadata',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'credit_note_id' => 'integer',
        'approver_id' => 'integer',
        'requested_by' => 'integer',
        'escalated_to' => 'integer',
        'escalated_by' => 'integer',
        'delegated_from' => 'integer',
        'delegated_to' => 'integer',
        'bypassed_by' => 'integer',
        'sequence_order' => 'integer',
        'sla_hours' => 'integer',
        'response_time_hours' => 'integer',
        'reminder_count' => 'integer',
        'approval_threshold' => 'decimal:2',
        'risk_score' => 'decimal:2',
        'amount_based' => 'boolean',
        'risk_based' => 'boolean',
        'client_based' => 'boolean',
        'reason_based' => 'boolean',
        'emergency_approval' => 'boolean',
        'approval_bypassed' => 'boolean',
        'escalated' => 'boolean',
        'delegated' => 'boolean',
        'sla_breached' => 'boolean',
        'auto_approved' => 'boolean',
        'notification_sent' => 'boolean',
        'conditional_approval' => 'boolean',
        'requires_documentation' => 'boolean',
        'documentation_complete' => 'boolean',
        'policy_exception' => 'boolean',
        'cross_border_approval' => 'boolean',
        'decision_factors' => 'array',
        'approval_criteria' => 'array',
        'risk_factors' => 'array',
        'auto_approval_rules' => 'array',
        'notification_history' => 'array',
        'workflow_context' => 'array',
        'approval_conditions' => 'array',
        'conditions_met' => 'array',
        'supporting_documents' => 'array',
        'approval_evidence' => 'array',
        'compliance_checks' => 'array',
        'external_response' => 'array',
        'audit_trail' => 'array',
        'compliance_verification' => 'array',
        'applicable_policies' => 'array',
        'rule_violations' => 'array',
        'regulatory_requirements' => 'array',
        'session_data' => 'array',
        'metadata' => 'array',
        'delegation_expiry' => 'date',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expired_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'notification_sent_at' => 'datetime',
        'last_reminder_sent' => 'datetime',
        'escalated_at' => 'datetime',
        'delegated_at' => 'datetime',
        'bypassed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Approval Levels
    const LEVEL_SUPERVISOR = 'supervisor';

    const LEVEL_MANAGER = 'manager';

    const LEVEL_FINANCE_MANAGER = 'finance_manager';

    const LEVEL_CONTROLLER = 'controller';

    const LEVEL_CFO = 'cfo';

    const LEVEL_EXECUTIVE = 'executive';

    const LEVEL_LEGAL = 'legal';

    // Status
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_ESCALATED = 'escalated';

    const STATUS_BYPASSED = 'bypassed';

    const STATUS_EXPIRED = 'expired';

    const STATUS_CANCELLED = 'cancelled';

    // Approval Types
    const TYPE_MANUAL = 'manual';

    const TYPE_AUTOMATIC = 'automatic';

    const TYPE_DELEGATED = 'delegated';

    const TYPE_ESCALATED = 'escalated';

    const TYPE_EMERGENCY = 'emergency';

    // Risk Levels
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

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function delegatedFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_from');
    }

    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }

    public function bypassedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bypassed_by');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? Auth::user()?->company_id;

        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

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

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    public function scopeSlaBreached($query)
    {
        return $query->where('sla_breached', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('sla_deadline', '<', now())
            ->where('status', self::STATUS_PENDING);
    }

    public function scopeEscalated($query)
    {
        return $query->where('escalated', true);
    }

    public function scopeDelegated($query)
    {
        return $query->where('delegated', true);
    }

    public function scopeEmergency($query)
    {
        return $query->where('emergency_approval', true);
    }

    public function scopeAutoApproved($query)
    {
        return $query->where('auto_approved', true);
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if approval is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if approval is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if approval is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if approval is expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->sla_deadline && $this->sla_deadline < now());
    }

    /**
     * Check if SLA is breached
     */
    public function isSlaBreached(): bool
    {
        return $this->sla_deadline && $this->sla_deadline < now() &&
               $this->status === self::STATUS_PENDING;
    }

    /**
     * Approve the request
     */
    public function approve(?string $reason = null, array $evidence = []): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_at = now();
        $this->approval_reason = $reason;
        $this->response_time_hours = $this->calculateResponseTime();

        if (! empty($evidence)) {
            $this->approval_evidence = $evidence;
        }

        $this->save();

        $this->createAuditEntry('approved', $reason);
        $this->sendApprovalNotifications();

        return true;
    }

    /**
     * Reject the request
     */
    public function reject(string $reason): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->status = self::STATUS_REJECTED;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;
        $this->response_time_hours = $this->calculateResponseTime();

        $this->save();

        $this->createAuditEntry('rejected', $reason);
        $this->sendRejectionNotifications();

        return true;
    }

    /**
     * Escalate to higher authority
     */
    public function escalate(User $escalatedTo, string $reason, ?User $escalatedBy = null): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->escalated = true;
        $this->escalated_to = $escalatedTo->id;
        $this->escalated_by = $escalatedBy ? $escalatedBy->id : Auth::id();
        $this->escalated_at = now();
        $this->escalation_reason = $reason;
        $this->status = self::STATUS_ESCALATED;

        $this->save();

        $this->createAuditEntry('escalated', $reason);
        $this->sendEscalationNotifications();

        return true;
    }

    /**
     * Delegate to another approver
     */
    public function delegate(User $delegateTo, string $reason, ?Carbon $expiry = null): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->delegated = true;
        $this->delegated_from = $this->approver_id;
        $this->delegated_to = $delegateTo->id;
        $this->delegated_at = now();
        $this->delegation_reason = $reason;
        $this->delegation_expiry = $expiry;
        $this->approver_id = $delegateTo->id; // Change the approver

        $this->save();

        $this->createAuditEntry('delegated', $reason);
        $this->sendDelegationNotifications();

        return true;
    }

    /**
     * Bypass approval (emergency situations)
     */
    public function bypass(User $bypassedBy, string $reason): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->approval_bypassed = true;
        $this->bypassed_by = $bypassedBy->id;
        $this->bypassed_at = now();
        $this->bypass_reason = $reason;
        $this->status = self::STATUS_BYPASSED;

        $this->save();

        $this->createAuditEntry('bypassed', $reason);
        $this->sendBypassNotifications();

        return true;
    }

    /**
     * Auto-approve based on rules
     */
    public function autoApprove(array $rules, string $reason): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->auto_approved = true;
        $this->auto_approval_rules = $rules;
        $this->auto_approval_reason = $reason;
        $this->status = self::STATUS_APPROVED;
        $this->approved_at = now();
        $this->approval_type = self::TYPE_AUTOMATIC;
        $this->response_time_hours = $this->calculateResponseTime();

        $this->save();

        $this->createAuditEntry('auto_approved', $reason);

        return true;
    }

    /**
     * Calculate response time in hours
     */
    public function calculateResponseTime(): ?int
    {
        if (! $this->requested_at) {
            return null;
        }

        return $this->requested_at->diffInHours(now());
    }

    /**
     * Send reminder notification
     */
    public function sendReminder(): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->reminder_count += 1;
        $this->last_reminder_sent = now();
        $this->save();

        $this->sendReminderNotification();

        return true;
    }

    /**
     * Check if conditions are met for conditional approval
     */
    public function checkConditions(): bool
    {
        if (! $this->conditional_approval || ! $this->approval_conditions) {
            return true;
        }

        $conditionsMet = [];
        foreach ($this->approval_conditions as $condition) {
            $conditionsMet[$condition['id']] = $this->evaluateCondition($condition);
        }

        $this->conditions_met = $conditionsMet;
        $this->save();

        return ! in_array(false, $conditionsMet);
    }

    /**
     * Get available approval levels
     */
    public static function getApprovalLevels(): array
    {
        return [
            self::LEVEL_SUPERVISOR => 'Supervisor',
            self::LEVEL_MANAGER => 'Manager',
            self::LEVEL_FINANCE_MANAGER => 'Finance Manager',
            self::LEVEL_CONTROLLER => 'Controller',
            self::LEVEL_CFO => 'CFO',
            self::LEVEL_EXECUTIVE => 'Executive',
            self::LEVEL_LEGAL => 'Legal',
        ];
    }

    /**
     * Get risk levels
     */
    public static function getRiskLevels(): array
    {
        return [
            self::RISK_LOW => 'Low',
            self::RISK_MEDIUM => 'Medium',
            self::RISK_HIGH => 'High',
            self::RISK_CRITICAL => 'Critical',
        ];
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_ESCALATED => 'purple',
            self::STATUS_BYPASSED => 'orange',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get risk color for UI
     */
    public function getRiskColorAttribute(): string
    {
        return match ($this->risk_level) {
            self::RISK_LOW => 'green',
            self::RISK_MEDIUM => 'yellow',
            self::RISK_HIGH => 'orange',
            self::RISK_CRITICAL => 'red',
            default => 'gray'
        };
    }

    /**
     * Private helper methods
     */
    private function evaluateCondition(array $condition): bool
    {
        // Implementation would evaluate specific conditions
        // This could include document verification, compliance checks, etc.
        return true;
    }

    private function createAuditEntry(string $action, ?string $details = null): void
    {
        // Implementation would create audit trail entries
    }

    private function sendApprovalNotifications(): void
    {
        // Implementation would send approval notifications
    }

    private function sendRejectionNotifications(): void
    {
        // Implementation would send rejection notifications
    }

    private function sendEscalationNotifications(): void
    {
        // Implementation would send escalation notifications
    }

    private function sendDelegationNotifications(): void
    {
        // Implementation would send delegation notifications
    }

    private function sendBypassNotifications(): void
    {
        // Implementation would send bypass notifications
    }

    private function sendReminderNotification(): void
    {
        // Implementation would send reminder notifications
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($approval) {
            if (! $approval->company_id) {
                $approval->company_id = Auth::user()?->company_id;
            }

            if (! $approval->requested_at) {
                $approval->requested_at = now();
            }

            // Set default SLA deadline
            if (! $approval->sla_deadline) {
                $approval->sla_deadline = now()->addHours($approval->sla_hours ?? 24);
            }
        });

        static::updating(function ($approval) {
            // Check for SLA breach
            if ($approval->sla_deadline < now() &&
                $approval->status === self::STATUS_PENDING &&
                ! $approval->sla_breached) {
                $approval->sla_breached = true;
            }
        });
    }
}
