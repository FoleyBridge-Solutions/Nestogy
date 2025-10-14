<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $typeFilter = '';
    public $statusFilter = '';
    public $billingModelFilter = '';
    public $sortBy = 'name';
    public $sortOrder = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'billingModelFilter' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'sortOrder' => ['except' => 'asc'],
    ];

    public function updatingSearch()
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

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingBillingModelFilter()
    {
        $this->resetPage();
    }

    public function sortByColumn($column)
    {
        if ($this->sortBy === $column) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortOrder = 'asc';
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'categoryFilter', 'typeFilter', 'statusFilter', 'billingModelFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Product::products()
            ->with(['category'])
            ->where('company_id', auth()->user()->company_id);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Apply category filter
        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        // Apply type filter
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        // Apply status filter
        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        // Apply billing model filter
        if ($this->billingModelFilter) {
            $query->where('billing_model', $this->billingModelFilter);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortOrder);

        $products = $query->paginate(20);

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
