<?php

namespace App\Domains\Financial\Services;

use App\Models\Client;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\ProductBundle;
use Illuminate\Support\Collection;

class ProductPricingService
{
    /**
     * Calculate product price for a client with all applicable rules
     */
    public function calculatePrice(Product $product, ?Client $client = null, int $quantity = 1, array $options = []): array
    {
        $basePrice = $product->base_price;
        $finalPrice = $basePrice;
        $appliedRules = collect();
        $breakdown = [];

        $rules = $this->getApplicablePricingRules($product, $client, $quantity);

        foreach ($rules as $rule) {
            if (! $rule->isValid()) {
                continue;
            }

            if (! $this->canApplyRule($rule, $appliedRules)) {
                continue;
            }

            $rulePrice = $rule->calculatePrice($finalPrice, $quantity);

            if ($rulePrice < $finalPrice) {
                $finalPrice = $rulePrice;
                $appliedRules->push($rule);

                $breakdown[] = [
                    'rule' => $rule->name ?? 'Pricing Rule #'.$rule->id,
                    'type' => $rule->discount_type,
                    'value' => $rule->discount_value,
                    'savings' => $basePrice - $rulePrice,
                ];
            }
        }

        if ($appliedRules->isEmpty() && $product->discount_percentage) {
            $finalPrice = $basePrice * (1 - $product->discount_percentage / 100);
            $breakdown[] = [
                'rule' => 'Product Discount',
                'type' => 'percentage',
                'value' => $product->discount_percentage,
                'savings' => $basePrice - $finalPrice,
            ];
        }

        $subtotal = $finalPrice * $quantity;
        $tax = $this->calculateTax($product, $subtotal, $client);
        $total = $subtotal + ($product->tax_inclusive ? 0 : $tax);

        return [
            'base_price' => $basePrice,
            'unit_price' => $finalPrice,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'currency' => $product->currency ?? 'USD',
            'applied_rules' => $appliedRules->pluck('id')->toArray(),
            'breakdown' => $breakdown,
            'savings' => ($basePrice - $finalPrice) * $quantity,
            'savings_percentage' => $basePrice > 0 ? (($basePrice - $finalPrice) / $basePrice) * 100 : 0,
        ];
    }

    /**
     * Calculate bundle price with selected products
     */
    public function calculateBundlePrice(ProductBundle $bundle, array $selectedProductIds = [], ?Client $client = null): array
    {
        if (! $bundle->isAvailable()) {
            throw new \Exception('Bundle is not currently available');
        }

        // Validate product selection for configurable bundles
        if ($bundle->isConfigurable()) {
            $errors = $bundle->validateSelection($selectedProductIds);
            if (! empty($errors)) {
                throw new \Exception('Invalid product selection: '.implode(', ', $errors));
            }
        }

        $bundlePrice = $bundle->calculatePrice($selectedProductIds);
        $originalPrice = 0;
        $itemDetails = [];

        // Calculate original prices and item details
        foreach ($bundle->products as $product) {
            if ($selectedProductIds !== null &&
                ! $product->pivot->is_required &&
                ! in_array($product->id, $selectedProductIds)) {
                continue;
            }

            $originalItemPrice = $product->base_price * $product->pivot->quantity;
            $originalPrice += $originalItemPrice;

            $itemDetails[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $product->pivot->quantity,
                'original_price' => $product->base_price,
                'bundle_price' => $product->pivot->price_override ??
                    ($product->base_price * (1 - ($product->pivot->discount_value ?? 0) / 100)),
                'is_required' => $product->pivot->is_required,
            ];
        }

        $savings = $bundle->getSavingsAmount($selectedProductIds);
        $savingsPercentage = $bundle->getSavingsPercentage($selectedProductIds);

        return [
            'bundle_id' => $bundle->id,
            'bundle_name' => $bundle->name,
            'bundle_type' => $bundle->bundle_type,
            'pricing_type' => $bundle->pricing_type,
            'original_price' => $originalPrice,
            'bundle_price' => $bundlePrice,
            'savings' => $savings,
            'savings_percentage' => $savingsPercentage,
            'items' => $itemDetails,
            'selected_products' => $selectedProductIds ?? [],
        ];
    }

