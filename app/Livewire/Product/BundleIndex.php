<?php

namespace App\Livewire\Product;

use App\Livewire\BaseIndexComponent;
use App\Domains\Product\Models\ProductBundle;
use Illuminate\Database\Eloquent\Builder;

class BundleIndex extends BaseIndexComponent
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
        return ProductBundle::with(['products']);
    }

    protected function getColumns(): array
    {
        return [
            'name' => [
                'label' => 'Name',
                'sortable' => true,
                'filterable' => false,
                'component' => 'bundles.cells.name',
            ],
            'sku' => [
                'label' => 'SKU',
                'sortable' => true,
                'filterable' => false,
            ],
            'bundle_type' => [
                'label' => 'Bundle Type',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'fixed' => 'Fixed',
                    'configurable' => 'Configurable',
                    'dynamic' => 'Dynamic',
                ],
                'component' => 'bundles.cells.bundle-type',
            ],
            'pricing_type' => [
                'label' => 'Pricing Type',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'fixed' => 'Fixed Price',
                    'percentage_discount' => 'Percentage Discount',
                    'sum' => 'Sum of Items',
                ],
                'component' => 'bundles.cells.pricing-type',
            ],
            'products_count' => [
                'label' => 'Products',
                'sortable' => false,
                'filterable' => false,
                'component' => 'bundles.cells.products-count',
            ],
            'price' => [
                'label' => 'Price',
                'sortable' => false,
                'filterable' => false,
                'component' => 'bundles.cells.price',
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
                'component' => 'bundles.cells.status',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'cube',
            'title' => 'Create your first bundle',
            'message' => 'Start selling product bundles by creating your first bundle',
            'action' => route('bundles.create'),
            'actionLabel' => 'Create Bundle',
        ];
    }

    public function getRowActions($bundle)
    {
        return [
            [
                'href' => route('bundles.show', $bundle),
                'icon' => 'eye',
                'variant' => 'ghost',
                'label' => 'View',
            ],
            [
                'href' => route('bundles.edit', $bundle),
                'icon' => 'pencil',
                'variant' => 'ghost',
                'label' => 'Edit',
            ],
        ];
    }
}
