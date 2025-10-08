<?php

namespace App\Livewire\Project;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Services\TimelineService;
use App\Livewire\Project\Concerns\CalculatesTimelinePositions;
use App\Livewire\Project\Concerns\ManagesTimelineFilters;
use App\Livewire\Project\Concerns\ManagesTimelineInteractions;
use App\Livewire\Project\Concerns\ManagesTimelineItems;
use App\Livewire\Project\Concerns\ManagesTimelineView;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Timeline extends Component
{
    use CalculatesTimelinePositions;
    use ManagesTimelineFilters;
    use ManagesTimelineInteractions;
    use ManagesTimelineItems;
    use ManagesTimelineView;

    public Project $project;

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

    #[On('task-updated')]
    #[On('milestone-updated')]
    #[On('project-updated')]
    public function refreshTimeline()
    {
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

    public function exportTimeline(string $format = 'png')
    {
        $this->dispatch('export-timeline', $format);
    }

    public function printTimeline()
    {
        $this->dispatch('print-timeline');
    }

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

    public function render()
    {
        return view('livewire.project.timeline');
    }
}
