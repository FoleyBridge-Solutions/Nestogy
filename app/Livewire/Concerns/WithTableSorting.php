<?php

namespace App\Livewire\Concerns;

trait WithTableSorting
{
    public $sortField;

    public $sortDirection;

    public function initializeWithTableSorting()
    {
        $defaults = $this->getDefaultSort();
        $this->sortField = $defaults['field'] ?? 'created_at';
        $this->sortDirection = $defaults['direction'] ?? 'asc';
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function sortByColumn($column)
    {
        $this->sortBy($column);
    }

    public function sort($column)
    {
        $this->sortBy($column);
    }

    protected function getDefaultSort(): array
    {
        return [
            'field' => 'created_at',
            'direction' => 'asc',
        ];
    }

    protected function applySorting($query)
    {
        if ($this->sortField === 'client' && method_exists($query->getModel(), 'client')) {
            return $query->leftJoin('clients', $query->getModel()->getTable().'.client_id', '=', 'clients.id')
                ->orderBy('clients.name', $this->sortDirection)
                ->select($query->getModel()->getTable().'.*');
        }

        return $query->orderBy($this->sortField, $this->sortDirection);
    }
}
