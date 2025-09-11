<?php

namespace App\Domains\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

/**
 * Task Model
 * 
 * Comprehensive task management with dependencies, subtasks, time tracking,
 * and status management for enterprise project workflows.
 * 
 * @property int $id
 * @property int $project_id
 * @property string $task_code
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property int|null $assigned_to
 * @property int|null $created_by
 * @property int|null $parent_task_id
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $actual_start_date
 * @property \Illuminate\Support\Carbon|null $actual_end_date
 * @property decimal|null $estimated_hours
 * @property decimal|null $actual_hours
 * @property int $progress_percentage
 * @property string|null $category
 * @property array|null $tags
 * @property array|null $custom_fields
 * @property string|null $notes
 * @property bool $is_billable
 * @property bool $is_recurring
 * @property string|null $recurring_pattern
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Task extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'project_tasks';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'task_code',
        'name',
        'description',
        'status',
        'priority',
        'assigned_to',
        'created_by',
        'parent_task_id',
        'start_date',
        'due_date',
        'actual_start_date',
        'actual_end_date',
        'estimated_hours',
        'actual_hours',
        'progress_percentage',
        'category',
        'tags',
        'custom_fields',
        'notes',
        'is_billable',
        'is_recurring',
        'recurring_pattern',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'project_id' => 'integer',
        'assigned_to' => 'integer',
        'created_by' => 'integer',
        'parent_task_id' => 'integer',
        'start_date' => 'date',
        'due_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'progress_percentage' => 'integer',
        'tags' => 'array',
        'custom_fields' => 'array',
        'is_billable' => 'boolean',
        'is_recurring' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Task status enumeration
     */
    const STATUS_TODO = 'todo';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Task priority enumeration
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * Task categories
     */
    const CATEGORY_DEVELOPMENT = 'development';
    const CATEGORY_DESIGN = 'design';
    const CATEGORY_TESTING = 'testing';
    const CATEGORY_DOCUMENTATION = 'documentation';
    const CATEGORY_RESEARCH = 'research';
    const CATEGORY_MEETING = 'meeting';
    const CATEGORY_REVIEW = 'review';
    const CATEGORY_OTHER = 'other';

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned to this task.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /**
     * Get the user who created this task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the parent task (for subtasks).
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Get all subtasks.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Get all task dependencies (tasks that must be completed before this one).
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
                    ->withPivot(['dependency_type', 'lag_days'])
                    ->withTimestamps();
    }

    /**
     * Get all tasks that depend on this task.
     */
    public function dependentTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
                    ->withPivot(['dependency_type', 'lag_days'])
                    ->withTimestamps();
    }

    /**
     * Get time tracking entries for this task.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TaskTimeEntry::class);
    }

    /**
     * Get comments for this task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Get attachments for this task.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * Get checklist items for this task.
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class);
    }

    /**
     * Get watchers for this task.
     */
    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'task_watchers', 'task_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Get the task's full identifier/code.
     */
    public function getFullCode(): string
    {
        return $this->task_code;
    }

    /**
     * Get the task status with human-readable formatting.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_TODO => 'To Do',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_IN_REVIEW => 'In Review',
            self::STATUS_BLOCKED => 'Blocked',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Get the task priority with human-readable formatting.
     */
    public function getPriorityLabel(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
            self::PRIORITY_CRITICAL => 'Critical',
            default => 'Normal',
        };
    }

    /**
     * Check if task is completed.
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CLOSED]);
    }

    /**
     * Check if task is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if task is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date || $this->isCompleted()) {
            return false;
        }

        return Carbon::now()->gt($this->due_date);
    }

    /**
     * Check if task is due soon.
     */
    public function isDueSoon(int $days = 3): bool
    {
        if (!$this->due_date || $this->isCompleted()) {
            return false;
        }

        return Carbon::now()->diffInDays($this->due_date, false) <= $days && 
               Carbon::now()->lte($this->due_date);
    }

    /**
     * Check if task can be started (all dependencies completed).
     */
    public function canBeStarted(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        $incompleteDependencies = $this->dependencies()
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CLOSED])
            ->count();

        return $incompleteDependencies === 0;
    }

    /**
     * Get days until due date.
     */
    public function getDaysUntilDue(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->due_date, false);
    }

    /**
     * Get task duration in days.
     */
    public function getPlannedDurationInDays(): ?int
    {
        if (!$this->start_date || !$this->due_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->due_date);
    }

    /**
     * Get actual task duration in days.
     */
    public function getActualDurationInDays(): ?int
    {
        if (!$this->actual_start_date) {
            return null;
        }

        $endDate = $this->actual_end_date ?: Carbon::now();
        return $this->actual_start_date->diffInDays($endDate);
    }

    /**
     * Get progress based on checklist completion.
     */
    public function getCalculatedProgress(): float
    {
        $totalItems = $this->checklistItems()->count();
        
        if ($totalItems === 0) {
            return $this->progress_percentage;
        }

        $completedItems = $this->checklistItems()->where('is_completed', true)->count();
        return round(($completedItems / $totalItems) * 100, 2);
    }

    /**
     * Get total time logged on task.
     */
    public function getTotalTimeLogged(): float
    {
        return $this->timeEntries()->sum('hours') ?? 0;
    }

    /**
     * Get time remaining based on estimate.
     */
    public function getTimeRemaining(): ?float
    {
        if (!$this->estimated_hours) {
            return null;
        }

        return max(0, $this->estimated_hours - $this->getTotalTimeLogged());
    }

    /**
     * Check if task is over estimated time.
     */
    public function isOverEstimatedTime(): bool
    {
        if (!$this->estimated_hours) {
            return false;
        }

        return $this->getTotalTimeLogged() > $this->estimated_hours;
    }

    /**
     * Get task health status.
     */
    public function getHealthStatus(): array
    {
        $health = ['status' => 'good', 'issues' => []];

        if ($this->isOverdue()) {
            $health['status'] = 'critical';
            $health['issues'][] = 'Task is overdue';
        } elseif ($this->isDueSoon()) {
            $health['status'] = 'warning';
            $health['issues'][] = 'Task due soon';
        }

        if ($this->isBlocked()) {
            $health['status'] = 'critical';
            $health['issues'][] = 'Task is blocked';
        }

        if ($this->isOverEstimatedTime()) {
            $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
            $health['issues'][] = 'Over estimated time';
        }

        if (!$this->canBeStarted() && !$this->isCompleted()) {
            $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
            $health['issues'][] = 'Waiting for dependencies';
        }

        return $health;
    }

    /**
     * Start working on task.
     */
    public function startWork(): void
    {
        if (!$this->canBeStarted()) {
            throw new \Exception('Task cannot be started until dependencies are completed.');
        }

        $updates = ['status' => self::STATUS_IN_PROGRESS];
        
        if (!$this->actual_start_date) {
            $updates['actual_start_date'] = now();
        }

        $this->update($updates);
    }

    /**
     * Mark task as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'actual_end_date' => now(),
            'progress_percentage' => 100,
        ]);

        // Mark all checklist items as completed
        $this->checklistItems()->update(['is_completed' => true]);
    }

    /**
     * Block task with reason.
     */
    public function block(string $reason = null): void
    {
        $this->update(['status' => self::STATUS_BLOCKED]);
        
        if ($reason) {
            $this->comments()->create([
                'user_id' => auth()->id(),
                'comment' => "Task blocked: {$reason}",
                'type' => 'system',
            ]);
        }
    }

    /**
     * Unblock task.
     */
    public function unblock(): void
    {
        $this->update(['status' => self::STATUS_TODO]);
        
        $this->comments()->create([
            'user_id' => auth()->id(),
            'comment' => 'Task unblocked',
            'type' => 'system',
        ]);
    }

    /**
     * Add dependency to this task.
     */
    public function addDependency(Task $dependsOnTask, string $type = 'finish_to_start', int $lagDays = 0): void
    {
        $this->dependencies()->attach($dependsOnTask->id, [
            'dependency_type' => $type,
            'lag_days' => $lagDays,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Remove dependency from this task.
     */
    public function removeDependency(Task $dependsOnTask): void
    {
        $this->dependencies()->detach($dependsOnTask->id);
    }

    /**
     * Clone task (create copy).
     */
    public function clone(array $overrides = []): Task
    {
        $attributes = $this->toArray();
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);
        
        $attributes = array_merge($attributes, $overrides);
        $attributes['name'] = 'Copy of ' . $attributes['name'];
        
        return Task::create($attributes);
    }

    /**
     * Create recurring task instance.
     */
    public function createRecurringInstance(): Task
    {
        if (!$this->is_recurring) {
            throw new \Exception('This task is not configured as recurring.');
        }

        $nextDueDate = $this->calculateNextRecurringDate();
        
        $newTask = $this->clone([
            'due_date' => $nextDueDate,
            'start_date' => $nextDueDate->subDays($this->getPlannedDurationInDays() ?? 1),
            'status' => self::STATUS_TODO,
            'progress_percentage' => 0,
            'actual_start_date' => null,
            'actual_end_date' => null,
            'actual_hours' => null,
        ]);

        return $newTask;
    }

    /**
     * Calculate next recurring date based on pattern.
     */
    protected function calculateNextRecurringDate(): Carbon
    {
        $pattern = json_decode($this->recurring_pattern, true);
        $baseDate = $this->due_date ?: now();

        return match($pattern['type']) {
            'daily' => $baseDate->addDays($pattern['interval'] ?? 1),
            'weekly' => $baseDate->addWeeks($pattern['interval'] ?? 1),
            'monthly' => $baseDate->addMonths($pattern['interval'] ?? 1),
            'yearly' => $baseDate->addYears($pattern['interval'] ?? 1),
            default => $baseDate->addDays(1),
        };
    }

    /**
     * Scope to get tasks by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get completed tasks.
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_CLOSED]);
    }

    /**
     * Scope to get active tasks (not completed).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope to get overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CLOSED])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', Carbon::now());
    }

    /**
     * Scope to get tasks due soon.
     */
    public function scopeDueSoon($query, int $days = 3)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CLOSED])
                    ->whereNotNull('due_date')
                    ->where('due_date', '>=', Carbon::now())
                    ->where('due_date', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Scope to get tasks assigned to user.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to get tasks by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get parent tasks (no parent_task_id).
     */
    public function scopeParentTasks($query)
    {
        return $query->whereNull('parent_task_id');
    }

    /**
     * Scope to get subtasks.
     */
    public function scopeSubtasks($query)
    {
        return $query->whereNotNull('parent_task_id');
    }

    /**
     * Scope to search tasks.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%')
              ->orWhere('task_code', 'like', '%' . $search . '%');
        });
    }

    /**
     * Get validation rules for task creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:' . implode(',', self::getAvailableStatuses()),
            'priority' => 'required|in:' . implode(',', self::getAvailablePriorities()),
            'assigned_to' => 'nullable|integer|exists:users,id',
            'parent_task_id' => 'nullable|integer|exists:project_tasks,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'category' => 'nullable|in:' . implode(',', self::getAvailableCategories()),
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_billable' => 'boolean',
            'is_recurring' => 'boolean',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_TODO,
            self::STATUS_IN_PROGRESS,
            self::STATUS_IN_REVIEW,
            self::STATUS_BLOCKED,
            self::STATUS_COMPLETED,
            self::STATUS_CLOSED,
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
            self::PRIORITY_URGENT,
            self::PRIORITY_CRITICAL,
        ];
    }

    /**
     * Get available categories.
     */
    public static function getAvailableCategories(): array
    {
        return [
            self::CATEGORY_DEVELOPMENT,
            self::CATEGORY_DESIGN,
            self::CATEGORY_TESTING,
            self::CATEGORY_DOCUMENTATION,
            self::CATEGORY_RESEARCH,
            self::CATEGORY_MEETING,
            self::CATEGORY_REVIEW,
            self::CATEGORY_OTHER,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate task code for new tasks
        static::creating(function ($task) {
            if (!$task->task_code) {
                $task->task_code = static::generateTaskCode($task->project_id);
            }

            // Set created_by from authenticated user if not set
            if (!$task->created_by && auth()->user()) {
                $task->created_by = auth()->user()->id;
            }

            // Set sort order
            if (!$task->sort_order) {
                $maxOrder = static::where('project_id', $task->project_id)
                    ->where('parent_task_id', $task->parent_task_id)
                    ->max('sort_order') ?? 0;
                $task->sort_order = $maxOrder + 1;
            }
        });

        // Update progress when task is updated
        static::updating(function ($task) {
            if ($task->isDirty('status') && in_array($task->status, [self::STATUS_COMPLETED, self::STATUS_CLOSED])) {
                $task->progress_percentage = 100;
                if (!$task->actual_end_date) {
                    $task->actual_end_date = now();
                }
            }
        });
    }

    /**
     * Generate a unique task code.
     */
    protected static function generateTaskCode(int $projectId): string
    {
        $project = Project::find($projectId);
        $projectCode = $project ? $project->project_code : 'PRJ-UNKNOWN';
        
        $lastTask = static::where('project_id', $projectId)
            ->where('task_code', 'like', $projectCode . '-T%')
            ->orderBy('task_code', 'desc')
            ->first();

        if ($lastTask) {
            $lastNumber = intval(substr($lastTask->task_code, strlen($projectCode . '-T')));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $projectCode . '-T' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}