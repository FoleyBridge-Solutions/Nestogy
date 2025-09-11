<?php

namespace App\Livewire\Project;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Services\TimelineService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Timeline extends Component
{
    public Project $project;

    // Timeline configuration
    public string $viewType = TimelineService::VIEW_GANTT;

    public string $zoomLevel = TimelineService::ZOOM_WEEK;

    // Filters
    public array $filters = [
        'assignee' => null,
        'status' => [],
        'priority' => [],
        'critical_only' => false,
    ];

    // UI state
    public bool $showFilters = false;

    public bool $showLegend = true;

    public bool $autoRefresh = true;

    public int $refreshInterval = 30; // seconds

    // Interaction state
    public ?string $selectedItemId = null;

    public ?string $hoveredItemId = null;

    public array $expandedItems = [];

    protected TimelineService $timelineService;

    public function boot(TimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->expandedItems = ["project_{$project->id}"];
    }

    #[Computed]
    public function timelineData()
    {
        return $this->timelineService->getTimelineData($this->project, [
            'view' => $this->viewType,
            'zoom' => $this->zoomLevel,
            'filters' => array_filter($this->filters),
        ]);
    }

    #[Computed]
    public function availableAssignees()
    {
        return $this->project->tasks()
            ->with('assignee')
            ->get()
            ->pluck('assignee')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    #[Computed]
    public function filterStats()
    {
        $data = $this->timelineData;

        if ($this->viewType === TimelineService::VIEW_GANTT) {
            $tasks = collect($data['data'])->where('type', 'task');
            $milestones = collect($data['data'])->where('type', 'milestone');

            return [
                'total_items' => $tasks->count() + $milestones->count(),
                'tasks' => $tasks->count(),
                'milestones' => $milestones->count(),
                'overdue' => $tasks->where('overdue', true)->count(),
            ];
        }

        return ['total_items' => 0];
    }

    // View type methods
    public function setViewType(string $viewType)
    {
        if (in_array($viewType, [
            TimelineService::VIEW_GANTT,
            TimelineService::VIEW_VERTICAL,
            TimelineService::VIEW_KANBAN,
            TimelineService::VIEW_RESOURCE,
        ])) {
            $this->viewType = $viewType;
            $this->selectedItemId = null;
        }
    }

    public function setZoomLevel(string $zoomLevel)
    {
        if (in_array($zoomLevel, [
            TimelineService::ZOOM_DAY,
            TimelineService::ZOOM_WEEK,
            TimelineService::ZOOM_MONTH,
            TimelineService::ZOOM_QUARTER,
        ])) {
            $this->zoomLevel = $zoomLevel;
        }
    }

    // Filter methods
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

    // Interaction methods
    public function selectItem(string $itemId)
    {
        $this->selectedItemId = $this->selectedItemId === $itemId ? null : $itemId;
        $this->dispatch('timeline-item-selected', $itemId);
    }

    public function hoverItem(string $itemId)
    {
        $this->hoveredItemId = $itemId;
        $this->dispatch('timeline-item-hovered', $itemId);
    }

    public function expandItem(string $itemId)
    {
        if (in_array($itemId, $this->expandedItems)) {
            $this->expandedItems = array_values(array_diff($this->expandedItems, [$itemId]));
        } else {
            $this->expandedItems[] = $itemId;
        }
    }

    public function toggleLegend()
    {
        $this->showLegend = ! $this->showLegend;
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = ! $this->autoRefresh;

        if ($this->autoRefresh) {
            $this->dispatch('start-auto-refresh', $this->refreshInterval);
        } else {
            $this->dispatch('stop-auto-refresh');
        }
    }

    // Task/milestone management methods
    public function createTask()
    {
        $this->dispatch('open-task-modal', ['project_id' => $this->project->id]);
    }

    public function editTask(int $taskId)
    {
        $this->dispatch('open-task-modal', ['task_id' => $taskId]);
    }

    public function createMilestone()
    {
        $this->dispatch('open-milestone-modal', ['project_id' => $this->project->id]);
    }

    public function editMilestone(int $milestoneId)
    {
        $this->dispatch('open-milestone-modal', ['milestone_id' => $milestoneId]);
    }

    // Drag and drop methods (for future implementation)
    public function updateTaskDates(int $taskId, string $startDate, string $endDate)
    {
        // Update task dates when dragged in timeline
        // This would be called from JavaScript
        $task = $this->project->tasks()->find($taskId);
        if ($task) {
            $task->update([
                'start_date' => $startDate,
                'due_date' => $endDate,
            ]);

            $this->dispatch('timeline-updated');
        }
    }

    // Event listeners
    #[On('task-updated')]
    #[On('milestone-updated')]
    #[On('project-updated')]
    public function refreshTimeline()
    {
        // Refresh computed properties
        unset($this->timelineData);
        unset($this->filterStats);
    }

    #[On('timeline-auto-refresh')]
    public function autoRefreshTimeline()
    {
        if ($this->autoRefresh) {
            $this->refreshTimeline();
        }
    }

    // Export methods
    public function exportTimeline(string $format = 'png')
    {
        $this->dispatch('export-timeline', $format);
    }

    public function printTimeline()
    {
        $this->dispatch('print-timeline');
    }

    // Keyboard shortcuts handler
    #[On('timeline-keyboard-shortcut')]
    public function handleKeyboardShortcut(string $action)
    {
        match ($action) {
            'toggle-filters' => $this->toggleFilters(),
            'toggle-legend' => $this->toggleLegend(),
            'create-task' => $this->createTask(),
            'create-milestone' => $this->createMilestone(),
            'zoom-in' => $this->zoomIn(),
            'zoom-out' => $this->zoomOut(),
            'view-gantt' => $this->setViewType(TimelineService::VIEW_GANTT),
            'view-vertical' => $this->setViewType(TimelineService::VIEW_VERTICAL),
            'view-kanban' => $this->setViewType(TimelineService::VIEW_KANBAN),
            'view-resource' => $this->setViewType(TimelineService::VIEW_RESOURCE),
            default => null,
        };
    }

    private function zoomIn()
    {
        $zoomLevels = [
            TimelineService::ZOOM_QUARTER,
            TimelineService::ZOOM_MONTH,
            TimelineService::ZOOM_WEEK,
            TimelineService::ZOOM_DAY,
        ];

        $currentIndex = array_search($this->zoomLevel, $zoomLevels);
        if ($currentIndex !== false && $currentIndex < count($zoomLevels) - 1) {
            $this->setZoomLevel($zoomLevels[$currentIndex + 1]);
        }
    }

    private function zoomOut()
    {
        $zoomLevels = [
            TimelineService::ZOOM_QUARTER,
            TimelineService::ZOOM_MONTH,
            TimelineService::ZOOM_WEEK,
            TimelineService::ZOOM_DAY,
        ];

        $currentIndex = array_search($this->zoomLevel, $zoomLevels);
        if ($currentIndex !== false && $currentIndex > 0) {
            $this->setZoomLevel($zoomLevels[$currentIndex - 1]);
        }
    }

    // Helper methods for timeline positioning
    public function calculateDatePosition(string $date, array $bounds, array $timeAxis, string $zoomLevel): float
    {
        $targetDate = \Carbon\Carbon::parse($date);
        $startDate = $bounds['start'];
        $endDate = $bounds['end'];

        $totalDays = $startDate->diffInDays($endDate);
        $daysSinceStart = $startDate->diffInDays($targetDate);

        $slotWidth = match ($zoomLevel) {
            TimelineService::ZOOM_DAY => 60,
            TimelineService::ZOOM_WEEK => 100,
            TimelineService::ZOOM_MONTH => 120,
            TimelineService::ZOOM_QUARTER => 120,
            default => 100,
        };

        return ($daysSinceStart / $totalDays) * (count($timeAxis) * $slotWidth);
    }

    public function calculateBarPosition(string $startDate, string $endDate, array $bounds, array $timeAxis, string $zoomLevel): array
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $boundsStart = $bounds['start'];
        $boundsEnd = $bounds['end'];

        $totalDays = $boundsStart->diffInDays($boundsEnd);
        $daysSinceStart = $boundsStart->diffInDays($start);
        $duration = $start->diffInDays($end);

        $slotWidth = match ($zoomLevel) {
            TimelineService::ZOOM_DAY => 60,
            TimelineService::ZOOM_WEEK => 100,
            TimelineService::ZOOM_MONTH => 120,
            TimelineService::ZOOM_QUARTER => 120,
            default => 100,
        };

        $totalWidth = count($timeAxis) * $slotWidth;

        return [
            'left' => ($daysSinceStart / $totalDays) * $totalWidth,
            'width' => max(($duration / $totalDays) * $totalWidth, 20), // Minimum width of 20px
        ];
    }

    public function render()
    {
        return view('livewire.project.timeline');
    }
}
