<?php

namespace App\Domains\Financial\Services;

use App\Models\Product;
use App\Models\Service;
use App\Models\ProductBundle;
use App\Models\Client;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class ProductSearchService
{
    protected ProductPricingService $pricingService;

    public function __construct(ProductPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Search products with filters
     */
    public function searchProducts(array $filters = [], Client $client = null): Collection
    {
        $query = Product::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->products(); // Use the products scope to filter only products

        // Apply filters
        $this->applyFilters($query, $filters);

        // Get products
        $products = $query->get();

        // Enhance with pricing information if client provided
        if ($client) {
            $products = $products->map(function ($product) use ($client) {
                $pricing = $this->pricingService->calculatePrice($product, $client);
                $product->client_price = $pricing['unit_price'];
                $product->has_discount = $pricing['savings'] > 0;
                $product->discount_percentage = $pricing['savings_percentage'];
                return $product;
            });
        }

        return $products;
    }

    /**
     * Search services with filters
     */
    public function searchServices(array $filters = [], Client $client = null): Collection
    {
        // First try to get services from Service model with product relationships
        $serviceQuery = Service::query()
            ->with('product')
            ->whereHas('product', function ($q) {
                $q->where('company_id', auth()->user()->company_id)
                  ->where('is_active', true)
                  ->where('type', 'service');
            });

        // Apply service-specific filters if available
        if (isset($filters['service_type'])) {
            $serviceQuery->where('service_type', $filters['service_type']);
        }

        $servicesWithMetadata = $serviceQuery->get();

        // Also get products that are services but don't have Service model records
        $productQuery = Product::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->services();

        // Exclude products that already have Service model records
        if ($servicesWithMetadata->count() > 0) {
            $productIds = $servicesWithMetadata->pluck('product_id');
            $productQuery->whereNotIn('id', $productIds);
        }

        // Apply filters to product query (exclude 'type' since we're already filtering to services)
        $filtersWithoutType = array_diff_key($filters, ['type' => '']);
        $this->applyFilters($productQuery, $filtersWithoutType);

        $standaloneServiceProducts = $productQuery->get();

        // Combine both types of services
        $allServices = $servicesWithMetadata;

        // Transform standalone products to look like services
        foreach ($standaloneServiceProducts as $product) {
            // Create a pseudo-service object
            $pseudoService = new \stdClass();
            $pseudoService->id = $product->id;
            $pseudoService->product_id = $product->id;
            $pseudoService->product = $product;
            $pseudoService->service_type = 'general';
            $pseudoService->estimated_hours = null;
            $pseudoService->requires_scheduling = false;
            $pseudoService->has_setup_fee = false;
            $pseudoService->setup_fee = 0;
            
            $allServices->push($pseudoService);
        }

        // Enhance with pricing information if client provided
        if ($client) {
            $allServices = $allServices->map(function ($service) use ($client) {
                if ($service->product) {
                    $pricing = $this->pricingService->calculatePrice($service->product, $client);
                    $service->client_price = $pricing['unit_price'];
                    $service->has_discount = $pricing['savings'] > 0;
                    $service->discount_percentage = $pricing['savings_percentage'];
                }
                return $service;
            });
        }

        return $allServices;
    }

    /**
     * Search bundles with filters
     */
    public function searchBundles(array $filters = [], Client $client = null): Collection
    {
        $query = ProductBundle::query()
            ->where('company_id', auth()->user()->company_id)
            ->active()
            ->with('products');

        // Apply standard filters
        $this->applyFilters($query, $filters);

        // Filter by bundle type
        if (isset($filters['bundle_type'])) {
            $query->where('bundle_type', $filters['bundle_type']);
        }

        // Filter by pricing type
        if (isset($filters['pricing_type'])) {
            $query->where('pricing_type', $filters['pricing_type']);
        }

        // Filter by price range
        if (isset($filters['max_price'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('fixed_price', '<=', $filters['max_price'])
                  ->orWhereNull('fixed_price');
            });
        }

        // Get bundles
        $bundles = $query->ordered()->get();

        // Enhance with pricing information if client provided
        if ($client) {
            $bundles = $bundles->map(function ($bundle) use ($client) {
                // Calculate bundle pricing (this might need to be implemented in PricingService)
                // For now, use the fixed price or sum of product prices
                $bundle->client_price = $bundle->fixed_price ?? $bundle->total_price;
                $bundle->has_discount = false; // TODO: implement bundle discounts
                $bundle->discount_percentage = 0;
                return $bundle;
            });
        }

        return $bundles;
    }

    /**
     * Get product recommendations for a client
     */
    public function getRecommendations(Client $client, int $limit = 5): Collection
    {
        $recommendations = collect();

        // Get client's purchase history
        $purchasedProducts = $this->getClientPurchaseHistory($client);
        $purchasedCategories = $purchasedProducts->pluck('category')->unique();

        // Find related products in same categories
        $relatedProducts = Product::query()
            ->where('company_id', $client->company_id)
            ->where('is_active', true)
            ->whereIn('category', $purchasedCategories)
            ->whereNotIn('id', $purchasedProducts->pluck('id'))
            ->limit($limit)
            ->get();

        $recommendations = $recommendations->merge($relatedProducts);

        // Find products with special pricing for this client
        $specialPricingProducts = Product::query()
            ->where('company_id', $client->company_id)
            ->where('is_active', true)
            ->whereHas('pricingRules', function ($q) use ($client) {
                $q->where('client_id', $client->id)
                  ->active();
            })
            ->limit($limit - $recommendations->count())
            ->get();

        $recommendations = $recommendations->merge($specialPricingProducts);

        // Find popular products
        if ($recommendations->count() < $limit) {
            $popularProducts = Product::query()
                ->where('company_id', $client->company_id)
                ->where('is_active', true)
                ->whereNotIn('id', $recommendations->pluck('id'))
                ->orderBy('created_at', 'desc')
                ->limit($limit - $recommendations->count())
                ->get();

            $recommendations = $recommendations->merge($popularProducts);
        }

        // Add pricing and scoring
        return $recommendations->map(function ($product) use ($client) {
            $pricing = $this->pricingService->calculatePrice($product, $client);
            $product->recommended_price = $pricing['unit_price'];
            $product->recommendation_score = $this->calculateRecommendationScore($product, $client);
            return $product;
        })->sortByDesc('recommendation_score')->take($limit);
    }

    /**
     * Quick search across all product types
     */
    public function quickSearch(string $query, int $limit = 10): array
    {
        $results = [
            'products' => [],
            'services' => [],
            'bundles' => []
        ];

        // Search products
        $products = Product::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        $results['products'] = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'type' => $product->type,
                'price' => $product->base_price,
                'category' => $product->category
            ];
        })->toArray();

        // Search services
        $services = Service::query()
            ->whereHas('product', function ($q) use ($query) {
                $q->where('company_id', auth()->user()->company_id)
                  ->where('is_active', true)
                  ->where(function ($sq) use ($query) {
                      $sq->where('name', 'like', "%{$query}%")
                         ->orWhere('description', 'like', "%{$query}%");
                  });
            })
            ->with('product')
            ->limit($limit)
            ->get();

        $results['services'] = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'product_id' => $service->product_id,
                'name' => $service->product->name,
                'type' => $service->service_type,
                'price' => $service->product->base_price,
                'sla_days' => $service->sla_days
            ];
        })->toArray();

        // Search bundles
        $bundles = ProductBundle::query()
            ->where('company_id', auth()->user()->company_id)
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        $results['bundles'] = $bundles->map(function ($bundle) {
            return [
                'id' => $bundle->id,
                'name' => $bundle->name,
                'type' => $bundle->bundle_type,
                'pricing_type' => $bundle->pricing_type,
                'price' => $bundle->fixed_price,
                'discount' => $bundle->discount_percentage
            ];
        })->toArray();

        return $results;
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory(string $category, Client $client = null): Collection
    {
        $products = Product::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->where('category', $category)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Add client-specific pricing if provided
        if ($client) {
            $products = $products->map(function ($product) use ($client) {
                $bestPrice = $this->pricingService->getBestPrice($product, $client);
                $product->best_price = $bestPrice['price'];
                $product->best_deal_type = $bestPrice['type'];
                if ($bestPrice['type'] === 'bundle') {
                    $product->bundle_id = $bestPrice['bundle_id'];
                    $product->bundle_name = $bestPrice['bundle_name'];
                }
                return $product;
            });
        }

        return $products;
    }

    /**
     * Get compatible products for a given product
     */
    public function getCompatibleProducts(Product $product, int $limit = 5): Collection
    {
        // Find products in bundles with this product
        $bundleProductIds = ProductBundle::query()
            ->whereHas('products', function ($q) use ($product) {
                $q->where('products.id', $product->id);
            })
            ->with('products')
            ->get()
            ->pluck('products')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->reject(function ($id) use ($product) {
                return $id === $product->id;
            });

        // Get the compatible products
        return Product::query()
            ->whereIn('id', $bundleProductIds)
            ->where('is_active', true)
            ->limit($limit)
            ->get();
    }

    /**
     * Apply filters to product query
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Filter by type
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by category
        if (isset($filters['category']) && $filters['category'] !== '' && $filters['category'] !== null) {
            $query->where('category_id', $filters['category']);
        }

        // Filter by billing model
        if (isset($filters['billing_model']) && $filters['billing_model'] !== '' && $filters['billing_model'] !== null) {
            $query->where('billing_model', $filters['billing_model']);
        }

        // Filter by price range
        if (isset($filters['min_price'])) {
            $query->where('base_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('base_price', '<=', $filters['max_price']);
        }

        // Filter by features
        if (isset($filters['features']) && is_array($filters['features'])) {
            foreach ($filters['features'] as $feature) {
                $query->whereJsonContains('features', $feature);
            }
        }

        // Filter by tags
        if (isset($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Search query
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        switch ($sortBy) {
            case 'price':
                $query->orderBy('base_price', $sortOrder);
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'popular':
                // This would need a sales/usage tracking
                $query->orderBy('sort_order', 'asc');
                break;
            default:
                $query->orderBy('name', $sortOrder);
        }
    }

    /**
     * Get client purchase history
     */
    protected function getClientPurchaseHistory(Client $client): Collection
    {
        // This would need to query from invoice items or orders
        // For now, returning empty collection
        return collect();
    }

    /**
     * Calculate recommendation score for a product
     */
    protected function calculateRecommendationScore(Product $product, Client $client): float
    {
        $score = 0;

        // Base score
        $score += 50;

        // Discount available
        $pricing = $this->pricingService->calculatePrice($product, $client);
        if ($pricing['savings_percentage'] > 0) {
            $score += min($pricing['savings_percentage'], 30);
        }

        // Product is in a bundle
        $bundles = ProductBundle::whereHas('products', function ($q) use ($product) {
            $q->where('products.id', $product->id);
        })->count();
        
        if ($bundles > 0) {
            $score += 10;
        }

        // New product (created in last 30 days)
        if ($product->created_at->gt(now()->subDays(30))) {
            $score += 15;
        }

        return min($score, 100);
    }

    /**
     * Get product availability status
     */
    public function checkAvailability(Product $product, int $quantity = 1): array
    {
        $available = true;
        $message = 'Available';
        $availableQuantity = null;

        // Check if product is active
        if (!$product->is_active) {
            return [
                'available' => false,
                'message' => 'Product is not available',
                'available_quantity' => 0
            ];
        }

        // Check inventory if tracking is enabled
        if ($product->track_inventory) {
            if ($product->current_stock < $quantity) {
                $available = false;
                $message = $product->current_stock > 0 
                    ? "Only {$product->current_stock} units available"
                    : 'Out of stock';
                $availableQuantity = $product->current_stock;
            }
        }

        // Check maximum quantity per order
        if ($product->max_quantity_per_order && $quantity > $product->max_quantity_per_order) {
            $available = false;
            $message = "Maximum {$product->max_quantity_per_order} units per order";
            $availableQuantity = $product->max_quantity_per_order;
        }

        return [
            'available' => $available,
            'message' => $message,
            'available_quantity' => $availableQuantity ?? $quantity,
            'in_stock' => !$product->track_inventory || $product->current_stock > 0,
            'stock_level' => $product->track_inventory ? $product->current_stock : null
        ];
    }
}