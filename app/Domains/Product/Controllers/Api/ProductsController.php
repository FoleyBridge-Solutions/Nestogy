<?php

namespace App\Domains\Product\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->select('id', 'name', 'sku', 'category', 'base_price', 'billing_cycle', 'description', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku ?? '',
                    'category' => $product->category ?? 'General',
                    'base_price' => (float) ($product->base_price ?? 0),
                    'billing_cycle' => $product->billing_cycle ?? 'one_time',
                    'description' => $product->description ?? '',
                    'features' => [],
                    'pricing_tiers' => [],
                    'created_at' => $product->created_at
                ];
            });

        return response()->json($products);
    }
}