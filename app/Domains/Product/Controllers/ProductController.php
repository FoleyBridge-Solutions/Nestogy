<?php

namespace App\Domains\Product\Controllers;

use App\Domains\Product\Requests\StoreProductRequest;
use App\Domains\Product\Requests\UpdateProductRequest;
use App\Domains\Product\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        return view('products.index');
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        return view('products.show', compact('product'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = Category::where('company_id', auth()->user()->company_id)
            ->whereJsonContains('type', 'product')
            ->orderBy('name')
            ->get();

        $type = 'product'; // Specify this is for product creation

        return view('products.create', compact('categories', 'type'));
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();

        // Handle tax profile and tax-specific data
        if ($request->filled('tax_profile_id')) {
            $data['tax_profile_id'] = $request->input('tax_profile_id');
        }

        // Handle category-specific tax data (e.g., VoIP line count, equipment weight)
        if ($request->filled('tax_data')) {
            $data['tax_data'] = $request->input('tax_data');
        }

        // Handle calculated tax data if provided
        if ($request->filled('calculated_tax_rate')) {
            $data['tax_rate'] = $request->input('calculated_tax_rate');
        }

        $product = $this->productService->create($data);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product created successfully with comprehensive tax configuration.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $categories = Category::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $type = 'product'; // Specify this is for product editing

        return view('products.edit', compact('product', 'categories', 'type'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        // Handle tax profile and tax-specific data
        if ($request->filled('tax_profile_id')) {
            $data['tax_profile_id'] = $request->input('tax_profile_id');
        }

        // Handle category-specific tax data (e.g., VoIP line count, equipment weight)
        if ($request->filled('tax_data')) {
            $data['tax_data'] = $request->input('tax_data');
        }

        // Handle calculated tax data if provided
        if ($request->filled('calculated_tax_rate')) {
            $data['tax_rate'] = $request->input('calculated_tax_rate');
        }

        $product = $this->productService->update($product, $data);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product updated successfully with tax configuration.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        // Check if product is used in any invoices or quotes
        $usageCount = DB::table('invoice_items')
            ->where('product_id', $product->id)
            ->count();

        if ($usageCount > 0) {
            return redirect()
                ->route('products.index')
                ->with('error', 'Cannot delete product that has been used in invoices or quotes.');
        }

        $this->productService->delete($product);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function duplicate(Product $product)
    {
        $this->authorize('create', Product::class);

        $newProduct = $this->productService->duplicate($product);

        return redirect()
            ->route('products.edit', $newProduct)
            ->with('success', 'Product duplicated successfully.');
    }

    public function bulkUpdate(Request $request)
    {
        $this->authorize('update', Product::class);

        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'action' => 'required|in:activate,deactivate,delete,update_category',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $products = Product::whereIn('id', $request->product_ids)
            ->where('company_id', auth()->user()->company_id)
            ->get();

        foreach ($products as $product) {
            switch ($request->action) {
                case 'activate':
                    $product->update(['is_active' => true]);
                    break;
                case 'deactivate':
                    $product->update(['is_active' => false]);
                    break;
                case 'delete':
                    if ($this->canDelete($product)) {
                        $product->delete();
                    }
                    break;
                case 'update_category':
                    if ($request->filled('category_id')) {
                        $product->update(['category_id' => $request->category_id]);
                    }
                    break;
                default:
                    break;
            }
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Bulk action completed successfully.');
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        return $this->productService->exportProducts($request->all());
    }

    public function import()
    {
        $this->authorize('create', Product::class);

        return view('products.import');
    }

    public function processImport(Request $request)
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        $result = $this->productService->importProducts($request->file('file'));

        return redirect()
            ->route('products.index')
            ->with('success', "Imported {$result['imported']} products. {$result['skipped']} skipped.");
    }

    protected function canDelete(Product $product): bool
    {
        return DB::table('invoice_items')
            ->where('product_id', $product->id)
            ->doesntExist();
    }
}
