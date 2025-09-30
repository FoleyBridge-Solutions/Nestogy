<?php

namespace App\Domains\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Domains\Product\Requests\StoreServiceRequest;
use App\Domains\Product\Requests\UpdateServiceRequest;
use App\Domains\Product\Services\ProductService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $query = Product::services()
            ->with(['category', 'tax'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('billing_model')) {
            $query->where('billing_model', $request->billing_model);
        }

        if ($request->filled('unit_type')) {
            $query->where('unit_type', $request->unit_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $services = $query->orderBy('name')->paginate(20)->appends($request->query());
        
        $categories = Category::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        return view('services.index', compact('services', 'categories'));
    }

    public function show(Product $service)
    {
        $this->authorize('view', $service);
        
        // Ensure this is actually a service
        if (!$service->isService()) {
            abort(404);
        }

        $service->load(['category', 'tax']);

        return view('services.show', compact('service'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = Category::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $type = 'service'; // Specify this is for service creation

        return view('products.create', compact('categories', 'type'));
    }

    public function store(StoreServiceRequest $request)
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();
        $data['type'] = 'service'; // Ensure type is set to service
        $data['company_id'] = auth()->user()->company_id;

        // Handle tax profile assignment
        if ($request->filled('tax_profile_id')) {
            $data['tax_profile_id'] = $request->input('tax_profile_id');
        }

        $service = $this->productService->create($data);

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Service created successfully. Tax calculations will be applied when added to invoices or quotes.');
    }

    public function edit(Product $service)
    {
        $this->authorize('update', $service);
        
        // Ensure this is actually a service
        if (!$service->isService()) {
            abort(404);
        }

        $categories = Category::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $type = 'service'; // Specify this is for service editing

        return view('products.edit', compact('service', 'categories', 'type'));
    }

    public function update(UpdateServiceRequest $request, Product $service)
    {
        $this->authorize('update', $service);
        
        // Ensure this is actually a service
        if (!$service->isService()) {
            abort(404);
        }

        $data = $request->validated();
        $data['type'] = 'service'; // Ensure type remains service
        
        // Handle tax profile assignment
        if ($request->filled('tax_profile_id')) {
            $data['tax_profile_id'] = $request->input('tax_profile_id');
        }

        $service = $this->productService->update($service, $data);

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Service updated successfully. Tax configuration will be applied during billing.');
    }

    public function destroy(Product $service)
    {
        $this->authorize('delete', $service);
        
        // Ensure this is actually a service
        if (!$service->isService()) {
            abort(404);
        }

        $this->productService->delete($service);

        return redirect()
            ->route('services.index')
            ->with('success', 'Service deleted successfully.');
    }

    public function duplicate(Product $service)
    {
        $this->authorize('create', Product::class);
        
        // Ensure this is actually a service
        if (!$service->isService()) {
            abort(404);
        }

        $duplicatedService = $this->productService->duplicate($service);

        return redirect()
            ->route('services.edit', $duplicatedService)
            ->with('success', 'Service duplicated successfully. You can now modify the copy.');
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'services' => 'required|array',
            'services.*' => 'exists:products,id',
            'action' => 'required|in:activate,deactivate,delete,update_category',
            'category_id' => 'required_if:action,update_category|exists:categories,id',
        ]);

        $services = Product::services()
            ->where('company_id', auth()->user()->company_id)
            ->whereIn('id', $request->services)
            ->get();

        foreach ($services as $service) {
            $this->authorize('update', $service);
        }

        $this->productService->bulkUpdate($services->toArray(), $request->action, $request->only(['category_id']));

        $count = $services->count();
        return back()->with('success', "Successfully updated {$count} services.");
    }

    public function export(Request $request)
    {
        $this->authorize('export-product-data');

        $query = Product::services()
            ->with(['category', 'tax'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('billing_model')) {
            $query->where('billing_model', $request->billing_model);
        }

        $services = $query->orderBy('name')->get();

        return $this->productService->exportToCsv($services, 'services');
    }
    

    public function calculatePrice(Request $request, Product $service)
    {
        $request->validate([
            'quantity' => 'integer|min:1',
            'billing_periods' => 'integer|min:1',
            'usage_amount' => 'numeric|min:0',
        ]);

        if (!$service->isService()) {
            abort(404);
        }

        $pricing = $this->productService->calculateServicePrice(
            $service,
            $request->get('quantity', 1),
            $request->get('billing_periods', 1),
            $request->get('usage_amount', 0)
        );

        return response()->json($pricing);
    }
}