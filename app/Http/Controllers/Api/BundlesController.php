<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductBundle;
use Illuminate\Http\Request;

class BundlesController extends Controller
{
    public function index(Request $request)
    {
        $bundles = ProductBundle::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->select('id', 'name', 'description', 'bundle_type', 'pricing_type', 'fixed_price', 'discount_percentage')
            ->get()
            ->map(function ($bundle) {
                return [
                    'id' => $bundle->id,
                    'name' => $bundle->name,
                    'description' => $bundle->description ?? '',
                    'bundle_type' => $bundle->bundle_type ?? 'standard',
                    'pricing_type' => $bundle->pricing_type ?? 'fixed',
                    'fixed_price' => $bundle->fixed_price ? (float) $bundle->fixed_price : null,
                    'discount_percentage' => $bundle->discount_percentage ? (float) $bundle->discount_percentage : null
                ];
            });

        return response()->json($bundles);
    }
}