<?php

namespace App\Livewire\Concerns;

trait WithBulkActions
{
    public $selected = [];

    public $selectedAssets = [];

    public $selectedTickets = [];

    public $selectAll = false;

    public function updatedSelectAll($value)
    {
        if ($value) {
            $items = $this->getItems();
            $this->selected = $items->pluck('id')->map(fn ($id) => (string) $id)->toArray();

            if (property_exists($this, 'selectedAssets')) {
                $this->selectedAssets = $items->pluck('id')->toArray();
            }

            if (property_exists($this, 'selectedTickets')) {
                $this->selectedTickets = $items->pluck('id')->toArray();
            }
        } else {
            $this->clearSelection();
        }
    }

    public function clearSelection()
    {
        $this->selected = [];
        $this->selectAll = false;

        if (property_exists($this, 'selectedAssets')) {
            $this->selectedAssets = [];
        }

        if (property_exists($this, 'selectedTickets')) {
            $this->selectedTickets = [];
        }
    }

    public function getSelectedCount(): int
    {
        return count($this->selected);
    }

    public function hasSelected(): bool
    {
        return count($this->selected) > 0;
    }

    abstract protected function getItems();
}
