<?php

namespace App\Services;

use App\Models\ProductBundle;
use App\Models\Product;
use App\Models\Client;
use App\Domains\Financial\Services\ProductPricingService;
use Illuminate\Support\Facades\DB;

class BundleService
{
    protected $productPricingService;

    public function __construct(ProductPricingService $productPricingService)
    {
        $this->productPricingService = $productPricingService;
    }

    public function create(array $data): ProductBundle
    {
        $data['company_id'] = auth()->user()->company_id;

        return DB::transaction(function () use ($data) {
            $products = $data['products'] ?? [];
            unset($data['products']);

            // Handle JSON fields
            if (isset($data['bundle_options']) && is_string($data['bundle_options'])) {
                $data['bundle_options'] = json_decode($data['bundle_options'], true);
            }

            $bundle = ProductBundle::create($data);

            if (!empty($products)) {
                $this->syncBundleProducts($bundle, $products);
            }

            return $bundle;
        });
    }

    public function update(ProductBundle $bundle, array $data): ProductBundle
    {
        return DB::transaction(function () use ($bundle, $data) {
            $products = $data['products'] ?? [];
            unset($data['products']);

            // Handle JSON fields
            if (isset($data['bundle_options']) && is_string($data['bundle_options'])) {
                $data['bundle_options'] = json_decode($data['bundle_options'], true);
            }

            $bundle->update($data);

            if (isset($products)) {
                $this->syncBundleProducts($bundle, $products);
            }

            return $bundle->fresh();
        });
    }

    public function delete(ProductBundle $bundle): bool
    {
        return DB::transaction(function () use ($bundle) {
            $bundle->products()->detach();
            return $bundle->delete();
        });
    }

    protected function syncBundleProducts(ProductBundle $bundle, array $products): void
    {
        $syncData = [];
        
        foreach ($products as $productData) {
            $productId = $productData['product_id'] ?? $productData['id'];
            $syncData[$productId] = [
                'quantity' => $productData['quantity'] ?? 1,
                'discount_percentage' => $productData['discount_percentage'] ?? 0,
                'is_optional' => $productData['is_optional'] ?? false,
                'display_order' => $productData['display_order'] ?? 0,
            ];
        }

        $bundle->products()->sync($syncData);
    }

    public function calculateBundlePrice(ProductBundle $bundle, int $quantity = 1, ?int $clientId = null): array
    {
        $bundle->load(['products']);
        
        $client = $clientId ? Client::find($clientId) : null;
        $totalPrice = 0;
        $totalDiscount = 0;
        $productBreakdown = [];

        foreach ($bundle->products as $product) {
            $productQuantity = $product->pivot->quantity * $quantity;
            
            // Calculate individual product price
            $productPricing = $this->productPricingService->calculatePrice(
                $product, 
                $productQuantity, 
                $client
            );

            $basePrice = $productPricing['total_price'];
            
            // Apply bundle-specific discount
            $bundleDiscount = ($basePrice * $product->pivot->discount_percentage) / 100;
            $finalPrice = $basePrice - $bundleDiscount;

            $productBreakdown[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $productQuantity,
                'unit_price' => $product->base_price,
                'base_total' => $basePrice,
                'bundle_discount' => $bundleDiscount,
                'final_price' => $finalPrice,
                'is_optional' => $product->pivot->is_optional,
            ];

            if (!$product->pivot->is_optional) {
                $totalPrice += $finalPrice;
                $totalDiscount += $bundleDiscount;
            }
        }

        // Apply bundle-level discount
        $bundleLevelDiscount = 0;
        if ($bundle->discount_percentage > 0) {
            $bundleLevelDiscount = ($totalPrice * $bundle->discount_percentage) / 100;
            $totalPrice -= $bundleLevelDiscount;
            $totalDiscount += $bundleLevelDiscount;
        }

        // Apply minimum price if set
        if ($bundle->min_price && $totalPrice < $bundle->min_price) {
            $totalPrice = $bundle->min_price;
        }

        return [
            'bundle_id' => $bundle->id,
            'bundle_name' => $bundle->name,
            'quantity' => $quantity,
            'subtotal' => $totalPrice + $totalDiscount,
            'total_discount' => $totalDiscount,
            'bundle_level_discount' => $bundleLevelDiscount,
            'final_price' => $totalPrice,
            'currency' => $bundle->currency_code,
            'products' => $productBreakdown,
            'savings_percentage' => $totalPrice > 0 ? round(($totalDiscount / ($totalPrice + $totalDiscount)) * 100, 2) : 0,
        ];
    }

    public function validateBundleConfiguration(array $data): array
    {
        $errors = [];

        // Validate products
        if (empty($data['products'])) {
            $errors[] = 'Bundle must contain at least one product.';
        } else {
            $productIds = array_column($data['products'], 'product_id');
            $existingProducts = Product::whereIn('id', $productIds)
                ->where('company_id', auth()->user()->company_id)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            $missingProducts = array_diff($productIds, $existingProducts);
            if (!empty($missingProducts)) {
                $errors[] = 'Some selected products are not available: ' . implode(', ', $missingProducts);
            }
        }

        // Validate pricing
        if (isset($data['discount_percentage']) && ($data['discount_percentage'] < 0 || $data['discount_percentage'] > 100)) {
            $errors[] = 'Discount percentage must be between 0 and 100.';
        }

        if (isset($data['min_price']) && $data['min_price'] < 0) {
            $errors[] = 'Minimum price cannot be negative.';
        }

        return $errors;
    }

    public function getBundleAnalytics(ProductBundle $bundle): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        return [
            'total_sales' => $this->getBundleSalesCount($bundle),
            'total_revenue' => $this->getBundleRevenue($bundle),
            'recent_sales' => $this->getBundleSalesCount($bundle, $thirtyDaysAgo),
            'average_order_value' => $this->getAverageOrderValue($bundle),
            'most_popular_combinations' => $this->getPopularCombinations($bundle),
            'conversion_rate' => $this->getConversionRate($bundle),
        ];
    }

    protected function getBundleSalesCount(ProductBundle $bundle, ?\Carbon\Carbon $since = null): int
    {
        $query = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.bundle_id', $bundle->id);

        if ($since) {
            $query->where('invoices.created_at', '>=', $since);
        }

        return $query->count();
    }

    protected function getBundleRevenue(ProductBundle $bundle, ?\Carbon\Carbon $since = null): float
    {
        $query = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.bundle_id', $bundle->id);

        if ($since) {
            $query->where('invoices.created_at', '>=', $since);
        }

        return (float) $query->sum(DB::raw('invoice_items.quantity * invoice_items.unit_price'));
    }

    protected function getAverageOrderValue(ProductBundle $bundle): float
    {
        $result = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.bundle_id', $bundle->id)
            ->selectRaw('AVG(invoice_items.quantity * invoice_items.unit_price) as avg_value')
            ->first();

        return (float) ($result->avg_value ?? 0);
    }

    protected function getPopularCombinations(ProductBundle $bundle): array
    {
        // This would analyze which optional products are most often included
        return DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.bundle_id', $bundle->id)
            ->select('invoice_items.product_id')
            ->selectRaw('COUNT(*) as frequency')
            ->groupBy('invoice_items.product_id')
            ->orderBy('frequency', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getConversionRate(ProductBundle $bundle): float
    {
        // This would require tracking bundle views vs purchases
        // For now, return a placeholder
        return 0.0;
    }
}