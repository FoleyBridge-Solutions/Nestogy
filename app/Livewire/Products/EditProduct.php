<?php

namespace App\Livewire\Products;

use App\Domains\Product\Services\ProductService;
use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

class EditProduct extends Component
{
    public Product $product;

    public $name = '';

    public $sku = '';

    public $description = '';

    public $short_description = '';

    public $type = 'product';

    public $category_id = '';

    public $unit_type = 'units';

    public $base_price = null;

    public $cost = null;

    public $currency_code = 'USD';

    public $pricing_model = 'fixed';

    public $billing_model = 'one_time';

    public $billing_cycle = 'one_time';

    public $billing_interval = 1;

    public $is_taxable = true;

    public $tax_inclusive = false;

    public $allow_discounts = true;

    public $requires_approval = false;

    public $is_active = true;

    public $is_featured = false;

    public $sort_order = 0;

    public $track_inventory = false;

    public $current_stock = 0;

    public $min_stock_level = 0;

    public $reorder_level = null;

    public $max_quantity_per_order = null;

    public $categories = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'sku' => 'nullable|string|max:100',
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

    public function mount(Product $product)
    {
        $this->authorize('update', $product);

        $this->product = $product;

        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->description = $product->description;
        $this->short_description = $product->short_description;
        $this->type = $product->type;
        $this->category_id = $product->category_id;
        $this->unit_type = $product->unit_type;

        $this->base_price = $product->base_price;
        $this->cost = $product->cost;
        $this->currency_code = $product->currency_code;
        $this->pricing_model = $product->pricing_model;

        $this->billing_model = $product->billing_model;
        $this->billing_cycle = $product->billing_cycle;
        $this->billing_interval = $product->billing_interval;

        $this->is_taxable = $product->is_taxable;
        $this->tax_inclusive = $product->tax_inclusive;
        $this->allow_discounts = $product->allow_discounts;
        $this->requires_approval = $product->requires_approval;

        $this->is_active = $product->is_active;
        $this->is_featured = $product->is_featured;
        $this->sort_order = $product->sort_order;

        $this->track_inventory = $product->track_inventory;
        $this->current_stock = $product->current_stock;
        $this->min_stock_level = $product->min_stock_level;
        $this->reorder_level = $product->reorder_level;
        $this->max_quantity_per_order = $product->max_quantity_per_order;

        $this->categories = Category::where('company_id', auth()->user()->company_id)
            ->whereJsonContains('type', $this->type)
            ->orderBy('name')
            ->get();
    }

    public function save()
    {
        $this->authorize('update', $this->product);

        $this->rules['sku'] = 'nullable|string|max:100|unique:products,sku,'.$this->product->id;
        $validated = $this->validate();

        foreach (['cost', 'base_price', 'current_stock', 'min_stock_level', 'reorder_level', 'max_quantity_per_order'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $productService = app(ProductService::class);
        $product = $productService->update($this->product, $validated);

        session()->flash('success', ucfirst($this->type).' updated successfully!');

        return redirect()->route('products.show', $product);
    }

    public function render()
    {
        return view('livewire.products.edit-product');
    }
}
