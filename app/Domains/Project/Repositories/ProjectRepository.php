<?php

namespace App\Domains\Project\Repositories;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\Task;
use App\Domains\Project\Models\ProjectMilestone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectRepository
{
    /**
     * Get projects with filters and pagination
     */
    public function getProjectsWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Project::with(['client', 'manager', 'members.user', 'tasks', 'milestones']);

        // Apply company filter
        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Apply search
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%")
                  ->orWhere('project_code', 'like', "%{$filters['search']}%");
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Apply priority filter
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Apply client filter
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Apply manager filter
        if (!empty($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }

        // Apply date range filter
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Get project by ID with all relationships
     */
    public function getProjectWithRelations(int $projectId): ?Project
    {
        return Project::with([
            'client',
            'manager',
            'members.user',
            'tasks.assignedUser',
            'tasks.dependencies',
            'milestones.tasks',
            'timeEntries.user',
            'comments.user',
            'files.uploadedBy',
            'expenses',
        ])->find($projectId);
    }

    /**
     * Get active projects for a company
     */
    public function getActiveProjects(int $companyId): Collection
    {
        return Project::where('company_id', $companyId)
            ->where('status', Project::STATUS_ACTIVE)
            ->with(['client', 'manager'])
            ->orderBy('priority', 'desc')
            ->orderBy('due', 'asc')
            ->get();
    }

    /**
     * Get overdue projects
     */
    public function getOverdueProjects(int $companyId): Collection
    {
        return Project::where('company_id', $companyId)
            ->whereNotIn('status', [Project::STATUS_COMPLETED, Project::STATUS_CANCELLED])
            ->where('due', '<', now())
            ->with(['client', 'manager'])
            ->orderBy('due', 'asc')
            ->get();
    }

    /**
     * Get projects due soon (within specified days)
     */
    public function getProjectsDueSoon(int $companyId, int $days = 7): Collection
    {
        return Project::where('company_id', $companyId)
            ->whereNotIn('status', [Project::STATUS_COMPLETED, Project::STATUS_CANCELLED])
            ->whereBetween('due', [now(), now()->addDays($days)])
            ->with(['client', 'manager'])
            ->orderBy('due', 'asc')
            ->get();
    }

    /**
     * Get projects by client
     */
    public function getProjectsByClient(int $clientId): Collection
    {
        return Project::where('client_id', $clientId)
            ->with(['manager', 'tasks', 'milestones'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get projects by manager
     */
    public function getProjectsByManager(int $managerId): Collection
    {
        return Project::where('manager_id', $managerId)
            ->with(['client', 'members', 'tasks'])
            ->orderBy('priority', 'desc')
            ->orderBy('due', 'asc')
            ->get();
    }

    /**
     * Get projects for a team member
     */
    public function getProjectsForMember(int $userId): Collection
    {
        return Project::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->where('is_active', true);
        })
        ->with(['client', 'manager'])
        ->orderBy('priority', 'desc')
        ->get();
    }

    /**
     * Get project statistics for dashboard
     */
    public function getProjectStatistics(int $companyId): array
    {
        $baseQuery = Project::where('company_id', $companyId);

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', Project::STATUS_ACTIVE)->count(),
            'completed' => (clone $baseQuery)->where('status', Project::STATUS_COMPLETED)->count(),
            'on_hold' => (clone $baseQuery)->where('status', Project::STATUS_ON_HOLD)->count(),
            'planning' => (clone $baseQuery)->where('status', Project::STATUS_PLANNING)->count(),
            'overdue' => (clone $baseQuery)
                ->whereNotIn('status', [Project::STATUS_COMPLETED, Project::STATUS_CANCELLED])
                ->where('due', '<', now())
                ->count(),
            'due_this_week' => (clone $baseQuery)
                ->whereNotIn('status', [Project::STATUS_COMPLETED, Project::STATUS_CANCELLED])
                ->whereBetween('due', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'total_budget' => (clone $baseQuery)->sum('budget'),
            'total_spent' => (clone $baseQuery)->sum('actual_cost'),
        ];
    }

    /**
     * Get project timeline data
     */
    public function getProjectTimeline(int $projectId): array
    {
        $project = $this->getProjectWithRelations($projectId);
        
        if (!$project) {
            return [];
        }

        $timeline = [];

        // Add project milestones to timeline
        foreach ($project->milestones as $milestone) {
            $timeline[] = [
                'type' => 'milestone',
                'id' => $milestone->id,
                'title' => $milestone->name,
                'date' => $milestone->due_date,
                'status' => $milestone->status,
                'is_critical' => $milestone->is_critical,
            ];
        }

        // Add task deadlines to timeline
        foreach ($project->tasks as $task) {
            if ($task->due_date) {
                $timeline[] = [
                    'type' => 'task',
                    'id' => $task->id,
                    'title' => $task->name,
                    'date' => $task->due_date,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'assignee' => $task->assignedUser?->name,
                ];
            }
        }

        // Sort timeline by date
        usort($timeline, function ($a, $b) {
            return $a['date']->timestamp - $b['date']->timestamp;
        });

        return $timeline;
    }

    /**
     * Get project resource allocation
     */
    public function getProjectResourceAllocation(int $projectId): array
    {
        $project = Project::with(['members.user', 'tasks'])->find($projectId);
        
        if (!$project) {
            return [];
        }

        $allocation = [];

        foreach ($project->members as $member) {
            $userTasks = $project->tasks->where('assigned_to', $member->user_id);
            
            $allocation[] = [
                'user_id' => $member->user_id,
                'user_name' => $member->user->name,
                'role' => $member->role,
                'availability' => $member->availability_percentage ?? 100,
                'tasks_count' => $userTasks->count(),
                'tasks_in_progress' => $userTasks->where('status', Task::STATUS_IN_PROGRESS)->count(),
                'estimated_hours' => $userTasks->sum('estimated_hours'),
                'actual_hours' => $userTasks->sum('actual_hours'),
            ];
        }

        return $allocation;
    }

    /**
     * Get project budget breakdown
     */
    public function getProjectBudgetBreakdown(int $projectId): array
    {
        $project = Project::with(['timeEntries', 'expenses'])->find($projectId);
        
        if (!$project) {
            return [];
        }

        $laborCost = 0;
        foreach ($project->timeEntries as $entry) {
            $member = $project->members()->where('user_id', $entry->user_id)->first();
            $hourlyRate = $member?->hourly_rate ?? 50;
            $laborCost += ($entry->hours * $hourlyRate);
        }

        $expensesCost = $project->expenses()->sum('amount');

        return [
            'budget' => $project->budget,
            'labor_cost' => $laborCost,
            'expenses_cost' => $expensesCost,
            'total_cost' => $laborCost + $expensesCost,
            'remaining' => $project->budget - ($laborCost + $expensesCost),
            'utilization_percentage' => $project->budget > 0 
                ? round((($laborCost + $expensesCost) / $project->budget) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Search projects
     */
    public function searchProjects(string $query, int $companyId, int $limit = 10): Collection
    {
        return Project::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('project_code', 'like', "%{$query}%");
            })
            ->with(['client', 'manager'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent projects
     */
    public function getRecentProjects(int $companyId, int $limit = 10): Collection
    {
        return Project::where('company_id', $companyId)
            ->with(['client', 'manager'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get project completion trends
     */
    public function getProjectCompletionTrends(int $companyId, int $months = 6): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        
        $completions = Project::where('company_id', $companyId)
            ->where('status', Project::STATUS_COMPLETED)
            ->where('completed_at', '>=', $startDate)
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as month')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(DATEDIFF(completed_at, created_at)) as avg_duration')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $completions->toArray();
    }

    /**
     * Clone project
     */
    public function cloneProject(Project $project, array $overrides = []): Project
    {
        DB::beginTransaction();
        
        try {
            // Clone the project
            $newProject = $project->replicate();
            $newProject->fill($overrides);
            $newProject->status = Project::STATUS_PLANNING;
            $newProject->progress_percentage = 0;
            $newProject->actual_start_date = null;
            $newProject->actual_end_date = null;
            $newProject->actual_cost = null;
            $newProject->save();

            // Clone tasks if requested
            if ($overrides['clone_tasks'] ?? false) {
                foreach ($project->tasks as $task) {
                    $newTask = $task->replicate();
                    $newTask->project_id = $newProject->id;
                    $newTask->status = Task::STATUS_TODO;
                    $newTask->progress_percentage = 0;
                    $newTask->save();
                }
            }

            // Clone milestones if requested
            if ($overrides['clone_milestones'] ?? false) {
                foreach ($project->milestones as $milestone) {
                    $newMilestone = $milestone->replicate();
                    $newMilestone->project_id = $newProject->id;
                    $newMilestone->status = ProjectMilestone::STATUS_PENDING;
                    $newMilestone->completed_at = null;
                    $newMilestone->save();
                }
            }

            // Clone team members if requested
            if ($overrides['clone_members'] ?? false) {
                foreach ($project->members as $member) {
                    $newMember = $member->replicate();
                    $newMember->project_id = $newProject->id;
                    $newMember->joined_at = now();
                    $newMember->left_at = null;
                    $newMember->save();
                }
            }

            DB::commit();
            return $newProject;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Archive old projects
     */
    public function archiveOldProjects(int $companyId, int $daysOld = 365): int
    {
        return Project::where('company_id', $companyId)
            ->where('status', Project::STATUS_COMPLETED)
            ->where('completed_at', '<', now()->subDays($daysOld))
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);
    }
}