<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with(['category', 'pricing'])
            ->when($request->get('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->when($request->get('category'), function ($query, $category) {
                $query->where('category_id', $category);
            })
            ->orderBy('name')
            ->paginate(20);

        $categories = collect(); // TODO: Load product categories
        
        return view('financial.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = collect(); // TODO: Load categories
        $taxRates = collect(); // TODO: Load tax rates
        
        return view('financial.products.create', compact('categories', 'taxRates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_categories,id',
            'unit_price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'is_active' => 'boolean'
        ]);

        Product::create($validated);

        return redirect()->route('financial.products.index')
            ->with('success', 'Product created successfully');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'pricing', 'sales_history']);
        
        $salesStats = [
            'total_sold' => 0, // TODO: Calculate from sales
            'revenue' => 0, // TODO: Calculate revenue
            'avg_price' => 0 // TODO: Calculate average selling price
        ];
        
        return view('financial.products.show', compact('product', 'salesStats'));
    }

    public function edit(Product $product): View
    {
        $categories = collect(); // TODO: Load categories
        $taxRates = collect(); // TODO: Load tax rates
        
        return view('financial.products.edit', compact('product', 'categories', 'taxRates'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_categories,id',
            'unit_price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'is_active' => 'boolean'
        ]);

        $product->update($validated);

        return redirect()->route('financial.products.show', $product)
            ->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        // TODO: Check if product has active sales/invoices
        
        $product->delete();

        return redirect()->route('financial.products.index')
            ->with('success', 'Product deleted successfully');
    }
}