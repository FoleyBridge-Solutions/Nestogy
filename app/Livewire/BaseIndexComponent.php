<?php

namespace App\Livewire;

use App\Domains\Core\Services\NavigationService;
use App\Livewire\Concerns\WithAuthenticatedUser;
use App\Livewire\Concerns\WithBulkActions;
use App\Livewire\Concerns\WithIndexFilters;
use App\Livewire\Concerns\WithTableSorting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

abstract class BaseIndexComponent extends Component
{
    use WithAuthenticatedUser;
    use WithBulkActions;
    use WithIndexFilters;
    use WithTableSorting;

    public $columnFilters = [];

    protected $queryString = [];

    public function mount()
    {
        $this->initializeColumnFilters();
        $this->queryString = $this->getQueryStringProperties();
        $this->applyClientContext();
    }

    protected function initializeColumnFilters()
    {
        foreach ($this->getColumns() as $key => $column) {
            if ($column['filterable'] ?? false) {
                $filterKey = str_replace('.', '_', $key);
                $this->columnFilters[$filterKey] = '';
            }
        }
    }

    public function updatedColumnFilters()
    {
        $this->resetPage();
    }

    protected function applyClientContext()
    {
        $selectedClient = app(NavigationService::class)->getSelectedClient();

        if ($selectedClient && property_exists($this, 'clientId')) {
            $this->clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
        }

        if ($selectedClient && property_exists($this, 'selectedClients')) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            if (! in_array($clientId, $this->selectedClients)) {
                $this->selectedClients[] = $clientId;
            }
        }
    }

    protected function buildQuery(): Builder
    {
        $query = $this->getBaseQuery();

        $query = $query->where('company_id', $this->companyId);

        $query = $this->applyArchiveFilter($query);

        $query = $this->applySearch($query);

        $query = $this->applyColumnFilters($query);

        $query = $this->applyCustomFilters($query);

        $query = $this->applySorting($query);

        return $query;
    }

    protected function applyArchiveFilter($query)
    {
        if (! property_exists($this, 'filter') || $this->filter !== 'archived') {
            if (Schema::hasColumn($query->getModel()->getTable(), 'archived_at')) {
                $query->whereNull('archived_at');
            }
        }

        return $query;
    }

    protected function applyColumnFilters($query)
    {
        foreach ($this->columnFilters as $filterKey => $value) {
            if (empty($value)) {
                continue;
            }

            $column = str_replace('_', '.', $filterKey);
            
            $columnConfig = collect($this->getColumns())->firstWhere(function ($config, $key) use ($filterKey) {
                return str_replace('.', '_', $key) === $filterKey;
            });

            if (!$columnConfig) {
                continue;
            }

            $isArray = is_array($value);
            $isSelect = ($columnConfig['type'] ?? 'text') === 'select';

            if (str_contains($column, '.')) {
                [$relation, $field] = explode('.', $column);
                $query->whereHas($relation, function ($q) use ($field, $value, $isArray, $isSelect) {
                    if ($isArray && $isSelect) {
                        $q->whereIn($field, $value);
                    } else {
                        $q->where($field, 'like', "%{$value}%");
                    }
                });
            } else {
                if ($isArray && $isSelect) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, 'like', "%{$value}%");
                }
            }
        }

        return $query;
    }

    protected function applyCustomFilters($query)
    {
        return $query;
    }

    protected function getItems()
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    public function clearAllFilters()
    {
        $this->search = '';
        foreach ($this->columnFilters as $key => $value) {
            $this->columnFilters[$key] = '';
        }
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        if ($this->search) {
            return true;
        }

        return collect($this->columnFilters)->filter()->isNotEmpty();
    }

    protected function getColumns(): array
    {
        return [];
    }

    protected function getStats(): array
    {
        return [];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'document-text',
            'title' => 'No items found',
            'message' => 'No items to display',
            'action' => null,
            'actionLabel' => 'Create New',
        ];
    }

    abstract protected function getBaseQuery(): Builder;

    public function render()
    {
        return view('livewire.base-index', [
            'items' => $this->getItems(),
            'columns' => $this->getColumns(),
            'stats' => $this->getStats(),
            'emptyState' => $this->getEmptyState(),
        ]);
    }
}
