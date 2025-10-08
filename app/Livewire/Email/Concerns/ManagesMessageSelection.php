<?php

namespace App\Livewire\Email\Concerns;

trait ManagesMessageSelection
{
    public array $selected = [];

    public bool $selectPage = false;

    public function toggleSelectPage()
    {
        $this->selectPage = ! $this->selectPage;
        if ($this->selectPage) {
            $this->selected = $this->messages()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function toggleSelect($id)
    {
        $id = (string) $id;
        if (in_array($id, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$id]));
        } else {
            $this->selected[] = $id;
        }
    }

    public function clearSelection()
    {
        $this->selected = [];
        $this->selectPage = false;
    }
}
