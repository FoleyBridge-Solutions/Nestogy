<?php

namespace App\Livewire\Project\Concerns;

use App\Domains\Project\Services\TimelineService;

trait ManagesTimelineView
{
    public string $viewType = TimelineService::VIEW_GANTT;

    public string $zoomLevel = TimelineService::ZOOM_WEEK;

    public bool $showLegend = true;

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

    public function toggleLegend()
    {
        $this->showLegend = ! $this->showLegend;
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
}
