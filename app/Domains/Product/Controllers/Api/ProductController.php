<?php

namespace App\Domains\Product\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\Client;
use App\Domains\Financial\Services\ProductPricingService;
use App\Domains\Financial\Services\ProductSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
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
     * Search products with filters
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'category', 'billing_model', 'type',
            'min_price', 'max_price', 'sort_by', 'sort_order'
        ]);

        $client = null;
        if ($request->client_id) {
            $client = Client::where('company_id', auth()->user()->company_id)
                ->find($request->client_id);
        }

        // Get current page and per page
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 12);

        try {
            if ($request->get('quick', false)) {
                // Quick search across all types
                $results = $this->searchService->quickSearch(
                    $request->get('q', ''),
                    $perPage
                );
                
                return response()->json([
                    'success' => true,
                    'products' => $results['products'] ?? [],
                    'services' => $results['services'] ?? [],
                    'bundles' => $results['bundles'] ?? []
                ]);
            } else {
                // Regular search with pagination
                $products = $this->searchService->searchProducts($filters, $client);
                
                // Manual pagination for simplicity
                $total = $products->count();
                $offset = ($page - 1) * $perPage;
                $paginatedProducts = $products->slice($offset, $perPage)->values();

                return response()->json([
                    'success' => true,
                    'products' => $paginatedProducts,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => ceil($total / $perPage)
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick search endpoint
     */
    public function quickSearch(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'products' => [],
                'services' => [],
                'bundles' => []
            ]);
        }

        try {
            $results = $this->searchService->quickSearch($query, $limit);
            
            return response()->json([
                'success' => true,
                ...$results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = Product::where('company_id', auth()->user()->company_id)
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->filter()
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate pricing for products
     */
    public function pricing(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
            'client_id' => 'nullable|integer|exists:clients,id'
        ]);

        $client = null;
        if ($request->client_id) {
            $client = Client::where('company_id', auth()->user()->company_id)
                ->find($request->client_id);
        }

        try {
            $pricing = [];
            
            foreach ($request->product_ids as $productId) {
                $product = Product::where('company_id', auth()->user()->company_id)
                    ->find($productId);
                
                if ($product) {
                    $pricing[$productId] = $this->pricingService->calculatePrice(
                        $product, 
                        $client
                    );
                }
            }

            return response()->json([
                'success' => true,
                'pricing' => $pricing
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate pricing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate price for a specific product and quantity
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'client_id' => 'nullable|integer|exists:clients,id',
            'quantity' => 'integer|min:1'
        ]);

        $quantity = $request->get('quantity', 1);
        
        $product = Product::where('company_id', auth()->user()->company_id)
            ->findOrFail($request->product_id);

        $client = null;
        if ($request->client_id) {
            $client = Client::where('company_id', auth()->user()->company_id)
                ->find($request->client_id);
        }

        try {
            $pricing = $this->pricingService->calculatePrice($product, $client, $quantity);
            
            return response()->json([
                'success' => true,
                ...$pricing
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate price',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product details
     */
    public function details(Product $product): JsonResponse
    {
        // Ensure product belongs to current company
        if ($product->company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        try {
            $product->load(['service']);
            
            // Get compatible products
            $compatibleProducts = $this->searchService->getCompatibleProducts($product, 5);
            
            // Get volume pricing tiers
            $pricingTiers = $this->pricingService->getVolumePricingTiers($product);
            
            // Check availability
            $availability = $this->searchService->checkAvailability($product);

            return response()->json([
                'success' => true,
                'product' => $product,
                'compatible_products' => $compatibleProducts,
                'pricing_tiers' => $pricingTiers,
                'availability' => $availability
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load product details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category
     */
    public function byCategory(string $category, Request $request): JsonResponse
    {
        $client = null;
        if ($request->client_id) {
            $client = Client::where('company_id', auth()->user()->company_id)
                ->find($request->client_id);
        }

        try {
            $products = $this->searchService->getProductsByCategory($category, $client);
            
            return response()->json([
                'success' => true,
                'products' => $products,
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product recommendations for a client
     */
    public function recommendations(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id'
        ]);

        $client = Client::where('company_id', auth()->user()->company_id)
            ->findOrFail($request->client_id);

        try {
            $recommendations = $this->searchService->getRecommendations($client, 5);
            
            return response()->json([
                'success' => true,
                'recommendations' => $recommendations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply promo code
     */
    public function applyPromo(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'promo_code' => 'required|string',
            'client_id' => 'nullable|integer|exists:clients,id',
            'quantity' => 'integer|min:1'
        ]);

        $product = Product::where('company_id', auth()->user()->company_id)
            ->findOrFail($request->product_id);

        $client = null;
        if ($request->client_id) {
            $client = Client::where('company_id', auth()->user()->company_id)
                ->find($request->client_id);
        }

        $quantity = $request->get('quantity', 1);

        try {
            $pricing = $this->pricingService->applyPromoCode(
                $product,
                $request->promo_code,
                $client,
                $quantity
            );
            
            return response()->json([
                'success' => true,
                'valid' => true,
                'message' => 'Promo code applied successfully',
                ...$pricing
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get best price for a product
     */
    public function bestPrice(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'client_id' => 'nullable|integer|exists:clients,id',
            'quantity' => 'integer|min:1'
        ]);

        $product = Product::where('company_id', auth()->user()->company_id)
            ->findOrFail($request->product_id);

        $client = null;
        if ($request->client_id) {
            $client = Client::where('company_id', auth()->user()->company_id)
                ->find($request->client_id);
        }

        $quantity = $request->get('quantity', 1);

        try {
            $bestPrice = $this->pricingService->getBestPrice($product, $client, $quantity);
            
            return response()->json([
                'success' => true,
                'best_price' => $bestPrice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate best price',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}