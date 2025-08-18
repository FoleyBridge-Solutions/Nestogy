<?php

namespace App\Domains\Product\Services;

use App\Models\Product;
use App\Models\Service;
use Illuminate\Support\Collection;

class ProductService
{
    /**
     * Create a new product service
     */
    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update an existing product
     */
    public function updateProduct(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    /**
     * Calculate product pricing
     */
    public function calculatePricing(Product $product, array $options = []): array
    {
        // Basic pricing calculation logic
        return [
            'base_price' => $product->base_price,
            'final_price' => $product->base_price,
            'discounts' => [],
            'taxes' => []
        ];
    }

    /**
     * Search products with filters
     */
    public function searchProducts(array $filters = []): Collection
    {
        $query = Product::query();

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        return $query->get();
    }

    /**
     * Duplicate a product
     */
    public function duplicateProduct(Product $product): Product
    {
        $newProduct = $product->replicate();
        $newProduct->name = $product->name . ' (Copy)';
        $newProduct->save();

        return $newProduct;
    }
}