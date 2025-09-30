<?php

namespace App\Domains\Product\Controllers;

use App\Domains\Product\Requests\StoreBundleRequest;
use App\Domains\Product\Requests\UpdateBundleRequest;
use App\Domains\Product\Services\BundleService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBundle;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    protected $bundleService;

    public function __construct(BundleService $bundleService)
    {
        $this->bundleService = $bundleService;
    }

    public function index(Request $request)
    {
        $query = ProductBundle::with(['products'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $bundles = $query->orderBy('name')->paginate(20)->appends($request->query());

        return view('bundles.index', compact('bundles'));
    }

    public function show(ProductBundle $bundle)
    {
        $this->authorize('view', $bundle);

        $bundle->load(['products']);

        return view('bundles.show', compact('bundle'));
    }

    public function create()
    {
        $this->authorize('create', ProductBundle::class);

        $products = Product::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('bundles.create', compact('products'));
    }

    public function store(StoreBundleRequest $request)
    {
        $this->authorize('create', ProductBundle::class);

        $bundle = $this->bundleService->create($request->validated());

        return redirect()
            ->route('bundles.show', $bundle)
            ->with('success', 'Bundle created successfully.');
    }

    public function edit(ProductBundle $bundle)
    {
        $this->authorize('update', $bundle);

        $products = Product::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $bundle->load(['products']);

        return view('bundles.edit', compact('bundle', 'products'));
    }

    public function update(UpdateBundleRequest $request, ProductBundle $bundle)
    {
        $this->authorize('update', $bundle);

        $bundle = $this->bundleService->update($bundle, $request->validated());

        return redirect()
            ->route('bundles.show', $bundle)
            ->with('success', 'Bundle updated successfully.');
    }

    public function destroy(ProductBundle $bundle)
    {
        $this->authorize('delete', $bundle);

        $this->bundleService->delete($bundle);

        return redirect()
            ->route('bundles.index')
            ->with('success', 'Bundle deleted successfully.');
    }

    public function calculatePrice(Request $request, ProductBundle $bundle)
    {
        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'quantity' => 'integer|min:1',
        ]);

        $pricing = $this->bundleService->calculateBundlePrice(
            $bundle,
            $request->get('quantity', 1),
            $request->get('client_id')
        );

        return response()->json($pricing);
    }
}
