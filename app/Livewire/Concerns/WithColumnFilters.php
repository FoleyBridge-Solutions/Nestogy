<?php

namespace App\Livewire\Concerns;

trait WithColumnFilters
{
    public $columnFilters = [];

    public function mountWithColumnFilters()
    {
        $this->columnFilters = array_fill_keys($this->getFilterableColumns(), '');
    }

    public function updatedColumnFilters()
    {
        $this->resetPage();
    }

    protected function applyColumnFilters($query)
    {
        foreach ($this->columnFilters as $column => $value) {
            if (empty($value)) {
                continue;
            }

            if (str_contains($column, '.')) {
                [$relation, $field] = explode('.', $column);
                $query->whereHas($relation, function ($q) use ($field, $value) {
                    $q->where($field, 'like', "%{$value}%");
                });
            } else {
                $query->where($column, 'like', "%{$value}%");
            }
        }

        return $query;
    }

    protected function getFilterableColumns(): array
    {
        return [];
    }

    public function clearColumnFilters()
    {
        $this->columnFilters = array_fill_keys($this->getFilterableColumns(), '');
        $this->resetPage();
    }

    public function hasActiveColumnFilters(): bool
    {
        return collect($this->columnFilters)->filter()->isNotEmpty();
    }
}
