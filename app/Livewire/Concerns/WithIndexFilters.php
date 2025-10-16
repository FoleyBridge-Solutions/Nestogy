<?php

namespace App\Livewire\Concerns;

use Livewire\WithPagination;

trait WithIndexFilters
{
    use WithPagination;

    public $search = '';

    public $perPage = 25;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingType()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingBillingModelFilter()
    {
        $this->resetPage();
    }

    public function updatingShowLeads()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingPriority()
    {
        $this->resetPage();
    }

    public function updatingClientId()
    {
        $this->resetPage();
    }

    protected function applySearch($query)
    {
        if (! $this->search) {
            return $query;
        }

        $searchFields = $this->getSearchFields();

        return $query->where(function ($q) use ($searchFields) {
            foreach ($searchFields as $field) {
                if (str_contains($field, '.')) {
                    [$relation, $column] = explode('.', $field);
                    $q->orWhereHas($relation, function ($relationQuery) use ($column) {
                        $relationQuery->where($column, 'like', "%{$this->search}%");
                    });
                } else {
                    $q->orWhere($field, 'like', "%{$this->search}%");
                }
            }
        });
    }

    protected function getSearchFields(): array
    {
        return ['name'];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'perPage' => ['except' => 25],
        ];
    }
}
