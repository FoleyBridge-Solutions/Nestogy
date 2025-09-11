<?php

namespace App\Domains\Project\Services;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\ProjectMember;
use App\Domains\Project\Models\Task;
use App\Domains\Project\Repositories\ProjectRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ProjectService
{
    public function __construct(
        protected ProjectRepository $repository
    ) {}

    /**
     * Get comprehensive project dashboard data
     */
    public function getProjectDashboard(Project $project): array
    {
        return Cache::remember("project_dashboard_{$project->id}", 300, function () use ($project) {
            return [
                'overview' => $this->getProjectOverview($project),
                'statistics' => $this->getProjectStatistics($project),
                'timeline' => $this->getProjectTimeline($project),
                'team' => $this->getTeamMetrics($project),
                'budget' => $this->getBudgetMetrics($project),
                'health' => $this->getHealthMetrics($project),
                'risks' => $this->getRiskAssessment($project),
                'milestones' => $this->getMilestoneProgress($project),
                'tasks' => $this->getTaskMetrics($project),
                'activity' => $this->getRecentActivity($project),
            ];
        });
    }

    /**
     * Get project overview data
     */
    public function getProjectOverview(Project $project): array
    {
        $now = Carbon::now();
        $startDate = $project->start_date ?? $project->created_at;
        $dueDate = $project->due;

        $totalDuration = $startDate && $dueDate ? $startDate->diffInDays($dueDate) : 0;
        $elapsedDays = $startDate ? $startDate->diffInDays($now) : 0;
        $remainingDays = $dueDate && $dueDate->isFuture() ? $now->diffInDays($dueDate) : 0;

        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'status' => $project->status,
            'status_label' => $project->getStatusLabel(),
            'priority' => $project->priority,
            'priority_label' => $project->getPriorityLabel(),
            'client' => $project->client ? [
                'id' => $project->client->id,
                'name' => $project->client->name,
                'logo' => $project->client->logo_url ?? null,
            ] : null,
            'manager' => $project->manager ? [
                'id' => $project->manager->id,
                'name' => $project->manager->name,
                'avatar' => $project->manager->avatar_url ?? null,
            ] : null,
            'dates' => [
                'start' => $startDate?->format('Y-m-d'),
                'due' => $dueDate?->format('Y-m-d'),
                'completed' => $project->completed_at?->format('Y-m-d'),
                'created' => $project->created_at->format('Y-m-d'),
            ],
            'duration' => [
                'total_days' => $totalDuration,
                'elapsed_days' => $elapsedDays,
                'remaining_days' => $remainingDays,
                'progress_percentage' => $totalDuration > 0 ? round(($elapsedDays / $totalDuration) * 100) : 0,
            ],
            'completion' => [
                'percentage' => $project->getCalculatedProgress(),
                'expected' => $project->getExpectedProgress(),
                'variance' => $project->getCalculatedProgress() - $project->getExpectedProgress(),
            ],
        ];
    }

    /**
     * Get project statistics
     */
    public function getProjectStatistics(Project $project): array
    {
        $tasks = $project->tasks;
        $milestones = $project->milestones;
        $members = $project->members()->active()->get();

        return [
            'tasks' => [
                'total' => $tasks->count(),
                'completed' => $tasks->where('status', Task::STATUS_COMPLETED)->count(),
                'in_progress' => $tasks->where('status', Task::STATUS_IN_PROGRESS)->count(),
                'todo' => $tasks->where('status', Task::STATUS_TODO)->count(),
                'overdue' => $tasks->filter(fn ($task) => $task->isOverdue())->count(),
                'completion_rate' => $tasks->count() > 0
                    ? round(($tasks->where('status', Task::STATUS_COMPLETED)->count() / $tasks->count()) * 100)
                    : 0,
            ],
            'milestones' => [
                'total' => $milestones->count(),
                'completed' => $milestones->filter(fn ($m) => $m->isCompleted())->count(),
                'upcoming' => $milestones->filter(fn ($m) => ! $m->isCompleted() && $m->due_date?->isFuture())->count(),
                'overdue' => $milestones->filter(fn ($m) => ! $m->isCompleted() && $m->due_date?->isPast())->count(),
                'critical' => $milestones->where('is_critical', true)->count(),
            ],
            'team' => [
                'total_members' => $members->count(),
                'active_members' => $members->count(), // All members without left_at are active
                'total_hours_logged' => 0, // TODO: Implement when ProjectTimeEntry model is available
                'average_utilization' => $this->calculateTeamUtilization($project),
            ],
            'time' => [
                'estimated_hours' => $tasks->sum('estimated_hours'),
                'actual_hours' => $tasks->sum('actual_hours'),
                'remaining_hours' => $tasks->where('status', '!=', Task::STATUS_COMPLETED)->sum('estimated_hours'),
                'efficiency' => $this->calculateTimeEfficiency($project),
            ],
        ];
    }

    /**
     * Get project timeline data for visualization
     */
    public function getProjectTimeline(Project $project): array
    {
        $tasks = $project->tasks()
            ->whereNotNull('start_date')
            ->orderBy('start_date')
            ->get();

        $milestones = $project->milestones()
            ->orderBy('due_date')
            ->get();

        $timeline = [];

        // Add project as main timeline item
        $timeline[] = [
            'id' => "project_{$project->id}",
            'type' => 'project',
            'name' => $project->name,
            'start' => $project->start_date?->format('Y-m-d') ?? $project->created_at->format('Y-m-d'),
            'end' => $project->due?->format('Y-m-d'),
            'progress' => $project->getCalculatedProgress(),
            'status' => $project->status,
            'color' => $this->getStatusColor($project->status),
            'children' => [],
        ];

        // Add tasks to timeline
        foreach ($tasks as $task) {
            $timeline[0]['children'][] = [
                'id' => "task_{$task->id}",
                'type' => 'task',
                'name' => $task->name,
                'start' => $task->start_date?->format('Y-m-d'),
                'end' => $task->due_date?->format('Y-m-d'),
                'progress' => $task->progress_percentage,
                'status' => $task->status,
                'assignee' => $task->assignedUser?->name,
                'color' => $this->getPriorityColor($task->priority),
                'dependencies' => $task->dependencies->pluck('id')->map(fn ($id) => "task_{$id}")->toArray(),
            ];
        }

        // Add milestones as markers
        foreach ($milestones as $milestone) {
            $timeline[0]['children'][] = [
                'id' => "milestone_{$milestone->id}",
                'type' => 'milestone',
                'name' => $milestone->name,
                'date' => $milestone->due_date?->format('Y-m-d'),
                'completed' => $milestone->isCompleted(),
                'critical' => $milestone->is_critical,
                'color' => $milestone->is_critical ? '#ef4444' : '#6366f1',
            ];
        }

        return $timeline;
    }

    /**
     * Get team metrics
     */
    public function getTeamMetrics(Project $project): array
    {
        $members = $project->members()->with('user')->active()->get();

        $teamData = [];
        foreach ($members as $member) {
            $userTasks = $project->tasks()->where('assigned_to', $member->user_id)->get();
            $completedTasks = $userTasks->where('status', Task::STATUS_COMPLETED)->count();
            $totalHours = 0; // TODO: Implement when ProjectTimeEntry model is available

            $teamData[] = [
                'id' => $member->user_id,
                'name' => $member->user->name,
                'role' => $member->role,
                'avatar' => $member->user->avatar_url ?? null,
                'joined_date' => $member->joined_at?->format('Y-m-d'),
                'tasks' => [
                    'total' => $userTasks->count(),
                    'completed' => $completedTasks,
                    'in_progress' => $userTasks->where('status', Task::STATUS_IN_PROGRESS)->count(),
                    'overdue' => $userTasks->filter(fn ($task) => $task->isOverdue())->count(),
                ],
                'hours' => [
                    'logged' => $totalHours,
                    'estimated' => $userTasks->sum('estimated_hours'),
                    'remaining' => $userTasks->where('status', '!=', Task::STATUS_COMPLETED)->sum('estimated_hours'),
                ],
                'productivity' => [
                    'completion_rate' => $userTasks->count() > 0
                        ? round(($completedTasks / $userTasks->count()) * 100)
                        : 0,
                    'efficiency' => $this->calculateMemberEfficiency($member, $userTasks),
                ],
                'availability' => $member->availability_percentage ?? 100,
                'permissions' => [
                    'can_edit' => $member->can_edit,
                    'can_manage_tasks' => $member->can_manage_tasks,
                    'can_view_reports' => $member->can_view_reports,
                ],
            ];
        }

        $memberCount = count($teamData);

        return [
            'members' => $teamData,
            'summary' => [
                'total_members' => $memberCount,
                'total_capacity' => $memberCount > 0
                    ? array_sum(array_column($teamData, 'availability')) / $memberCount
                    : 0,
                'average_productivity' => $memberCount > 0
                    ? array_sum(array_column(array_column($teamData, 'productivity'), 'completion_rate')) / $memberCount
                    : 0,
            ],
        ];
    }

    /**
     * Get budget metrics
     */
    public function getBudgetMetrics(Project $project): array
    {
        $actualCost = $project->actual_cost ?? 0;
        $budget = $project->budget ?? 0;
        $laborCost = $this->calculateLaborCost($project);
        $expensesCost = 0; // TODO: Implement when ProjectExpense model is available

        return [
            'budget' => $budget,
            'actual_cost' => $actualCost,
            'labor_cost' => $laborCost,
            'expenses_cost' => $expensesCost,
            'total_cost' => $laborCost + $expensesCost,
            'remaining_budget' => $budget - ($laborCost + $expensesCost),
            'budget_utilization' => $budget > 0 ? round((($laborCost + $expensesCost) / $budget) * 100) : 0,
            'variance' => $budget - ($laborCost + $expensesCost),
            'variance_percentage' => $budget > 0 ? round(((($budget - ($laborCost + $expensesCost)) / $budget) * 100)) : 0,
            'burn_rate' => $this->calculateBurnRate($project),
            'projected_cost' => $this->projectFinalCost($project),
            'cost_performance_index' => $this->calculateCPI($project),
            'currency' => $project->budget_currency ?? 'USD',
        ];
    }

    /**
     * Get health metrics
     */
    public function getHealthMetrics(Project $project): array
    {
        $health = $project->getHealthStatus();
        $risks = $this->identifyRisks($project);
        $score = $this->calculateHealthScore($project);

        return [
            'overall_status' => $health['status'],
            'health_score' => $score,
            'indicators' => [
                'schedule' => $this->assessScheduleHealth($project),
                'budget' => $this->assessBudgetHealth($project),
                'scope' => $this->assessScopeHealth($project),
                'team' => $this->assessTeamHealth($project),
                'quality' => $this->assessQualityHealth($project),
            ],
            'risks' => $risks,
            'recommendations' => $this->generateRecommendations($project, $health, $risks),
        ];
    }

    /**
     * Get risk assessment
     */
    public function getRiskAssessment(Project $project): array
    {
        $risks = [];

        // Schedule risks
        if ($project->due && $project->due->isPast() && $project->status !== Project::STATUS_COMPLETED) {
            $risks[] = [
                'type' => 'schedule',
                'severity' => 'high',
                'title' => 'Project Overdue',
                'description' => "Project is {$project->due->diffInDays(now())} days overdue",
                'mitigation' => 'Review timeline and reallocate resources or adjust deadline',
            ];
        }

        // Budget risks
        $budgetMetrics = $this->getBudgetMetrics($project);
        if ($budgetMetrics['budget_utilization'] > 90) {
            $risks[] = [
                'type' => 'budget',
                'severity' => $budgetMetrics['budget_utilization'] > 100 ? 'critical' : 'high',
                'title' => 'Budget Risk',
                'description' => "Budget utilization at {$budgetMetrics['budget_utilization']}%",
                'mitigation' => 'Review expenses and consider budget reallocation',
            ];
        }

        // Resource risks
        $teamMetrics = $this->getTeamMetrics($project);
        if ($teamMetrics['summary']['total_members'] < 2) {
            $risks[] = [
                'type' => 'resource',
                'severity' => 'medium',
                'title' => 'Limited Resources',
                'description' => 'Project has minimal team members',
                'mitigation' => 'Consider adding team members for critical tasks',
            ];
        }

        // Task completion risks
        $stats = $this->getProjectStatistics($project);
        if ($stats['tasks']['overdue'] > 5) {
            $risks[] = [
                'type' => 'execution',
                'severity' => 'high',
                'title' => 'Multiple Overdue Tasks',
                'description' => "{$stats['tasks']['overdue']} tasks are overdue",
                'mitigation' => 'Prioritize overdue tasks and reassign if needed',
            ];
        }

        return $risks;
    }

    /**
     * Get milestone progress
     */
    public function getMilestoneProgress(Project $project): array
    {
        $milestones = $project->milestones()->orderBy('due_date')->get();

        return $milestones->map(function ($milestone) {
            $tasks = $milestone->tasks;
            $completedTasks = $tasks->where('status', Task::STATUS_COMPLETED)->count();

            return [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'description' => $milestone->description,
                'due_date' => $milestone->due_date?->format('Y-m-d'),
                'is_critical' => $milestone->is_critical,
                'status' => $milestone->status,
                'progress' => [
                    'percentage' => $milestone->completion_percentage,
                    'tasks_total' => $tasks->count(),
                    'tasks_completed' => $completedTasks,
                ],
                'is_completed' => $milestone->isCompleted(),
                'is_overdue' => ! $milestone->isCompleted() && $milestone->due_date?->isPast(),
                'days_remaining' => $milestone->due_date && ! $milestone->isCompleted()
                    ? ($milestone->due_date->isFuture() ? $milestone->due_date->diffInDays(now()) : 0)
                    : null,
            ];
        })->toArray();
    }

    /**
     * Get task metrics
     */
    public function getTaskMetrics(Project $project): array
    {
        $tasks = $project->tasks;

        // Group tasks by status
        $tasksByStatus = $tasks->groupBy('status')->map(fn ($group) => $group->count());

        // Group tasks by priority
        $tasksByPriority = $tasks->groupBy('priority')->map(fn ($group) => $group->count());

        // Group tasks by assignee
        $tasksByAssignee = $tasks->groupBy('assigned_to')->map(function ($group, $userId) {
            // Handle null or empty assigned_to values
            if (empty($userId)) {
                return [
                    'user' => 'Unassigned',
                    'count' => $group->count(),
                    'completed' => $group->where('status', Task::STATUS_COMPLETED)->count(),
                ];
            }

            $user = \App\Models\User::find($userId);

            return [
                'user' => $user ? $user->name : 'Unassigned',
                'count' => $group->count(),
                'completed' => $group->where('status', Task::STATUS_COMPLETED)->count(),
            ];
        });

        // Calculate velocity (tasks completed per week)
        $completedTasks = $tasks->where('status', Task::STATUS_COMPLETED);
        $velocity = $this->calculateVelocity($completedTasks);

        return [
            'by_status' => $tasksByStatus->toArray(),
            'by_priority' => $tasksByPriority->toArray(),
            'by_assignee' => $tasksByAssignee->values()->toArray(),
            'velocity' => $velocity,
            'burndown' => $this->generateBurndownData($project),
            'upcoming' => $tasks->filter(fn ($task) => $task->due_date &&
                $task->due_date->isFuture() &&
                $task->due_date->diffInDays(now()) <= 7
            )->count(),
        ];
    }

    /**
     * Get recent project activity
     */
    public function getRecentActivity(Project $project, int $limit = 20): array
    {
        $activities = [];

        // Recent tasks
        $recentTasks = $project->tasks()
            ->with('assignedUser')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($recentTasks as $task) {
            $activities[] = [
                'type' => 'task',
                'action' => 'created',
                'title' => "Task created: {$task->name}",
                'user' => $task->createdBy?->name ?? 'System',
                'timestamp' => $task->created_at,
                'metadata' => [
                    'task_id' => $task->id,
                    'assignee' => $task->assignedUser?->name,
                ],
            ];
        }

        // Recent comments
        $recentComments = $project->comments()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($recentComments as $comment) {
            $activities[] = [
                'type' => 'comment',
                'action' => 'added',
                'title' => 'Comment added',
                'user' => $comment->user?->name ?? 'Unknown',
                'timestamp' => $comment->created_at,
                'metadata' => [
                    'comment_preview' => \Str::limit($comment->content, 100),
                ],
            ];
        }

        // Recent time entries - TODO: Implement when ProjectTimeEntry model is available
        $recentTimeEntries = collect([]);

        foreach ($recentTimeEntries as $entry) {
            $activities[] = [
                'type' => 'time_entry',
                'action' => 'logged',
                'title' => "{$entry->hours} hours logged",
                'user' => $entry->user?->name ?? 'Unknown',
                'timestamp' => $entry->created_at,
                'metadata' => [
                    'task' => $entry->task?->name,
                    'description' => $entry->description,
                ],
            ];
        }

        // Sort by timestamp and limit
        usort($activities, fn ($a, $b) => $b['timestamp']->timestamp - $a['timestamp']->timestamp);

        return array_slice($activities, 0, $limit);
    }

    /**
     * Calculate team utilization
     */
    protected function calculateTeamUtilization(Project $project): float
    {
        $members = $project->members()->active()->get();
        if ($members->isEmpty()) {
            return 0;
        }

        $totalUtilization = 0;
        foreach ($members as $member) {
            $assignedTasks = $project->tasks()
                ->where('assigned_to', $member->user_id)
                ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])
                ->count();

            $utilization = min(100, ($assignedTasks * 20)); // Assume 20% utilization per task
            $totalUtilization += $utilization;
        }

        return round($totalUtilization / $members->count());
    }

    /**
     * Calculate time efficiency
     */
    protected function calculateTimeEfficiency(Project $project): float
    {
        $tasks = $project->tasks()->where('status', Task::STATUS_COMPLETED)->get();

        $totalEstimated = $tasks->sum('estimated_hours');
        $totalActual = $tasks->sum('actual_hours');

        if ($totalEstimated == 0 || $totalActual == 0) {
            return 100;
        }

        return round(($totalEstimated / $totalActual) * 100);
    }

    /**
     * Calculate member efficiency
     */
    protected function calculateMemberEfficiency(ProjectMember $member, $tasks): float
    {
        $completedTasks = $tasks->where('status', Task::STATUS_COMPLETED);

        $estimatedHours = $completedTasks->sum('estimated_hours');
        $actualHours = $completedTasks->sum('actual_hours');

        if ($estimatedHours == 0 || $actualHours == 0) {
            return 100;
        }

        return round(($estimatedHours / $actualHours) * 100);
    }

    /**
     * Calculate labor cost
     */
    protected function calculateLaborCost(Project $project): float
    {
        // TODO: Implement when ProjectTimeEntry model is available
        // For now, return a placeholder value based on project budget
        return $project->budget ? $project->budget * 0.6 : 0; // Assume 60% of budget is labor
    }

    /**
     * Calculate burn rate
     */
    protected function calculateBurnRate(Project $project): float
    {
        $startDate = $project->start_date ?? $project->created_at;
        $daysElapsed = $startDate->diffInDays(now());

        if ($daysElapsed == 0) {
            return 0;
        }

        $totalSpent = $this->calculateLaborCost($project) + 0; // TODO: Add expenses when model is available

        return round($totalSpent / $daysElapsed, 2);
    }

    /**
     * Project final cost
     */
    protected function projectFinalCost(Project $project): float
    {
        $burnRate = $this->calculateBurnRate($project);
        $totalDuration = $project->start_date && $project->due
            ? $project->start_date->diffInDays($project->due)
            : 30;

        return $burnRate * $totalDuration;
    }

    /**
     * Calculate Cost Performance Index
     */
    protected function calculateCPI(Project $project): float
    {
        $earnedValue = ($project->getCalculatedProgress() / 100) * ($project->budget ?? 0);
        $actualCost = $this->calculateLaborCost($project) + 0; // TODO: Add expenses when model is available

        if ($actualCost == 0) {
            return 1;
        }

        return round($earnedValue / $actualCost, 2);
    }

    /**
     * Identify risks
     */
    protected function identifyRisks(Project $project): array
    {
        return $this->getRiskAssessment($project);
    }

    /**
     * Calculate health score
     */
    protected function calculateHealthScore(Project $project): int
    {
        $score = 100;

        // Deduct for overdue status
        if ($project->due && $project->due->isPast() && $project->status !== Project::STATUS_COMPLETED) {
            $score -= 30;
        }

        // Deduct for budget overrun
        $budgetMetrics = $this->getBudgetMetrics($project);
        if ($budgetMetrics['budget_utilization'] > 100) {
            $score -= 25;
        } elseif ($budgetMetrics['budget_utilization'] > 90) {
            $score -= 15;
        }

        // Deduct for task delays
        $stats = $this->getProjectStatistics($project);
        if ($stats['tasks']['overdue'] > 0) {
            $score -= min(20, $stats['tasks']['overdue'] * 2);
        }

        // Deduct for milestone delays
        if ($stats['milestones']['overdue'] > 0) {
            $score -= min(15, $stats['milestones']['overdue'] * 5);
        }

        return max(0, $score);
    }

    /**
     * Assess schedule health
     */
    protected function assessScheduleHealth(Project $project): array
    {
        $progress = $project->getCalculatedProgress();
        $expected = $project->getExpectedProgress();
        $variance = $progress - $expected;

        return [
            'status' => $variance >= -5 ? 'good' : ($variance >= -15 ? 'warning' : 'critical'),
            'variance' => $variance,
            'message' => $variance >= 0 ? 'On schedule' : "{$variance}% behind schedule",
        ];
    }

    /**
     * Assess budget health
     */
    protected function assessBudgetHealth(Project $project): array
    {
        $metrics = $this->getBudgetMetrics($project);
        $utilization = $metrics['budget_utilization'];

        return [
            'status' => $utilization <= 80 ? 'good' : ($utilization <= 95 ? 'warning' : 'critical'),
            'utilization' => $utilization,
            'message' => $utilization <= 100 ? "{$utilization}% of budget used" : ($utilization - 100).'% over budget',
        ];
    }

    /**
     * Assess scope health
     */
    protected function assessScopeHealth(Project $project): array
    {
        $stats = $this->getProjectStatistics($project);
        $completionRate = $stats['tasks']['completion_rate'];

        return [
            'status' => $completionRate >= 70 ? 'good' : ($completionRate >= 50 ? 'warning' : 'critical'),
            'completion_rate' => $completionRate,
            'message' => "{$completionRate}% of tasks completed",
        ];
    }

    /**
     * Assess team health
     */
    protected function assessTeamHealth(Project $project): array
    {
        $utilization = $this->calculateTeamUtilization($project);

        return [
            'status' => $utilization <= 80 ? 'good' : ($utilization <= 95 ? 'warning' : 'critical'),
            'utilization' => $utilization,
            'message' => $utilization <= 80 ? 'Team capacity healthy' : 'Team may be overloaded',
        ];
    }

    /**
     * Assess quality health
     */
    protected function assessQualityHealth(Project $project): array
    {
        // This would typically check bug counts, test coverage, etc.
        // For now, we'll use a simplified metric
        $efficiency = $this->calculateTimeEfficiency($project);

        return [
            'status' => $efficiency >= 80 ? 'good' : ($efficiency >= 60 ? 'warning' : 'critical'),
            'efficiency' => $efficiency,
            'message' => "{$efficiency}% efficiency rating",
        ];
    }

    /**
     * Generate recommendations
     */
    protected function generateRecommendations(Project $project, array $health, array $risks): array
    {
        $recommendations = [];

        foreach ($risks as $risk) {
            if ($risk['severity'] === 'critical' || $risk['severity'] === 'high') {
                $recommendations[] = $risk['mitigation'];
            }
        }

        if ($health['status'] === 'warning' || $health['status'] === 'critical') {
            $recommendations[] = 'Schedule a project review meeting with stakeholders';
        }

        return array_unique($recommendations);
    }

    /**
     * Calculate velocity
     */
    protected function calculateVelocity($completedTasks): float
    {
        if ($completedTasks->isEmpty()) {
            return 0;
        }

        $weeksData = [];
        foreach ($completedTasks as $task) {
            $week = $task->completed_date?->format('Y-W') ?? $task->updated_at->format('Y-W');
            $weeksData[$week] = ($weeksData[$week] ?? 0) + 1;
        }

        return count($weeksData) > 0 ? round(array_sum($weeksData) / count($weeksData), 1) : 0;
    }

    /**
     * Generate burndown data
     */
    protected function generateBurndownData(Project $project): array
    {
        $startDate = $project->start_date ?? $project->created_at;
        $endDate = $project->due ?? now()->addMonth();

        $totalTasks = $project->tasks->count();
        $data = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $completedByDate = $project->tasks()
                ->where('status', Task::STATUS_COMPLETED)
                ->where('completed_date', '<=', $currentDate)
                ->count();

            $data[] = [
                'date' => $currentDate->format('Y-m-d'),
                'remaining' => $totalTasks - $completedByDate,
                'ideal' => $totalTasks * (1 - ($currentDate->diffInDays($startDate) / $endDate->diffInDays($startDate))),
            ];

            $currentDate->addDay();
        }

        return $data;
    }

    /**
     * Get status color
     */
    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            Project::STATUS_PLANNING => '#6366f1',
            Project::STATUS_ACTIVE => '#10b981',
            Project::STATUS_ON_HOLD => '#f59e0b',
            Project::STATUS_COMPLETED => '#06b6d4',
            Project::STATUS_CANCELLED => '#ef4444',
            default => '#6b7280',
        };
    }

    /**
     * Get priority color
     */
    protected function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'critical' => '#dc2626',
            'high' => '#f59e0b',
            'medium' => '#3b82f6',
            'low' => '#10b981',
            default => '#6b7280',
        };
    }
}