    /**
     * Check if a rule can be applied with already applied rules
     */
    protected function canApplyRule(PricingRule $rule, Collection $appliedRules): bool
    {
        foreach ($appliedRules as $appliedRule) {
            if (! $rule->canCombineWith($appliedRule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get applicable pricing rules for a product
     */
    protected function getApplicablePricingRules(Product $product, ?Client $client = null, int $quantity = 1): Collection
    {
        $query = PricingRule::where('product_id', $product->id)
            ->active()
            ->byPriority();

        if ($client) {
            $query->forClient($client->id);
        } else {
            $query->whereNull('client_id');
        }

        return $query->get()->filter(function ($rule) use ($quantity) {
            return $rule->appliesToQuantity($quantity) &&
                   $rule->appliesToDateTime();
        });
    }

    /**
     * Calculate tax for a product
     */
    protected function calculateTax(Product $product, float $subtotal, ?Client $client = null): float
    {
        $taxRate = $product->tax_rate ?? 0;

        // Override with client-specific tax rate if available
        if ($client && $client->tax_rate !== null) {
            $taxRate = $client->tax_rate;
        }

        // Check for tax exemption
        if ($client && $client->is_tax_exempt) {
            return 0;
        }

        return $subtotal * ($taxRate / 100);
    }

    /**
     * Apply promo code to pricing
     */
    public function applyPromoCode(Product $product, string $promoCode, ?Client $client = null, int $quantity = 1): array
    {
        $rule = PricingRule::where('product_id', $product->id)
            ->where('promo_code', $promoCode)
            ->where('is_promotional', true)
            ->active()
            ->first();

        if (! $rule) {
            throw new \Exception('Invalid promo code');
        }

        if (! $rule->validatePromoCode($promoCode)) {
            throw new \Exception('Promo code is not valid');
        }

        if (! $rule->appliesToQuantity($quantity)) {
            throw new \Exception('Promo code does not apply to this quantity');
        }

        // Apply the promo rule
        $pricing = $this->calculatePrice($product, $client, $quantity);
        $pricing['promo_code'] = $promoCode;
        $pricing['promo_discount'] = $rule->getDiscountDisplay();

        // Increment usage count
        $rule->incrementUsage();

        return $pricing;
    }

    /**
     * Get volume pricing tiers for a product
     */
    public function getVolumePricingTiers(Product $product): array
    {
        $rules = PricingRule::where('product_id', $product->id)
            ->where('pricing_model', 'volume')
            ->active()
            ->get();

        $tiers = [];

        foreach ($rules as $rule) {
            if (! empty($rule->conditions['volumes'])) {
                foreach ($rule->conditions['volumes'] as $volume) {
                    $tiers[] = [
                        'min_quantity' => $volume['min'] ?? 0,
                        'max_quantity' => $volume['max'] ?? null,
                        'price' => $volume['price'] ?? $product->base_price,
                        'discount_percentage' => (($product->base_price - $volume['price']) / $product->base_price) * 100,
                    ];
                }
            }
        }

        // Sort by min_quantity
        usort($tiers, function ($a, $b) {
            return $a['min_quantity'] - $b['min_quantity'];
        });

        return $tiers;
    }

    /**
     * Calculate recurring revenue for subscription products
     */
    public function calculateRecurringRevenue(Product $product, int $quantity = 1, ?string $billingCycle = null): array
    {
        if ($product->billing_model !== 'subscription') {
            throw new \Exception('Product is not a subscription');
        }

        $billingCycle = $billingCycle ?? $product->billing_cycle;
        $price = $product->base_price * $quantity;

        // Convert to monthly for MRR calculation
        $monthlyRevenue = match ($billingCycle) {
            'weekly' => $price * 4.33, // Average weeks per month
            'monthly' => $price,
            'quarterly' => $price / 3,
            'semi-annually' => $price / 6,
            'annually' => $price / 12,
            default => $price
        };

        // Calculate annual revenue
        $annualRevenue = match ($billingCycle) {
            'weekly' => $price * 52,
            'monthly' => $price * 12,
            'quarterly' => $price * 4,
            'semi-annually' => $price * 2,
            'annually' => $price,
            default => $price * 12
        };

        return [
            'billing_cycle' => $billingCycle,
            'cycle_price' => $price,
            'monthly_revenue' => round($monthlyRevenue, 2),
            'annual_revenue' => round($annualRevenue, 2),
            'quantity' => $quantity,
        ];
    }

    /**
     * Get best price for a product across all rules
     */
    public function getBestPrice(Product $product, ?Client $client = null, int $quantity = 1): array
    {
        $standardPricing = $this->calculatePrice($product, $client, $quantity);

        // Check for better bundle deals
        $bundles = ProductBundle::active()
            ->whereHas('products', function ($query) use ($product) {
                $query->where('products.id', $product->id);
            })
            ->get();

        $bestDeal = [
            'type' => 'standard',
            'price' => $standardPricing['unit_price'],
            'total' => $standardPricing['total'],
            'savings' => $standardPricing['savings'],
            'details' => $standardPricing,
        ];

        foreach ($bundles as $bundle) {
            try {
                $bundlePricing = $this->calculateBundlePrice($bundle, [$product->id], $client);
                $bundleUnitPrice = $bundlePricing['bundle_price'] / $quantity;

                if ($bundleUnitPrice < $bestDeal['price']) {
                    $bestDeal = [
                        'type' => 'bundle',
                        'bundle_id' => $bundle->id,
                        'bundle_name' => $bundle->name,
                        'price' => $bundleUnitPrice,
                        'total' => $bundlePricing['bundle_price'],
                        'savings' => $bundlePricing['savings'],
                        'details' => $bundlePricing,
                    ];
                }
            } catch (\Exception $e) {
                // Bundle might not be valid for this selection
                continue;
            }
        }

        return $bestDeal;
    }
}
