<?php

namespace App\Domains\Project\Services;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\Task;
use Carbon\Carbon;

class ProjectMetricsService
{
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
                'active_members' => $members->count(),
                'total_hours_logged' => 0,
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

    public function getTaskMetrics(Project $project): array
    {
        $tasks = $project->tasks;

        $tasksByStatus = $tasks->groupBy('status')->map(fn ($group) => $group->count());

        $tasksByPriority = $tasks->groupBy('priority')->map(fn ($group) => $group->count());

        $tasksByAssignee = $tasks->groupBy('assigned_to')->map(function ($group, $userId) {
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

            $utilization = min(100, ($assignedTasks * 20));
            $totalUtilization += $utilization;
        }

        return round($totalUtilization / $members->count());
    }

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
