<?php

namespace App\Livewire\Products;

use App\Domains\Product\Services\ProductService;
use App\Domains\Financial\Models\Category;
use App\Domains\Product\Models\Product;
use Livewire\Component;

class CreateProduct extends Component
{
    // Basic Information
    public $name = '';

    public $sku = '';

    public $description = '';

    public $short_description = '';

    public $type = 'product';

    public $category_id = '';

    public $unit_type = 'units';

    // Pricing
    public $base_price = null;

    public $cost = null;

    public $currency_code = 'USD';

    public $pricing_model = 'fixed';

    // Billing
    public $billing_model = 'one_time';

    public $billing_cycle = 'one_time';

    public $billing_interval = 1;

    // Tax & Discounts
    public $is_taxable = true;

    public $tax_inclusive = false;

    public $allow_discounts = true;

    public $requires_approval = false;

    // Status & Settings
    public $is_active = true;

    public $is_featured = false;

    public $sort_order = 0;

    // Inventory
    public $track_inventory = false;

    public $current_stock = 0;

    public $min_stock_level = 0;

    public $reorder_level = null;

    public $max_quantity_per_order = null;

    // Categories for dropdown
    public $categories = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'sku' => 'nullable|string|max:100|unique:products,sku',
        'description' => 'nullable|string',
        'short_description' => 'nullable|string|max:500',
        'type' => 'required|in:product,service',
        'category_id' => 'required|exists:categories,id',
        'unit_type' => 'required|in:units,hours,days,weeks,months,years,fixed,subscription',
        'base_price' => 'required|numeric|min:0',
        'cost' => 'nullable|numeric|min:0',
        'currency_code' => 'required|string|size:3',
        'pricing_model' => 'required|in:fixed,tiered,volume,usage,value,custom',
        'billing_model' => 'required|in:one_time,subscription,usage_based,hybrid',
        'billing_cycle' => 'required|in:one_time,hourly,daily,weekly,monthly,quarterly,semi_annually,annually',
        'billing_interval' => 'required|integer|min:1',
        'is_taxable' => 'boolean',
        'tax_inclusive' => 'boolean',
        'allow_discounts' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer|min:0',
        'track_inventory' => 'boolean',
        'current_stock' => 'nullable|integer|min:0',
        'min_stock_level' => 'nullable|integer|min:0',
        'reorder_level' => 'nullable|integer|min:0',
        'max_quantity_per_order' => 'nullable|integer|min:1',
    ];

    public function mount()
    {
        $this->authorize('create', Product::class);

        // Load categories for the current company (product type only)
        $this->categories = Category::where('company_id', auth()->user()->company_id)
            ->whereJsonContains('type', 'product')
            ->orderBy('name')
            ->get();
    }

    public function save()
    {
        $this->authorize('create', Product::class);

        $validated = $this->validate();

        // Add company_id
        $validated['company_id'] = auth()->user()->company_id;

        // Convert empty strings to null for numeric fields
        foreach (['cost', 'base_price', 'current_stock', 'min_stock_level', 'reorder_level', 'max_quantity_per_order'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Use ProductService to create the product
        $productService = app(ProductService::class);
        $product = $productService->create($validated);

        session()->flash('success', ucfirst($this->type).' created successfully!');

        return redirect()->route('products.show', $product);
    }

    public function render()
    {
        return view('livewire.products.create-product');
    }
}
