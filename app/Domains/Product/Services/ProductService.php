<?php

namespace App\Domains\Product\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return Product::create($data);
        });
    }

    public function update(Product $product, array $data)
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($data);
            return $product;
        });
    }

    public function delete(Product $product)
    {
        return DB::transaction(function () use ($product) {
            return $product->delete();
        });
    }

    public function calculatePrice(Product $product)
    {
        $basePrice = $product->price;
        
        // Apply any pricing rules or modifiers here
        
        return $basePrice;
    }

    public function duplicate(Product $product)
    {
        return DB::transaction(function () use ($product) {
            $newProduct = $product->replicate();
            $newProduct->name = $product->name . ' (Copy)';
            $newProduct->sku = $product->sku ? $product->sku . '-COPY' : null;
            $newProduct->save();
            
            return $newProduct;
        });
    }

    public function bulkUpdate(array $productIds, array $data)
    {
        return DB::transaction(function () use ($productIds, $data) {
            return Product::whereIn('id', $productIds)->update($data);
        });
    }
}
