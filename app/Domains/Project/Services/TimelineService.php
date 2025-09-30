<?php

namespace App\Domains\Project\Services;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimelineService
{
    /**
     * Timeline view types
     */
    const VIEW_GANTT = 'gantt';

    const VIEW_VERTICAL = 'vertical';

    const VIEW_KANBAN = 'kanban';

    const VIEW_RESOURCE = 'resource';

    /**
     * Timeline zoom levels
     */
    const ZOOM_DAY = 'day';

    const ZOOM_WEEK = 'week';

    const ZOOM_MONTH = 'month';

    const ZOOM_QUARTER = 'quarter';

    /**
     * Get comprehensive timeline data for a project
     */
    public function getTimelineData(Project $project, array $options = []): array
    {
        $viewType = $options['view'] ?? self::VIEW_GANTT;
        $zoomLevel = $options['zoom'] ?? self::ZOOM_WEEK;
        $filters = $options['filters'] ?? [];

        return match ($viewType) {
            self::VIEW_GANTT => $this->buildGanttTimeline($project, $zoomLevel, $filters),
            self::VIEW_VERTICAL => $this->buildVerticalTimeline($project, $filters),
            self::VIEW_KANBAN => $this->buildKanbanTimeline($project, $zoomLevel, $filters),
            self::VIEW_RESOURCE => $this->buildResourceTimeline($project, $zoomLevel, $filters),
            default => $this->buildGanttTimeline($project, $zoomLevel, $filters),
        };
    }

