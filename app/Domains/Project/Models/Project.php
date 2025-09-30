<?php

namespace App\Domains\Project\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Enhanced Project Model
 *
 * Enterprise-grade project management with comprehensive lifecycle management,
 * task dependencies, team management, timeline tracking, and reporting capabilities.
 *
 * @property int $id
 * @property int $company_id
 * @property string $project_code
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property int|null $client_id
 * @property int|null $manager_id
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $due
 * @property \Illuminate\Support\Carbon|null $actual_start_date
 * @property \Illuminate\Support\Carbon|null $actual_end_date
 * @property decimal|null $budget
 * @property decimal|null $actual_cost
 * @property string|null $budget_currency
 * @property int $progress_percentage
 * @property string|null $category
 * @property array|null $tags
 * @property array|null $custom_fields
 * @property string|null $notes
 * @property bool $is_billable
 * @property bool $is_template
 * @property int|null $template_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'projects';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'prefix',
        'number',
        'name',
        'description',
        'status',
        'progress',
        'priority',
        'client_id',
        'manager_id',
        'start_date',
        'due',
        'completed_at',
        'budget',
        'actual_cost',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'manager_id' => 'integer',
        'start_date' => 'date',
        'due' => 'date',
        'completed_at' => 'datetime',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'progress' => 'integer',
        'deleted_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Project status enumeration
     */
    const STATUS_PLANNING = 'planning';

    const STATUS_ACTIVE = 'active';

    const STATUS_ON_HOLD = 'on_hold';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_ARCHIVED = 'archived';

    /**
     * Project priority enumeration
     */
    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    const PRIORITY_CRITICAL = 'critical';

    /**
     * Project categories
     */
    const CATEGORY_DEVELOPMENT = 'development';

    const CATEGORY_DESIGN = 'design';

    const CATEGORY_MARKETING = 'marketing';

    const CATEGORY_MAINTENANCE = 'maintenance';

    const CATEGORY_CONSULTING = 'consulting';

    const CATEGORY_SUPPORT = 'support';

    const CATEGORY_RESEARCH = 'research';

    const CATEGORY_OTHER = 'other';

    /**
     * Get the company that owns the project.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Get the client that owns the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client::class);
    }

    /**
     * Get the project manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    /**
     * Get the project template if this project was created from one.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    /**
     * Get all tasks associated with this project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get all milestones associated with this project.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    /**
     * Get all project members (team assignments).
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Get team members through pivot relationship.
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'project_members', 'project_id', 'user_id')
            ->withPivot(['role', 'hourly_rate', 'can_edit', 'can_manage_tasks', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get tickets associated with this project.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(\App\Domains\Ticket\Models\Ticket::class);
    }

    /**
     * Get time tracking entries for this project.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(ProjectTimeEntry::class);
    }

    /**
     * Get project files/documents.
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * Get project expenses.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    /**
     * Get project comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ProjectComment::class);
    }

    /**
     * Get all projects created from this template (if this is a template).
     */
    public function projectsFromTemplate(): HasMany
    {
        return $this->hasMany(Project::class, 'template_id');
    }

    /**
     * Get the project's full identifier/code.
     */
    public function getFullCode(): string
    {
        return $this->project_code;
    }

    /**
     * Get the project status with human-readable formatting.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PLANNING => 'Planning',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ARCHIVED => 'Archived',
            default => 'Unknown',
        };
    }

    /**
     * Get the project priority with human-readable formatting.
     */
    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
            self::PRIORITY_CRITICAL => 'Critical',
            default => 'Normal',
        };
    }

    /**
     * Check if project is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if project is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if project is on hold.
     */
    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    /**
     * Check if project is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if project is overdue.
     */
    public function isOverdue(): bool
    {
        if (! $this->due || $this->isCompleted()) {
            return false;
        }

        return Carbon::now()->gt($this->due);
    }

    /**
     * Check if project is due soon.
     */
    public function isDueSoon(int $days = 7): bool
    {
        if (! $this->due || $this->isCompleted()) {
            return false;
        }

        return Carbon::now()->diffInDays($this->due, false) <= $days &&
               Carbon::now()->lte($this->due);
    }

    /**
     * Get days until due date.
     */
    public function getDaysUntilDue(): ?int
    {
        if (! $this->due) {
            return null;
        }

        return Carbon::now()->diffInDays($this->due, false);
    }

    /**
     * Get project duration in days.
     */
    public function getPlannedDurationInDays(): ?int
    {
        if (! $this->start_date || ! $this->due) {
            return null;
        }

        return $this->start_date->diffInDays($this->due);
    }

    /**
     * Get actual project duration in days.
     */
    public function getActualDurationInDays(): ?int
    {
        if (! $this->actual_start_date) {
            return null;
        }

        $endDate = $this->actual_end_date ?: Carbon::now();

        return $this->actual_start_date->diffInDays($endDate);
    }

    /**
     * Get project progress percentage based on tasks.
     */
    public function getCalculatedProgress(): float
    {
        $totalTasks = $this->tasks()->count();

        if ($totalTasks === 0) {
            return $this->isCompleted() ? 100.0 : (float) ($this->progress_percentage ?? 0);
        }

        $completedTasks = $this->tasks()
            ->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])
            ->count();

        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    /**
     * Get project health status based on various factors.
     */
    public function getHealthStatus(): array
    {
        $health = ['status' => 'good', 'issues' => []];

        if ($this->isOverdue()) {
            $health['status'] = 'critical';
            $health['issues'][] = 'Project is overdue';
        } elseif ($this->isDueSoon()) {
            $health['status'] = 'warning';
            $health['issues'][] = 'Project due soon';
        }

        // Budget health
        if ($this->budget && $this->actual_cost && $this->actual_cost > $this->budget) {
            $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
            $health['issues'][] = 'Over budget';
        }

        // Progress health
        $progress = $this->getCalculatedProgress();
        $expectedProgress = $this->getExpectedProgress();

        if ($expectedProgress && $progress < ($expectedProgress - 20)) {
            $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
            $health['issues'][] = 'Behind schedule';
        }

        // Task health
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
     * Get expected progress based on timeline.
     */
    public function getExpectedProgress(): ?float
    {
        if (! $this->start_date || ! $this->due) {
            return null;
        }

        $totalDays = $this->start_date->diffInDays($this->due);
        if ($totalDays === 0) {
            return 100;
        }

        $daysPassed = $this->start_date->diffInDays(Carbon::now());

        if ($daysPassed <= 0) {
            return 0;
        }

        if ($daysPassed >= $totalDays) {
            return 100;
        }

        return round(($daysPassed / $totalDays) * 100, 2);
    }

    /**
     * Get project statistics.
     */
    public function getStatistics(): array
    {
        return [
            'tasks' => [
                'total' => $this->tasks()->count(),
                'completed' => $this->tasks()->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])->count(),
                'in_progress' => $this->tasks()->where('status', Task::STATUS_IN_PROGRESS)->count(),
                'overdue' => $this->tasks()->where('due', '<', Carbon::now())->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])->count(),
            ],
            'milestones' => [
                'total' => $this->milestones()->count(),
                'completed' => $this->milestones()->where('is_completed', true)->count(),
                'overdue' => $this->milestones()->where('due', '<', Carbon::now())->where('is_completed', false)->count(),
            ],
            'team' => [
                'members' => $this->teamMembers()->count(),
                'active' => $this->teamMembers()->wherePivot('is_active', true)->count(),
            ],
            'time' => [
                'total_logged' => $this->getTotalTimeLogged(),
                'estimated' => $this->getTotalEstimatedTime(),
            ],
            'budget' => [
                'planned' => $this->budget ?? 0,
                'actual' => $this->actual_cost ?? 0,
                'remaining' => ($this->budget ?? 0) - ($this->actual_cost ?? 0),
            ],
        ];
    }

    /**
     * Get total time logged on project.
     */
    public function getTotalTimeLogged(): float
    {
        return $this->timeEntries()->sum('hours') ?? 0;
    }

    /**
     * Get total estimated time for all tasks.
     */
    public function getTotalEstimatedTime(): float
    {
        return $this->tasks()->sum('estimated_hours') ?? 0;
    }

    /**
     * Mark project as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'actual_end_date' => now(),
            'progress_percentage' => 100,
        ]);
    }

    /**
     * Mark project as active.
     */
    public function markAsActive(): void
    {
        $updates = ['status' => self::STATUS_ACTIVE];

        if (! $this->actual_start_date) {
            $updates['actual_start_date'] = now();
        }

        $this->update($updates);
    }

    /**
     * Put project on hold.
     */
    public function putOnHold(): void
    {
        $this->update(['status' => self::STATUS_ON_HOLD]);
    }

    /**
     * Cancel project.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'actual_end_date' => now(),
        ]);
    }

    /**
     * Archive project.
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Create project from template.
     */
    public static function createFromTemplate(ProjectTemplate $template, array $customData = []): Project
    {
        $projectData = array_merge($template->getTemplateData(), $customData);
        $projectData['template_id'] = $template->id;
        $projectData['is_template'] = false;

        $project = self::create($projectData);

        // Copy template tasks, milestones, etc.
        $template->copyToProject($project);

        return $project;
    }

    /**
     * Scope to get active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get completed projects.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get projects by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get overdue projects.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED])
            ->whereNotNull('due')
            ->where('due', '<', Carbon::now());
    }

    /**
     * Scope to get projects due soon.
     */
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED])
            ->whereNotNull('due')
            ->where('due', '>=', Carbon::now())
            ->where('due', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Scope to search projects.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhere('project_code', 'like', '%'.$search.'%')
                ->orWhere('category', 'like', '%'.$search.'%');
        });
    }

    /**
     * Scope to get projects by client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get projects by manager.
     */
    public function scopeForManager($query, int $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    /**
     * Scope to get projects by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get projects by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get billable projects.
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope to get project templates.
     */
    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    /**
     * Get validation rules for project creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:'.implode(',', self::getAvailableStatuses()),
            'priority' => 'required|in:'.implode(',', self::getAvailablePriorities()),
            'client_id' => 'nullable|integer|exists:clients,id',
            'manager_id' => 'nullable|integer|exists:users,id',
            'start_date' => 'nullable|date',
            'due' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'budget_currency' => 'nullable|string|size:3',
            'category' => 'nullable|in:'.implode(',', self::getAvailableCategories()),
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_billable' => 'boolean',
            'is_template' => 'boolean',
            'template_id' => 'nullable|integer|exists:projects,id',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PLANNING,
            self::STATUS_ACTIVE,
            self::STATUS_ON_HOLD,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_ARCHIVED,
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
            self::CATEGORY_MARKETING,
            self::CATEGORY_MAINTENANCE,
            self::CATEGORY_CONSULTING,
            self::CATEGORY_SUPPORT,
            self::CATEGORY_RESEARCH,
            self::CATEGORY_OTHER,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate project code for new projects
        static::creating(function ($project) {
            if (! $project->project_code) {
                $project->project_code = static::generateProjectCode($project->company_id);
            }

            // Set company_id from authenticated user if not set
            if (! $project->company_id && auth()->user()) {
                $project->company_id = auth()->user()->company_id;
            }
        });

        // Update progress when project is updated
        static::updating(function ($project) {
            if ($project->isDirty('status') && $project->status === self::STATUS_COMPLETED) {
                $project->progress_percentage = 100;
                if (! $project->actual_end_date) {
                    $project->actual_end_date = now();
                }
            }
        });
    }

    /**
     * Generate a unique project code.
     */
    protected static function generateProjectCode(int $companyId): string
    {
        $year = date('Y');
        $prefix = 'PRJ-'.$year.'-';

        $lastProject = static::where('company_id', $companyId)
            ->where('project_code', 'like', $prefix.'%')
            ->orderBy('project_code', 'desc')
            ->first();

        if ($lastProject) {
            $lastNumber = intval(substr($lastProject->project_code, strlen($prefix)));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
