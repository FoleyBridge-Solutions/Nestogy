<?php

namespace Foleybridge\Nestogy\Domains\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * ProjectMember Model
 * 
 * Manages project team members with roles, permissions, and hourly rates.
 * 
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $role
 * @property decimal|null $hourly_rate
 * @property string|null $currency
 * @property bool $can_edit
 * @property bool $can_manage_tasks
 * @property bool $can_manage_time
 * @property bool $can_view_reports
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ProjectMember extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'project_members';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'hourly_rate',
        'currency',
        'can_edit',
        'can_manage_tasks',
        'can_manage_time',
        'can_view_reports',
        'is_active',
        'joined_at',
        'left_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'project_id' => 'integer',
        'user_id' => 'integer',
        'hourly_rate' => 'decimal:2',
        'can_edit' => 'boolean',
        'can_manage_tasks' => 'boolean',
        'can_manage_time' => 'boolean',
        'can_view_reports' => 'boolean',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Project role enumeration
     */
    const ROLE_MANAGER = 'manager';
    const ROLE_LEAD = 'lead';
    const ROLE_DEVELOPER = 'developer';
    const ROLE_DESIGNER = 'designer';
    const ROLE_TESTER = 'tester';
    const ROLE_ANALYST = 'analyst';
    const ROLE_CONSULTANT = 'consultant';
    const ROLE_COORDINATOR = 'coordinator';
    const ROLE_CLIENT = 'client';
    const ROLE_OBSERVER = 'observer';

    /**
     * Get the project that owns the member.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user associated with this membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the role with human-readable formatting.
     */
    public function getRoleLabel(): string
    {
        return match($this->role) {
            self::ROLE_MANAGER => 'Project Manager',
            self::ROLE_LEAD => 'Team Lead',
            self::ROLE_DEVELOPER => 'Developer',
            self::ROLE_DESIGNER => 'Designer',
            self::ROLE_TESTER => 'Tester',
            self::ROLE_ANALYST => 'Business Analyst',
            self::ROLE_CONSULTANT => 'Consultant',
            self::ROLE_COORDINATOR => 'Coordinator',
            self::ROLE_CLIENT => 'Client Representative',
            self::ROLE_OBSERVER => 'Observer',
            default => 'Team Member',
        };
    }

    /**
     * Check if member is active in the project.
     */
    public function isActive(): bool
    {
        return $this->is_active && is_null($this->left_at);
    }

    /**
     * Check if member has management permissions.
     */
    public function canManageProject(): bool
    {
        return $this->role === self::ROLE_MANAGER || $this->can_edit;
    }

    /**
     * Check if member can manage tasks.
     */
    public function canManageTasks(): bool
    {
        return $this->canManageProject() || $this->can_manage_tasks;
    }

    /**
     * Check if member can manage time tracking.
     */
    public function canManageTime(): bool
    {
        return $this->canManageProject() || $this->can_manage_time;
    }

    /**
     * Check if member can view reports.
     */
    public function canViewReports(): bool
    {
        return $this->canManageProject() || $this->can_view_reports;
    }

    /**
     * Get member's workload for the project.
     */
    public function getWorkloadHours(Carbon $startDate = null, Carbon $endDate = null): float
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth();
        $endDate = $endDate ?: Carbon::now()->endOfMonth();

        return $this->project->timeEntries()
            ->where('user_id', $this->user_id)
            ->whereBetween('logged_at', [$startDate, $endDate])
            ->sum('hours') ?? 0;
    }

    /**
     * Get member's assigned tasks count.
     */
    public function getAssignedTasksCount(): int
    {
        return $this->project->tasks()
            ->where('assigned_to', $this->user_id)
            ->count();
    }

    /**
     * Get member's completed tasks count.
     */
    public function getCompletedTasksCount(): int
    {
        return $this->project->tasks()
            ->where('assigned_to', $this->user_id)
            ->whereIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])
            ->count();
    }

    /**
     * Get member's task completion rate.
     */
    public function getTaskCompletionRate(): float
    {
        $totalTasks = $this->getAssignedTasksCount();
        if ($totalTasks === 0) {
            return 0;
        }

        return round(($this->getCompletedTasksCount() / $totalTasks) * 100, 2);
    }

    /**
     * Get member's total billable hours.
     */
    public function getTotalBillableHours(): float
    {
        return $this->project->timeEntries()
            ->where('user_id', $this->user_id)
            ->where('is_billable', true)
            ->sum('hours') ?? 0;
    }

    /**
     * Get member's total earnings.
     */
    public function getTotalEarnings(): float
    {
        if (!$this->hourly_rate) {
            return 0;
        }

        return $this->getTotalBillableHours() * $this->hourly_rate;
    }

    /**
     * Get member statistics.
     */
    public function getStatistics(): array
    {
        return [
            'tasks' => [
                'assigned' => $this->getAssignedTasksCount(),
                'completed' => $this->getCompletedTasksCount(),
                'completion_rate' => $this->getTaskCompletionRate(),
            ],
            'time' => [
                'total_hours' => $this->getWorkloadHours(),
                'billable_hours' => $this->getTotalBillableHours(),
                'earnings' => $this->getTotalEarnings(),
            ],
            'membership' => [
                'duration_days' => $this->getMembershipDuration(),
                'is_active' => $this->isActive(),
            ],
        ];
    }

    /**
     * Get membership duration in days.
     */
    public function getMembershipDuration(): int
    {
        $startDate = $this->joined_at ?: $this->created_at;
        $endDate = $this->left_at ?: Carbon::now();

        return $startDate->diffInDays($endDate);
    }

    /**
     * Activate member.
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'left_at' => null,
        ]);
    }

    /**
     * Deactivate member.
     */
    public function deactivate(string $reason = null): void
    {
        $this->update([
            'is_active' => false,
            'left_at' => now(),
        ]);

        if ($reason) {
            $this->update(['notes' => $this->notes . "\n\nDeactivated: {$reason}"]);
        }
    }

    /**
     * Update member permissions.
     */
    public function updatePermissions(array $permissions): void
    {
        $allowedPermissions = [
            'can_edit',
            'can_manage_tasks',
            'can_manage_time',
            'can_view_reports',
        ];

        $updateData = [];
        foreach ($permissions as $permission => $value) {
            if (in_array($permission, $allowedPermissions)) {
                $updateData[$permission] = (bool) $value;
            }
        }

        if (!empty($updateData)) {
            $this->update($updateData);
        }
    }

    /**
     * Clone member to another project.
     */
    public function cloneToProject(Project $project): ProjectMember
    {
        $attributes = $this->toArray();
        unset($attributes['id'], $attributes['project_id'], $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);
        
        $attributes['project_id'] = $project->id;
        $attributes['joined_at'] = now();
        
        return ProjectMember::create($attributes);
    }

    /**
     * Scope to get active members.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('left_at');
    }

    /**
     * Scope to get members by role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get managers.
     */
    public function scopeManagers($query)
    {
        return $query->where('role', self::ROLE_MANAGER);
    }

    /**
     * Scope to get members who can edit.
     */
    public function scopeCanEdit($query)
    {
        return $query->where(function ($q) {
            $q->where('can_edit', true)
              ->orWhere('role', self::ROLE_MANAGER);
        });
    }

    /**
     * Scope to get billable members.
     */
    public function scopeBillable($query)
    {
        return $query->whereNotNull('hourly_rate')->where('hourly_rate', '>', 0);
    }

    /**
     * Get validation rules for member creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects,id',
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|in:' . implode(',', self::getAvailableRoles()),
            'hourly_rate' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'can_edit' => 'boolean',
            'can_manage_tasks' => 'boolean',
            'can_manage_time' => 'boolean',
            'can_view_reports' => 'boolean',
            'is_active' => 'boolean',
            'joined_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get available roles.
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_MANAGER,
            self::ROLE_LEAD,
            self::ROLE_DEVELOPER,
            self::ROLE_DESIGNER,
            self::ROLE_TESTER,
            self::ROLE_ANALYST,
            self::ROLE_CONSULTANT,
            self::ROLE_COORDINATOR,
            self::ROLE_CLIENT,
            self::ROLE_OBSERVER,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default permissions based on role
        static::creating(function ($member) {
            if (!$member->joined_at) {
                $member->joined_at = now();
            }

            // Set default permissions based on role
            if ($member->role === self::ROLE_MANAGER) {
                $member->can_edit = true;
                $member->can_manage_tasks = true;
                $member->can_manage_time = true;
                $member->can_view_reports = true;
            } elseif ($member->role === self::ROLE_LEAD) {
                $member->can_manage_tasks = $member->can_manage_tasks ?? true;
                $member->can_view_reports = $member->can_view_reports ?? true;
            }

            $member->is_active = $member->is_active ?? true;
        });

        // Ensure unique user per project
        static::creating(function ($member) {
            $exists = static::where('project_id', $member->project_id)
                ->where('user_id', $member->user_id)
                ->exists();

            if ($exists) {
                throw new \Exception('User is already a member of this project.');
            }
        });
    }
}