    /**
     * Build Gantt chart timeline data
     */
    protected function buildGanttTimeline(Project $project, string $zoomLevel, array $filters): array
    {
        $tasks = $this->getFilteredTasks($project, $filters);
        $milestones = $this->getFilteredMilestones($project, $filters);

        // Calculate timeline bounds
        $bounds = $this->calculateTimelineBounds($project, $tasks, $milestones);
        $timeAxis = $this->generateTimeAxis($bounds['start'], $bounds['end'], $zoomLevel);

        // Build Gantt data structure
        $ganttData = [];

        // Add project row
        $ganttData[] = [
            'id' => "project_{$project->id}",
            'type' => 'project',
            'name' => $project->name,
            'level' => 0,
            'start' => $project->start_date?->format('Y-m-d') ?? $project->created_at->format('Y-m-d'),
            'end' => $project->due?->format('Y-m-d') ?? $bounds['end']->format('Y-m-d'),
            'progress' => $project->progress ?? 0,
            'status' => $project->status,
            'color' => $this->getStatusColor($project->status),
            'duration' => $this->calculateDuration($project->start_date ?? $project->created_at, $project->due ?? $bounds['end']),
            'expandable' => true,
            'expanded' => true,
        ];

        // Add milestone rows
        foreach ($milestones as $milestone) {
            $ganttData[] = [
                'id' => "milestone_{$milestone->id}",
                'type' => 'milestone',
                'name' => $milestone->name,
                'level' => 1,
                'date' => $milestone->due_date?->format('Y-m-d'),
                'completed' => $milestone->status === 'completed',
                'critical' => $milestone->is_critical,
                'color' => $milestone->is_critical ? '#ef4444' : '#6366f1',
                'progress' => $milestone->completion_percentage ?? 0,
            ];
        }

        // Add task rows
        foreach ($tasks as $task) {
            $ganttData[] = [
                'id' => "task_{$task->id}",
                'type' => 'task',
                'name' => $task->name,
                'level' => 1,
                'start' => $task->start_date?->format('Y-m-d'),
                'end' => $task->due_date?->format('Y-m-d'),
                'progress' => $task->progress_percentage ?? 0,
                'status' => $task->status,
                'priority' => $task->priority,
                'assignee' => $task->assignedUser ? [
                    'id' => $task->assignedUser->id,
                    'name' => $task->assignedUser->name,
                    'avatar' => $task->assignedUser->avatar_url ?? null,
                ] : null,
                'color' => $this->getPriorityColor($task->priority),
                'duration' => $this->calculateDuration($task->start_date, $task->due_date),
                'dependencies' => $this->getTaskDependencies($task),
                'overdue' => $task->due_date && $task->due_date->isPast() && $task->status !== 'completed',
            ];
        }

        return [
            'type' => 'gantt',
            'data' => $ganttData,
            'timeAxis' => $timeAxis,
            'bounds' => $bounds,
            'zoom' => $zoomLevel,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'progress' => $project->progress ?? 0,
            ],
            'stats' => $this->calculateTimelineStats($tasks, $milestones),
        ];
    }

    /**
     * Build vertical timeline data (events chronologically)
     */
    protected function buildVerticalTimeline(Project $project, array $filters): array
    {
        $events = collect();

        // Project events
        $events->push([
            'id' => "project_start_{$project->id}",
            'type' => 'project_start',
            'title' => 'Project Started',
            'description' => $project->name,
            'date' => $project->start_date ?? $project->created_at,
            'icon' => 'play',
            'color' => 'green',
        ]);

        if ($project->due) {
            $events->push([
                'id' => "project_due_{$project->id}",
                'type' => 'project_due',
                'title' => 'Project Due',
                'description' => $project->name,
                'date' => $project->due,
                'icon' => 'flag',
                'color' => 'orange',
            ]);
        }

        // Task events
        $tasks = $this->getFilteredTasks($project, $filters);
        foreach ($tasks as $task) {
            if ($task->start_date) {
                $events->push([
                    'id' => "task_start_{$task->id}",
                    'type' => 'task_start',
                    'title' => 'Task Started',
                    'description' => $task->name,
                    'date' => $task->start_date,
                    'icon' => 'play-circle',
                    'color' => 'blue',
                    'assignee' => $task->assignedUser?->name,
                ]);
            }

            if ($task->due_date) {
                $events->push([
                    'id' => "task_due_{$task->id}",
                    'type' => 'task_due',
                    'title' => 'Task Due',
                    'description' => $task->name,
                    'date' => $task->due_date,
                    'icon' => 'clock',
                    'color' => $task->due_date->isPast() && $task->status !== 'completed' ? 'red' : 'yellow',
                    'assignee' => $task->assignedUser?->name,
                ]);
            }

            if ($task->completed_at) {
                $events->push([
                    'id' => "task_completed_{$task->id}",
                    'type' => 'task_completed',
                    'title' => 'Task Completed',
                    'description' => $task->name,
                    'date' => $task->completed_at,
                    'icon' => 'check-circle',
                    'color' => 'green',
                    'assignee' => $task->assignedUser?->name,
                ]);
            }
        }

        // Milestone events
        $milestones = $this->getFilteredMilestones($project, $filters);
        foreach ($milestones as $milestone) {
            $events->push([
                'id' => "milestone_{$milestone->id}",
                'type' => 'milestone',
                'title' => 'Milestone',
                'description' => $milestone->name,
                'date' => $milestone->due_date,
                'icon' => 'flag',
                'color' => $milestone->is_critical ? 'red' : 'purple',
                'completed' => $milestone->status === 'completed',
            ]);
        }

        return [
            'type' => 'vertical',
            'events' => $events->sortBy('date')->values()->toArray(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
            ],
        ];
    }

    /**
     * Build Kanban timeline data
     */
    protected function buildKanbanTimeline(Project $project, string $zoomLevel, array $filters): array
    {
        $tasks = $this->getFilteredTasks($project, $filters);
        $bounds = $this->calculateTimelineBounds($project, $tasks, collect());
        $timeAxis = $this->generateTimeAxis($bounds['start'], $bounds['end'], $zoomLevel);

        $columns = [
            'planning' => ['name' => 'Planning', 'color' => 'gray', 'tasks' => []],
            'in_progress' => ['name' => 'In Progress', 'color' => 'blue', 'tasks' => []],
            'review' => ['name' => 'Review', 'color' => 'yellow', 'tasks' => []],
            'completed' => ['name' => 'Completed', 'color' => 'green', 'tasks' => []],
        ];

        foreach ($tasks as $task) {
            $status = $task->status ?? 'planning';
            if (! isset($columns[$status])) {
                $status = 'planning';
            }

            $columns[$status]['tasks'][] = [
                'id' => $task->id,
                'name' => $task->name,
                'assignee' => $task->assignee?->name,
                'avatar' => $task->assignedUser?->avatar_url,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->format('M j'),
                'overdue' => $task->due_date && $task->due_date->isPast() && $task->status !== 'completed',
                'progress' => $task->progress_percentage ?? 0,
                'timeline_position' => $this->calculateTimelinePosition($task->start_date, $task->due_date, $bounds),
            ];
        }

        return [
            'type' => 'kanban',
            'columns' => $columns,
            'timeAxis' => $timeAxis,
            'bounds' => $bounds,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
            ],
        ];
    }

    /**
     * Build resource timeline data
     */
    protected function buildResourceTimeline(Project $project, string $zoomLevel, array $filters): array
    {
        $tasks = $this->getFilteredTasks($project, $filters);
        $bounds = $this->calculateTimelineBounds($project, $tasks, collect());
        $timeAxis = $this->generateTimeAxis($bounds['start'], $bounds['end'], $zoomLevel);

        // Group tasks by assignee
        $resources = [];
        $unassigned = [];

        foreach ($tasks as $task) {
            if ($task->assignedUser) {
                $resourceId = $task->assignedUser->id;
                if (! isset($resources[$resourceId])) {
                    $resources[$resourceId] = [
                        'id' => $resourceId,
                        'name' => $task->assignedUser->name,
                        'avatar' => $task->assignedUser->avatar_url,
                        'tasks' => [],
                        'workload' => 0,
                    ];
                }
                $resources[$resourceId]['tasks'][] = $this->formatTaskForResource($task, $bounds);
                $resources[$resourceId]['workload'] += $this->calculateTaskWorkload($task);
            } else {
                $unassigned[] = $this->formatTaskForResource($task, $bounds);
            }
        }

        // Add unassigned group if there are unassigned tasks
        if (! empty($unassigned)) {
            $resources['unassigned'] = [
                'id' => 'unassigned',
                'name' => 'Unassigned',
                'avatar' => null,
                'tasks' => $unassigned,
                'workload' => array_sum(array_map(fn ($task) => $this->calculateTaskWorkload($task), $unassigned)),
            ];
        }

        return [
            'type' => 'resource',
            'resources' => array_values($resources),
            'timeAxis' => $timeAxis,
            'bounds' => $bounds,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
            ],
        ];
    }

    /**
     * Helper methods
     */
    protected function getFilteredTasks(Project $project, array $filters): Collection
    {
        $query = $project->tasks();

        if (! empty($filters['assignee'])) {
            $query->where('assigned_to', $filters['assignee']);
        }

        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->whereIn('priority', (array) $filters['priority']);
        }

        return $query->with(['assignedUser'])->get();
    }

    protected function getFilteredMilestones(Project $project, array $filters): Collection
    {
        $query = $project->milestones();

        if (! empty($filters['critical_only'])) {
            $query->where('is_critical', true);
        }

        return $query->orderBy('due_date')->get();
    }

    protected function calculateTimelineBounds(Project $project, Collection $tasks, Collection $milestones): array
    {
        $dates = collect();

        // Project dates
        $dates->push($project->start_date ?? $project->created_at);
        if ($project->due) {
            $dates->push($project->due);
        }

        // Task dates
        foreach ($tasks as $task) {
            if ($task->start_date) {
                $dates->push($task->start_date);
            }
            if ($task->due_date) {
                $dates->push($task->due_date);
            }
        }

        // Milestone dates
        foreach ($milestones as $milestone) {
            if ($milestone->due_date) {
                $dates->push($milestone->due_date);
            }
        }

        $dates = $dates->filter()->sort();

        $start = $dates->first() ?? now();
        $end = $dates->last() ?? now()->addMonth();

        // Add some padding
        $start = $start->copy()->subWeek();
        $end = $end->copy()->addWeek();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    protected function generateTimeAxis(Carbon $start, Carbon $end, string $zoomLevel): array
    {
        $axis = [];
        $current = $start->copy();

        $interval = match ($zoomLevel) {
            self::ZOOM_DAY => 'day',
            self::ZOOM_WEEK => 'week',
            self::ZOOM_MONTH => 'month',
            self::ZOOM_QUARTER => 'quarter',
            default => 'week',
        };

        while ($current->lte($end)) {
            $axis[] = [
                'date' => $current->format('Y-m-d'),
                'label' => $this->formatAxisLabel($current, $zoomLevel),
                'isWeekend' => $current->isWeekend(),
                'isToday' => $current->isToday(),
            ];

            $current->add(1, $interval);
        }

        return $axis;
    }

    protected function formatAxisLabel(Carbon $date, string $zoomLevel): string
    {
        return match ($zoomLevel) {
            self::ZOOM_DAY => $date->format('M j'),
            self::ZOOM_WEEK => 'Week '.$date->week,
            self::ZOOM_MONTH => $date->format('M Y'),
            self::ZOOM_QUARTER => 'Q'.$date->quarter.' '.$date->year,
            default => $date->format('M j'),
        };
    }

    protected function calculateDuration(?Carbon $start = null, ?Carbon $end = null): int
    {
        if (! $start || ! $end) {
            return 0;
        }

        return (int) $start->diffInDays($end);
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'planning' => '#6b7280',
            'active', 'in_progress' => '#3b82f6',
            'on_hold' => '#f59e0b',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
            default => '#6b7280',
        };
    }

    protected function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'low' => '#10b981',
            'medium' => '#f59e0b',
            'high' => '#f97316',
            'critical' => '#ef4444',
            default => '#6b7280',
        };
    }

    protected function getTaskDependencies(Task $task): array
    {
        return $task->dependencies->map(fn ($dep) => "task_{$dep->id}")->toArray();
    }

    protected function calculateTimelineStats(Collection $tasks, Collection $milestones): array
    {
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $overdueTasks = $tasks->filter(fn ($task) => $task->due_date && $task->due_date->isPast() && $task->status !== 'completed'
        )->count();

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
            'total_milestones' => $milestones->count(),
            'completed_milestones' => $milestones->where('status', 'completed')->count(),
        ];
    }

    protected function calculateTimelinePosition($startDate, $endDate, array $bounds): array
    {
        if (! $startDate || ! $endDate) {
            return ['start' => 0, 'width' => 0];
        }

        $totalDays = $bounds['start']->diffInDays($bounds['end']);
        $startOffset = $bounds['start']->diffInDays($startDate);
        $duration = $startDate->diffInDays($endDate);

        return [
            'start' => ($startOffset / $totalDays) * 100,
            'width' => ($duration / $totalDays) * 100,
        ];
    }

    protected function formatTaskForResource($task, array $bounds): array
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'start_date' => $task->start_date?->format('Y-m-d'),
            'due_date' => $task->due_date?->format('Y-m-d'),
            'status' => $task->status,
            'priority' => $task->priority,
            'progress' => $task->progress_percentage ?? 0,
            'color' => $this->getPriorityColor($task->priority),
            'position' => $this->calculateTimelinePosition($task->start_date, $task->due_date, $bounds),
        ];
    }

    protected function calculateTaskWorkload($task): int
    {
        if (! $task->start_date || ! $task->due_date) {
            return 0;
        }

        return (int) $task->start_date->diffInDays($task->due_date);
    }
}
