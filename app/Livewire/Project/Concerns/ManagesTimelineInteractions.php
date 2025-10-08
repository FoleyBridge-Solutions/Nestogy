<?php

namespace App\Livewire\Project\Concerns;

trait ManagesTimelineInteractions
{
    public ?string $selectedItemId = null;

    public ?string $hoveredItemId = null;

    public array $expandedItems = [];

    public bool $autoRefresh = true;

    public int $refreshInterval = 30;

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

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = ! $this->autoRefresh;

        if ($this->autoRefresh) {
            $this->dispatch('start-auto-refresh', $this->refreshInterval);
        } else {
            $this->dispatch('stop-auto-refresh');
        }
    }
}
