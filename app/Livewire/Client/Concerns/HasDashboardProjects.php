<?php

namespace App\Livewire\Client\Concerns;

trait HasDashboardProjects
{
    protected function getProjects()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->projects()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getProjectStats(): array
    {
        if (! $this->client) {
            return [];
        }

        $projects = $this->client->projects();

        return [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->whereNull('completed_at')->count(),
            'completed_projects' => $projects->whereNotNull('completed_at')->count(),
            'projects_on_time' => $projects->whereNull('completed_at')
                ->where('due', '>', now())
                ->count(),
        ];
    }

    protected function getActiveProjects()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->projects()
            ->whereNull('completed_at')
            ->with(['tasks' => function ($query) {
                $query->whereIn('status', ['pending', 'in_progress']);
            }])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($project) {
                $totalTasks = $project->tasks()->count();
                $completedTasks = $project->tasks()->where('status', 'completed')->count();
                $progress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status ?? 'active',
                    'progress' => round($progress, 0),
                    'due_date' => $project->due,
                    'tasks_remaining' => $totalTasks - $completedTasks,
                ];
            });
    }
}
