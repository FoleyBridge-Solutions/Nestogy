<?php

namespace App\Livewire\Products;

use App\Livewire\BaseIndexComponent;
use App\Models\Category;
use App\Models\Product;

class ProductIndex extends BaseIndexComponent
{
    public $categoryFilter = '';

    public $typeFilter = '';

    public $statusFilter = '';

    public $billingModelFilter = '';

    protected function getDefaultSort(): array
    {
        return [
            'field' => 'name',
            'direction' => 'asc',
        ];
    }

    protected function getSearchFields(): array
    {
        return [
            'name',
            'sku',
            'description',
        ];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'categoryFilter' => ['except' => ''],
            'typeFilter' => ['except' => ''],
            'statusFilter' => ['except' => ''],
            'billingModelFilter' => ['except' => ''],
            'sortField' => ['except' => 'name'],
            'sortDirection' => ['except' => 'asc'],
        ];
    }

    protected function getBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Product::products()->with(['category']);
    }

    protected function applyCustomFilters($query)
    {
        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        if ($this->billingModelFilter) {
            $query->where('billing_model', $this->billingModelFilter);
        }

        return $query;
    }

    public function clearFilters()
    {
        $this->reset(['search', 'categoryFilter', 'typeFilter', 'statusFilter', 'billingModelFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $products = $this->getItems();

        $categories = Category::where('company_id', auth()->user()->company_id)
            ->whereJsonContains('type', 'product')
            ->orderBy('name')
            ->get();

        return view('livewire.products.product-index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
