<?php

namespace App\Livewire\Project\Concerns;

trait ManagesTimelineFilters
{
    public array $filters = [
        'assignee' => null,
        'status' => [],
        'priority' => [],
        'critical_only' => false,
    ];

    public bool $showFilters = false;

    public function toggleFilters()
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function setAssigneeFilter(?int $assigneeId)
    {
        $this->filters['assignee'] = $assigneeId;
    }

    public function toggleStatusFilter(string $status)
    {
        $statuses = $this->filters['status'];

        if (in_array($status, $statuses)) {
            $this->filters['status'] = array_values(array_diff($statuses, [$status]));
        } else {
            $this->filters['status'][] = $status;
        }
    }

    public function togglePriorityFilter(string $priority)
    {
        $priorities = $this->filters['priority'];

        if (in_array($priority, $priorities)) {
            $this->filters['priority'] = array_values(array_diff($priorities, [$priority]));
        } else {
            $this->filters['priority'][] = $priority;
        }
    }

    public function toggleCriticalOnlyFilter()
    {
        $this->filters['critical_only'] = ! $this->filters['critical_only'];
    }

    public function clearFilters()
    {
        $this->filters = [
            'assignee' => null,
            'status' => [],
            'priority' => [],
            'critical_only' => false,
        ];
    }
}
