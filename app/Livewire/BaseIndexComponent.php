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
    
    protected $filterOptionsCache = [];
    
    protected $columnStatsCache = [];

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
                $filterType = $column['filter_type'] ?? ($column['type'] ?? 'text');
                
                if ($filterType === 'numeric_range') {
                    $this->columnFilters[$filterKey] = ['min' => '', 'max' => ''];
                } else {
                    $this->columnFilters[$filterKey] = '';
                }
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
        $visibleColumns = $this->getVisibleColumns();
        
        foreach ($this->columnFilters as $filterKey => $value) {
            if (empty($value)) {
                continue;
            }

            $columnConfig = collect($visibleColumns)->firstWhere(function ($config, $key) use ($filterKey) {
                return str_replace('.', '_', $key) === $filterKey;
            });

            if (!$columnConfig) {
                continue;
            }
            
            $columnKey = collect($visibleColumns)->search(function ($config, $key) use ($filterKey) {
                return str_replace('.', '_', $key) === $filterKey;
            });
            
            $column = $columnKey ?: $filterKey;

            $isArray = is_array($value);
            $type = $columnConfig['type'] ?? 'text';
            $filterType = $columnConfig['filter_type'] ?? $type;
            $isSelect = $type === 'select';
            $isDateRange = $type === 'date' && $isArray && isset($value['start'], $value['end']);
            $isNumericRange = $filterType === 'numeric_range' && $isArray && (isset($value['min']) || isset($value['max']));

            if ($isDateRange) {
                $query->whereBetween($column, [$value['start'], $value['end']]);
            } elseif ($isNumericRange) {
                if (isset($value['min']) && $value['min'] !== '' && $value['min'] !== null) {
                    $query->where($column, '>=', $value['min']);
                }
                if (isset($value['max']) && $value['max'] !== '' && $value['max'] !== null) {
                    $query->where($column, '<=', $value['max']);
                }
            } elseif (str_contains($column, '.')) {
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
            $this->columnFilters[$key] = is_array($value) ? ['min' => '', 'max' => ''] : '';
        }
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        if ($this->search) {
            return true;
        }

        return collect($this->columnFilters)->filter(function ($value) {
            if (is_array($value)) {
                return !empty($value['min']) || !empty($value['max']);
            }
            return !empty($value);
        })->isNotEmpty();
    }

    protected function getColumns(): array
    {
        return [];
    }

    protected function getVisibleColumns(): array
    {
        $columns = $this->getColumns();
        
        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $columns = collect($columns)->filter(function ($column, $key) {
                return !str_contains($key, 'client');
            })->toArray();
        }
        
        return $columns;
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
    
    public function getFilterOptions(string $columnKey): array
    {
        if (isset($this->filterOptionsCache[$columnKey])) {
            return $this->filterOptionsCache[$columnKey];
        }

        $columns = $this->getColumns();
        $columnConfig = $columns[$columnKey] ?? null;

        if (!$columnConfig || !($columnConfig['filterable'] ?? false)) {
            return [];
        }

        if (isset($columnConfig['options']) && !($columnConfig['dynamic_options'] ?? false)) {
            return $columnConfig['options'];
        }

        $query = $this->getBaseQuery()->where('company_id', $this->companyId);
        $query = $this->applyArchiveFilter($query);
        
        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient && method_exists($query->getModel(), 'client')) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $query->where('client_id', $clientId);
        }

        if (str_contains($columnKey, '.')) {
            [$relation, $field] = explode('.', $columnKey);
            $values = $query->with($relation)
                ->get()
                ->pluck("{$relation}.{$field}")
                ->filter()
                ->unique()
                ->sort()
                ->values();
        } else {
            $values = $query->distinct()
                ->pluck($columnKey)
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        $options = [];
        foreach ($values as $value) {
            $label = $columnConfig['option_label_callback'] ?? null;
            $options[$value] = $label ? $label($value) : $this->formatOptionLabel($value);
        }

        $this->filterOptionsCache[$columnKey] = $options;

        return $options;
    }

    protected function formatOptionLabel(string $value): string
    {
        return str($value)->replace('_', ' ')->title()->toString();
    }
    
    public function getColumnStats(string $columnKey): array
    {
        if (isset($this->columnStatsCache[$columnKey])) {
            return $this->columnStatsCache[$columnKey];
        }

        $columns = $this->getColumns();
        $columnConfig = $columns[$columnKey] ?? null;
        
        if (!$columnConfig || !($columnConfig['filterable'] ?? false)) {
            return ['min' => 0, 'max' => 0];
        }

        $query = $this->getBaseQuery()->where('company_id', $this->companyId);
        $query = $this->applyArchiveFilter($query);
        
        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient && method_exists($query->getModel(), 'client')) {
            $clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            $query->where('client_id', $clientId);
        }

        // Check if column exists in database table
        $tableName = $query->getModel()->getTable();
        if (!Schema::hasColumn($tableName, $columnKey)) {
            // Column doesn't exist (computed property), return defaults
            $this->columnStatsCache[$columnKey] = ['min' => 0, 'max' => 0];
            return $this->columnStatsCache[$columnKey];
        }

        try {
            $stats = $query->selectRaw("MIN({$columnKey}) as min, MAX({$columnKey}) as max")->first();
            
            $this->columnStatsCache[$columnKey] = [
                'min' => $stats->min ?? 0,
                'max' => $stats->max ?? 0,
            ];
        } catch (\Exception $e) {
            // If query fails, return defaults
            $this->columnStatsCache[$columnKey] = ['min' => 0, 'max' => 0];
        }
        
        return $this->columnStatsCache[$columnKey];
    }

    abstract protected function getBaseQuery(): Builder;

    public function render()
    {
        $columns = $this->getVisibleColumns();
        
        foreach ($columns as $key => $column) {
            if (($column['filterable'] ?? false) && ($column['dynamic_options'] ?? false)) {
                $columns[$key]['options'] = $this->getFilterOptions($key);
            }
        }
        
        return view('livewire.base-index', [
            'items' => $this->getItems(),
            'columns' => $columns,
            'stats' => $this->getStats(),
            'emptyState' => $this->getEmptyState(),
        ]);
    }
}
