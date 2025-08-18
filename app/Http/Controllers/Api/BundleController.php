<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductBundle;
use App\Models\Client;
use App\Domains\Financial\Services\ProductPricingService;
use App\Domains\Financial\Services\ProductSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BundleController extends Controller
{
    protected ProductPricingService $pricingService;
    protected ProductSearchService $searchService;

    public function __construct(
        ProductPricingService $pricingService,
        ProductSearchService $searchService
    ) {
        $this->pricingService = $pricingService;
        $this->searchService = $searchService;
    }

    /**
     * Search bundles
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'bundle_type', 'pricing_type', 'max_price'
        ]);

        try {
            $bundles = $this->searchService->searchBundles($filters);
            
            return response()->json([
                'success' => true,
                'bundles' => $bundles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search bundles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bundle details with products
     */
    public function details(ProductBundle $bundle): JsonResponse
    {
        // Ensure bundle belongs to current company
        if ($bundle->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Bundle not found'
            ], 404);
        }

        try {
            $bundle->load(['products']);
            
            return response()->json([
                'success' => true,
                'bundle' => $bundle
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load bundle details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate bundle price
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'bundle_id' => 'required|integer|exists:product_bundles,id',
            'selected_products' => 'nullable|array',
            'selected_products.*' => 'integer|exists:products,id',
            'client_id' => 'nullable|integer|exists:clients,id'
        ]);

        $bundle = ProductBundle::where('company_id', auth()->user()->company_id)
            ->findOrFail($request->bundle_id);

        $client = null;
        if ($request->client_id) {
            $client = Client::where('company_id', auth()->user()->company_id)
                ->find($request->client_id);
        }

        $selectedProducts = $request->get('selected_products', []);

        try {
            $pricing = $this->pricingService->calculateBundlePrice(
                $bundle,
                $selectedProducts,
                $client
            );
            
            return response()->json([
                'success' => true,
                ...$pricing
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate bundle price',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Validate bundle selection
     */
    public function validateSelection(Request $request): JsonResponse
    {
        $request->validate([
            'bundle_id' => 'required|integer|exists:product_bundles,id',
            'selected_products' => 'required|array',
            'selected_products.*' => 'integer|exists:products,id'
        ]);

        $bundle = ProductBundle::where('company_id', auth()->user()->company_id)
            ->findOrFail($request->bundle_id);

        try {
            $errors = $bundle->validateSelection($request->selected_products);
            
            return response()->json([
                'success' => true,
                'valid' => empty($errors),
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate selection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get configurable options for a bundle
     */
    public function configurableOptions(ProductBundle $bundle): JsonResponse
    {
        // Ensure bundle belongs to current company
        if ($bundle->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Bundle not found'
            ], 404);
        }

        if (!$bundle->isConfigurable()) {
            return response()->json([
                'success' => false,
                'message' => 'Bundle is not configurable'
            ], 400);
        }

        try {
            $bundle->load(['products']);
            
            $requiredProducts = $bundle->getRequiredProducts();
            $optionalProducts = $bundle->getOptionalProducts();
            
            return response()->json([
                'success' => true,
                'bundle' => $bundle,
                'required_products' => $requiredProducts,
                'optional_products' => $optionalProducts,
                'is_configurable' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load configurable options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active bundles
     */
    public function index(): JsonResponse
    {
        try {
            $bundles = ProductBundle::where('company_id', auth()->user()->company_id)
                ->active()
                ->ordered()
                ->with(['products'])
                ->get();
            
            return response()->json([
                'success' => true,
                'bundles' => $bundles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load bundles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate a bundle
     */
    public function duplicate(ProductBundle $bundle): JsonResponse
    {
        // Ensure bundle belongs to current company
        if ($bundle->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Bundle not found'
            ], 404);
        }

        try {
            $newBundle = $bundle->duplicate();
            
            return response()->json([
                'success' => true,
                'message' => 'Bundle duplicated successfully',
                'bundle' => $newBundle
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate bundle',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}