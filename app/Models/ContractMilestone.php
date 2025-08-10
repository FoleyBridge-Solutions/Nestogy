<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * ContractMilestone Model
 * 
 * Milestone management for contract projects with progress tracking,
 * billing integration, and VoIP-specific milestone types.
 * 
 * @property int $id
 * @property int $contract_id
 * @property int $company_id
 * @property string $milestone_number
 * @property string $title
 * @property string|null $description
 * @property string $milestone_type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $planned_start_date
 * @property \Illuminate\Support\Carbon $planned_completion_date
 * @property \Illuminate\Support\Carbon|null $actual_start_date
 * @property \Illuminate\Support\Carbon|null $actual_completion_date
 * @property int|null $estimated_duration_days
 * @property int|null $actual_duration_days
 * @property array|null $prerequisites
 * @property array|null $dependencies
 * @property array|null $blocks
 * @property float $milestone_value
 * @property float $invoice_amount
 * @property bool $billable
 * @property string|null $billing_trigger
 * @property int $completion_percentage
 * @property array|null $progress_metrics
 * @property array|null $kpis
 * @property array|null $deliverables
 * @property array|null $acceptance_criteria
 * @property array|null $quality_requirements
 * @property array|null $testing_requirements
 * @property array|null $voip_requirements
 * @property array|null $equipment_requirements
 * @property array|null $installation_requirements
 * @property array|null $porting_requirements
 * @property array|null $compliance_requirements
 * @property array|null $assigned_resources
 * @property array|null $resource_requirements
 * @property float|null $budget_allocated
 * @property float $budget_spent
 * @property array|null $risk_factors
 * @property array|null $mitigation_strategies
 * @property string|null $risk_level
 * @property array|null $stakeholders
 * @property array|null $notification_settings
 * @property \Illuminate\Support\Carbon|null $last_notification_sent
 * @property bool $requires_client_approval
 * @property bool $requires_internal_approval
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $approval_notes
 * @property array|null $attached_documents
 * @property array|null $completion_evidence
 * @property string|null $completion_notes
 * @property array|null $integration_triggers
 * @property array|null $automation_rules
 * @property bool $auto_invoice_generation
 * @property bool $auto_service_activation
 * @property array|null $performance_metrics
 * @property float|null $client_satisfaction_score
 * @property string|null $lessons_learned
 * @property int $sort_order
 * @property string|null $milestone_group
 * @property bool $is_critical_path
 * @property int $created_by
 * @property int|null $assigned_to
 * @property int|null $completed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ContractMilestone extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_milestones';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'contract_id',
        'company_id',
        'milestone_number',
        'title',
        'description',
        'milestone_type',
        'status',
        'planned_start_date',
        'planned_completion_date',
        'actual_start_date',
        'actual_completion_date',
        'estimated_duration_days',
        'actual_duration_days',
        'prerequisites',
        'dependencies',
        'blocks',
        'milestone_value',
        'invoice_amount',
        'billable',
        'billing_trigger',
        'completion_percentage',
        'progress_metrics',
        'kpis',
        'deliverables',
        'acceptance_criteria',
        'quality_requirements',
        'testing_requirements',
        'voip_requirements',
        'equipment_requirements',
        'installation_requirements',
        'porting_requirements',
        'compliance_requirements',
        'assigned_resources',
        'resource_requirements',
        'budget_allocated',
        'budget_spent',
        'risk_factors',
        'mitigation_strategies',
        'risk_level',
        'stakeholders',
        'notification_settings',
        'last_notification_sent',
        'requires_client_approval',
        'requires_internal_approval',
        'approved_by',
        'approved_at',
        'approval_notes',
        'attached_documents',
        'completion_evidence',
        'completion_notes',
        'integration_triggers',
        'automation_rules',
        'auto_invoice_generation',
        'auto_service_activation',
        'performance_metrics',
        'client_satisfaction_score',
        'lessons_learned',
        'sort_order',
        'milestone_group',
        'is_critical_path',
        'created_by',
        'assigned_to',
        'completed_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'contract_id' => 'integer',
        'company_id' => 'integer',
        'planned_start_date' => 'date',
        'planned_completion_date' => 'date',
        'actual_start_date' => 'date',
        'actual_completion_date' => 'date',
        'estimated_duration_days' => 'integer',
        'actual_duration_days' => 'integer',
        'prerequisites' => 'array',
        'dependencies' => 'array',
        'blocks' => 'array',
        'milestone_value' => 'decimal:2',
        'invoice_amount' => 'decimal:2',
        'billable' => 'boolean',
        'completion_percentage' => 'integer',
        'progress_metrics' => 'array',
        'kpis' => 'array',
        'deliverables' => 'array',
        'acceptance_criteria' => 'array',
        'quality_requirements' => 'array',
        'testing_requirements' => 'array',
        'voip_requirements' => 'array',
        'equipment_requirements' => 'array',
        'installation_requirements' => 'array',
        'porting_requirements' => 'array',
        'compliance_requirements' => 'array',
        'assigned_resources' => 'array',
        'resource_requirements' => 'array',
        'budget_allocated' => 'decimal:2',
        'budget_spent' => 'decimal:2',
        'risk_factors' => 'array',
        'mitigation_strategies' => 'array',
        'stakeholders' => 'array',
        'notification_settings' => 'array',
        'last_notification_sent' => 'datetime',
        'requires_client_approval' => 'boolean',
        'requires_internal_approval' => 'boolean',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'attached_documents' => 'array',
        'completion_evidence' => 'array',
        'integration_triggers' => 'array',
        'automation_rules' => 'array',
        'auto_invoice_generation' => 'boolean',
        'auto_service_activation' => 'boolean',
        'performance_metrics' => 'array',
        'client_satisfaction_score' => 'decimal:2',
        'sort_order' => 'integer',
        'is_critical_path' => 'boolean',
        'created_by' => 'integer',
        'assigned_to' => 'integer',
        'completed_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Milestone type enumeration
     */
    const TYPE_PROJECT_PHASE = 'project_phase';
    const TYPE_DELIVERABLE = 'deliverable';
    const TYPE_PAYMENT_MILESTONE = 'payment_milestone';
    const TYPE_SERVICE_ACTIVATION = 'service_activation';
    const TYPE_EQUIPMENT_DELIVERY = 'equipment_delivery';
    const TYPE_INSTALLATION_COMPLETE = 'installation_complete';
    const TYPE_TESTING_COMPLETE = 'testing_complete';
    const TYPE_GO_LIVE = 'go_live';
    const TYPE_TRAINING_COMPLETE = 'training_complete';
    const TYPE_ACCEPTANCE_CRITERIA = 'acceptance_criteria';
    const TYPE_CUSTOM = 'custom';

    /**
     * Milestone status enumeration
     */
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DELAYED = 'delayed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_BLOCKED = 'blocked';

    /**
     * Billing trigger enumeration
     */
    const BILLING_ON_START = 'milestone_start';
    const BILLING_ON_COMPLETION = 'milestone_completion';
    const BILLING_MANUAL = 'manual_trigger';
    const BILLING_CLIENT_APPROVAL = 'client_approval';

    /**
     * Risk level enumeration
     */
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    /**
     * Get the contract this milestone belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the user who created this milestone.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to this milestone.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who completed this milestone.
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get the user who approved this milestone.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if milestone is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if milestone is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        return Carbon::now()->gt($this->planned_completion_date);
    }

    /**
     * Check if milestone is on critical path.
     */
    public function isCriticalPath(): bool
    {
        return $this->is_critical_path;
    }

    /**
     * Check if milestone is billable.
     */
    public function isBillable(): bool
    {
        return $this->billable;
    }

    /**
     * Get days until completion.
     */
    public function getDaysUntilCompletion(): int
    {
        if ($this->isCompleted()) {
            return 0;
        }

        return max(0, Carbon::now()->diffInDays($this->planned_completion_date, false));
    }

    /**
     * Get duration variance (actual vs planned).
     */
    public function getDurationVariance(): ?int
    {
        if (!$this->actual_duration_days || !$this->estimated_duration_days) {
            return null;
        }

        return $this->actual_duration_days - $this->estimated_duration_days;
    }

    /**
     * Get budget variance (spent vs allocated).
     */
    public function getBudgetVariance(): ?float
    {
        if (!$this->budget_allocated) {
            return null;
        }

        return $this->budget_spent - $this->budget_allocated;
    }

    /**
     * Start milestone.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'actual_start_date' => now(),
        ]);
    }

    /**
     * Complete milestone.
     */
    public function complete(array $completionData = []): void
    {
        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'actual_completion_date' => now(),
            'completion_percentage' => 100,
        ];

        if (isset($completionData['completed_by'])) {
            $updateData['completed_by'] = $completionData['completed_by'];
        }

        if (isset($completionData['completion_notes'])) {
            $updateData['completion_notes'] = $completionData['completion_notes'];
        }

        if (isset($completionData['completion_evidence'])) {
            $updateData['completion_evidence'] = $completionData['completion_evidence'];
        }

        // Calculate actual duration
        if ($this->actual_start_date) {
            $updateData['actual_duration_days'] = $this->actual_start_date->diffInDays(now());
        }

        $this->update($updateData);

        // Trigger auto-invoice generation if configured
        if ($this->auto_invoice_generation && $this->isBillable()) {
            $this->triggerInvoiceGeneration();
        }

        // Trigger service activation if configured
        if ($this->auto_service_activation) {
            $this->triggerServiceActivation();
        }
    }

    /**
     * Update progress percentage.
     */
    public function updateProgress(int $percentage, array $metrics = []): void
    {
        $updateData = ['completion_percentage' => min(100, max(0, $percentage))];

        if (!empty($metrics)) {
            $updateData['progress_metrics'] = array_merge($this->progress_metrics ?? [], $metrics);
        }

        // Auto-complete if 100%
        if ($percentage >= 100 && !$this->isCompleted()) {
            $updateData['status'] = self::STATUS_COMPLETED;
            $updateData['actual_completion_date'] = now();
        }

        $this->update($updateData);
    }

    /**
     * Add risk factor.
     */
    public function addRisk(string $description, string $level = self::RISK_MEDIUM, string $mitigation = null): void
    {
        $risks = $this->risk_factors ?? [];
        $risks[] = [
            'description' => $description,
            'level' => $level,
            'mitigation' => $mitigation,
            'added_at' => now(),
            'added_by' => auth()->id(),
        ];

        $this->update(['risk_factors' => $risks]);
    }

    /**
     * Update milestone status with reason.
     */
    public function updateStatus(string $status, string $reason = null): void
    {
        $this->update([
            'status' => $status,
            'completion_notes' => $reason ? ($this->completion_notes . "\n" . now()->format('Y-m-d H:i:s') . ": " . $reason) : $this->completion_notes,
        ]);
    }

    /**
     * Get formatted milestone value.
     */
    public function getFormattedValue(): string
    {
        return $this->contract->formatCurrency($this->milestone_value);
    }

    /**
     * Trigger invoice generation for this milestone.
     */
    protected function triggerInvoiceGeneration(): void
    {
        // This would integrate with the invoice generation system
        // Implementation depends on the specific invoicing service
    }

    /**
     * Trigger service activation for this milestone.
     */
    protected function triggerServiceActivation(): void
    {
        // This would integrate with service provisioning systems
        // Implementation depends on the specific VoIP provisioning system
    }

    /**
     * Scope to get milestones by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get completed milestones.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get overdue milestones.
     */
    public function scopeOverdue($query)
    {
        return $query->where('planned_completion_date', '<', now())
                    ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope to get billable milestones.
     */
    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    /**
     * Scope to get critical path milestones.
     */
    public function scopeCriticalPath($query)
    {
        return $query->where('is_critical_path', true);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set defaults when creating
        static::creating(function ($milestone) {
            if (!$milestone->status) {
                $milestone->status = self::STATUS_NOT_STARTED;
            }

            if (!$milestone->completion_percentage) {
                $milestone->completion_percentage = 0;
            }

            if (!$milestone->sort_order) {
                $lastMilestone = static::where('contract_id', $milestone->contract_id)
                    ->orderBy('sort_order', 'desc')
                    ->first();
                
                $milestone->sort_order = $lastMilestone ? $lastMilestone->sort_order + 1 : 1;
            }
        });
    }
}