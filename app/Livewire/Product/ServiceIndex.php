<?php

namespace App\Livewire\Product;

use App\Livewire\BaseIndexComponent;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ServiceIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'name', 'direction' => 'asc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'description', 'sku'];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'sortField' => ['except' => 'name'],
            'sortDirection' => ['except' => 'asc'],
            'perPage' => ['except' => 25],
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return Product::services()
            ->with(['category', 'tax']);
    }

    protected function getColumns(): array
    {
        return [
            'name' => [
                'label' => 'Name',
                'sortable' => true,
                'filterable' => false,
                'component' => 'products.cells.name',
            ],
            'sku' => [
                'label' => 'SKU',
                'sortable' => true,
                'filterable' => false,
            ],
            'category.name' => [
                'label' => 'Category',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
            ],
            'billing_model' => [
                'label' => 'Billing Model',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'one_time' => 'One Time',
                    'subscription' => 'Subscription',
                    'usage_based' => 'Usage Based',
                    'tiered' => 'Tiered',
                ],
                'component' => 'products.cells.billing-model',
            ],
            'base_price' => [
                'label' => 'Price',
                'sortable' => true,
                'filterable' => true,
                'type' => 'currency',
                'filter_type' => 'numeric_range',
            ],
            'unit_type' => [
                'label' => 'Unit Type',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'hours' => 'Hours',
                    'days' => 'Days',
                    'weeks' => 'Weeks',
                    'months' => 'Months',
                    'years' => 'Years',
                    'fixed' => 'Fixed',
                    'units' => 'Units',
                ],
                'component' => 'products.cells.unit-type',
            ],
            'is_active' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    '1' => 'Active',
                    '0' => 'Inactive',
                ],
                'component' => 'products.cells.status',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'wrench-screwdriver',
            'title' => 'Create your first service',
            'message' => 'Start offering services by creating your first service offering',
            'action' => route('services.create'),
            'actionLabel' => 'Create Service',
        ];
    }

    public function getRowActions($service)
    {
        return [
            [
                'href' => route('services.show', $service),
                'icon' => 'eye',
                'variant' => 'ghost',
                'label' => 'View',
            ],
            [
                'href' => route('services.edit', $service),
                'icon' => 'pencil',
                'variant' => 'ghost',
                'label' => 'Edit',
            ],
        ];
    }
}
