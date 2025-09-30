<?php

namespace App\Domains\Project\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ProjectMilestone Model
 *
 * Manages project milestones and deliverables with tracking capabilities.
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string $status
 * @property string $priority
 * @property array|null $deliverables
 * @property array|null $acceptance_criteria
 * @property string|null $notes
 * @property decimal|null $completion_percentage
 * @property bool $is_critical
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ProjectMilestone extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'project_milestones';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'due_date',
        'completed_at',
        'status',
        'priority',
        'deliverables',
        'acceptance_criteria',
        'notes',
        'completion_percentage',
        'is_critical',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'project_id' => 'integer',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'deliverables' => 'array',
        'acceptance_criteria' => 'array',
        'completion_percentage' => 'decimal:2',
        'is_critical' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Milestone status enumeration
     */
    const STATUS_PENDING = 'pending';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_OVERDUE = 'overdue';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Milestone priority enumeration
     */
    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_CRITICAL = 'critical';

    /**
     * Get the project that owns the milestone.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get tasks associated with this milestone.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'milestone_id');
    }

    /**
     * Get the milestone status with human-readable formatting.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Get the milestone priority with human-readable formatting.
     */
    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
            default => 'Normal',
        };
    }

    /**
     * Check if milestone is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED && ! is_null($this->completed_at);
    }

    /**
     * Get completed attribute for convenience.
     */
    public function getCompletedAttribute(): bool
    {
        return $this->isCompleted();
    }

    /**
     * Check if milestone is overdue.
     */
    public function isOverdue(): bool
    {
        if (! $this->due_date || $this->isCompleted()) {
            return false;
        }

        return Carbon::now()->gt($this->due_date);
    }

    /**
     * Check if milestone is due soon.
     */
    public function isDueSoon(int $days = 7): bool
    {
        if (! $this->due_date || $this->isCompleted()) {
            return false;
        }

        return Carbon::now()->diffInDays($this->due_date, false) <= $days &&
               Carbon::now()->lte($this->due_date);
    }

    /**
     * Get days until due date.
     */
    public function getDaysUntilDue(): ?int
    {
        if (! $this->due_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->due_date, false);
    }

    /**
     * Get completion percentage based on associated tasks.
     */
    public function getCalculatedCompletion(): float
    {
        $totalTasks = $this->tasks()->count();

        if ($totalTasks === 0) {
            return $this->completion_percentage ?? 0;
        }

        $completedTasks = $this->tasks()
            ->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])
            ->count();

        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    /**
     * Get milestone health status.
     */
    public function getHealthStatus(): array
    {
        $health = ['status' => 'good', 'issues' => []];

        if ($this->isOverdue()) {
            $health['status'] = 'critical';
            $health['issues'][] = 'Milestone is overdue';
        } elseif ($this->isDueSoon()) {
            $health['status'] = 'warning';
            $health['issues'][] = 'Milestone due soon';
        }

        $completion = $this->getCalculatedCompletion();
        if ($this->due_date) {
            $expectedCompletion = $this->getExpectedCompletion();
            if ($expectedCompletion && $completion < ($expectedCompletion - 20)) {
                $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
                $health['issues'][] = 'Behind schedule';
            }
        }

        // Check overdue tasks
        $overdueTasks = $this->tasks()
            ->where('due_date', '<', Carbon::now())
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])
            ->count();

        if ($overdueTasks > 0) {
            $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
            $health['issues'][] = "{$overdueTasks} overdue tasks";
        }

        return $health;
    }

    /**
     * Get expected completion percentage based on timeline.
     */
    public function getExpectedCompletion(): ?float
    {
        if (! $this->due_date || ! $this->project->start_date) {
            return null;
        }

        $projectStart = $this->project->start_date;
        $totalProjectDays = $projectStart->diffInDays($this->project->due_date ?? $this->due_date);
        $daysToDue = $projectStart->diffInDays($this->due_date);
        $daysPassed = $projectStart->diffInDays(Carbon::now());

        if ($totalProjectDays === 0 || $daysToDue === 0) {
            return 100;
        }

        if ($daysPassed <= 0) {
            return 0;
        }

        if ($daysPassed >= $daysToDue) {
            return 100;
        }

        return round(($daysPassed / $daysToDue) * 100, 2);
    }

    /**
     * Mark milestone as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completion_percentage' => 100,
        ]);
    }

    /**
     * Mark milestone as in progress.
     */
    public function markAsInProgress(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    /**
     * Cancel milestone.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Get milestone statistics.
     */
    public function getStatistics(): array
    {
        return [
            'tasks' => [
                'total' => $this->tasks()->count(),
                'completed' => $this->tasks()->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])->count(),
                'in_progress' => $this->tasks()->where('status', Task::STATUS_IN_PROGRESS)->count(),
                'overdue' => $this->tasks()->where('due_date', '<', Carbon::now())->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])->count(),
            ],
            'deliverables' => [
                'total' => count($this->deliverables ?? []),
                'completed' => count(array_filter($this->deliverables ?? [], fn ($d) => $d['completed'] ?? false)),
            ],
            'acceptance_criteria' => [
                'total' => count($this->acceptance_criteria ?? []),
                'met' => count(array_filter($this->acceptance_criteria ?? [], fn ($c) => $c['met'] ?? false)),
            ],
        ];
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
        return $query->where('status', '!=', self::STATUS_COMPLETED)
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now());
    }

    /**
     * Scope to get milestones due soon.
     */
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', '!=', self::STATUS_COMPLETED)
            ->whereNotNull('due_date')
            ->where('due_date', '>=', Carbon::now())
            ->where('due_date', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Scope to get critical milestones.
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Scope to get milestones by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get validation rules for milestone creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'required|in:'.implode(',', self::getAvailableStatuses()),
            'priority' => 'required|in:'.implode(',', self::getAvailablePriorities()),
            'deliverables' => 'nullable|array',
            'deliverables.*.name' => 'required|string|max:255',
            'deliverables.*.description' => 'nullable|string',
            'deliverables.*.completed' => 'boolean',
            'acceptance_criteria' => 'nullable|array',
            'acceptance_criteria.*.criterion' => 'required|string|max:500',
            'acceptance_criteria.*.met' => 'boolean',
            'completion_percentage' => 'nullable|numeric|min:0|max:100',
            'is_critical' => 'boolean',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_OVERDUE,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Get available priorities.
     */
    public static function getAvailablePriorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_NORMAL,
            self::PRIORITY_HIGH,
            self::PRIORITY_CRITICAL,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set sort order for new milestones
        static::creating(function ($milestone) {
            if (! $milestone->sort_order) {
                $maxOrder = static::where('project_id', $milestone->project_id)
                    ->max('sort_order') ?? 0;
                $milestone->sort_order = $maxOrder + 1;
            }
        });

        // Update status based on due date and completion
        static::updating(function ($milestone) {
            if ($milestone->isDirty('due_date') || $milestone->isDirty('status')) {
                if ($milestone->status !== self::STATUS_COMPLETED && $milestone->isOverdue()) {
                    $milestone->status = self::STATUS_OVERDUE;
                }
            }
        });
    }
}
