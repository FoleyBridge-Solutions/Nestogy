<?php

namespace App\Services;

use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class PricingRuleService
{
    public function create(array $data): PricingRule
    {
        $data['company_id'] = auth()->user()->company_id;

        // Handle JSON fields
        if (isset($data['conditions']) && is_string($data['conditions'])) {
            $data['conditions'] = json_decode($data['conditions'], true);
        }

        return PricingRule::create($data);
    }

    public function update(PricingRule $pricingRule, array $data): PricingRule
    {
        // Handle JSON fields
        if (isset($data['conditions']) && is_string($data['conditions'])) {
            $data['conditions'] = json_decode($data['conditions'], true);
        }

        $pricingRule->update($data);
        
        return $pricingRule->fresh();
    }

    public function delete(PricingRule $pricingRule): bool
    {
        return $pricingRule->delete();
    }

    public function findApplicableRules(Product $product, ?Client $client = null, int $quantity = 1): array
    {
        $query = PricingRule::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->where(function ($q) use ($product) {
                $q->whereNull('product_id')
                  ->orWhere('product_id', $product->id);
            })
            ->where(function ($q) use ($client) {
                $q->whereNull('client_id');
                if ($client) {
                    $q->orWhere('client_id', $client->id);
                }
            })
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc');

        $rules = $query->get();
        $applicableRules = [];

        foreach ($rules as $rule) {
            if ($this->isRuleApplicable($rule, $product, $client, $quantity)) {
                $applicableRules[] = $rule;
            }
        }

        return $applicableRules;
    }

    public function applyRules(array $rules, float $basePrice, int $quantity = 1): array
    {
        $appliedRules = [];
        $currentPrice = $basePrice;
        $totalDiscount = 0;

        foreach ($rules as $rule) {
            $result = $this->applyRule($rule, $currentPrice, $quantity);
            
            if ($result['applied']) {
                $appliedRules[] = [
                    'rule' => $rule,
                    'original_price' => $currentPrice,
                    'discount_amount' => $result['discount'],
                    'new_price' => $result['new_price'],
                    'description' => $this->getRuleDescription($rule),
                ];

                $currentPrice = $result['new_price'];
                $totalDiscount += $result['discount'];
            }
        }

        return [
            'original_price' => $basePrice,
            'final_price' => $currentPrice,
            'total_discount' => $totalDiscount,
            'applied_rules' => $appliedRules,
            'savings_percentage' => $basePrice > 0 ? round(($totalDiscount / $basePrice) * 100, 2) : 0,
        ];
    }

    protected function isRuleApplicable(PricingRule $rule, Product $product, ?Client $client, int $quantity): bool
    {
        // Check date range
        if ($rule->valid_from && now() < $rule->valid_from) {
            return false;
        }
        if ($rule->valid_until && now() > $rule->valid_until) {
            return false;
        }

        // Check conditions
        if ($rule->conditions) {
            return $this->evaluateConditions($rule->conditions, $product, $client, $quantity);
        }

        return true;
    }

    protected function evaluateConditions(array $conditions, Product $product, ?Client $client, int $quantity): bool
    {
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $product, $client, $quantity)) {
                return false;
            }
        }

        return true;
    }

    protected function evaluateCondition(array $condition, Product $product, ?Client $client, int $quantity): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';

        switch ($field) {
            case 'quantity':
                return $this->compareValues($quantity, $operator, (int) $value);
            
            case 'product_category':
                return $this->compareValues($product->category_id, $operator, (int) $value);
            
            case 'product_type':
                return $this->compareValues($product->type, $operator, $value);
            
            case 'client_type':
                if (!$client) return false;
                return $this->compareValues($client->type ?? '', $operator, $value);
            
            case 'total_value':
                $totalValue = $product->base_price * $quantity;
                return $this->compareValues($totalValue, $operator, (float) $value);
            
            case 'day_of_week':
                $dayOfWeek = now()->dayOfWeek; // 0 = Sunday, 6 = Saturday
                return $this->compareValues($dayOfWeek, $operator, (int) $value);
            
            case 'time_of_day':
                $hour = (int) now()->format('H');
                return $this->compareValues($hour, $operator, (int) $value);
            
            default:
                return true;
        }
    }

    protected function compareValues($actual, string $operator, $expected): bool
    {
        switch ($operator) {
            case '=':
            case '==':
                return $actual == $expected;
            
            case '!=':
                return $actual != $expected;
            
            case '>':
                return $actual > $expected;
            
            case '>=':
                return $actual >= $expected;
            
            case '<':
                return $actual < $expected;
            
            case '<=':
                return $actual <= $expected;
            
            case 'in':
                return in_array($actual, is_array($expected) ? $expected : explode(',', $expected));
            
            case 'not_in':
                return !in_array($actual, is_array($expected) ? $expected : explode(',', $expected));
            
            default:
                return false;
        }
    }

    protected function applyRule(PricingRule $rule, float $currentPrice, int $quantity): array
    {
        $discount = 0;
        $newPrice = $currentPrice;

        switch ($rule->rule_type) {
            case 'percentage_discount':
                $discount = ($currentPrice * $rule->discount_percentage) / 100;
                $newPrice = $currentPrice - $discount;
                break;
            
            case 'fixed_discount':
                $discount = min($rule->discount_amount, $currentPrice);
                $newPrice = $currentPrice - $discount;
                break;
            
            case 'fixed_price':
                $discount = max(0, $currentPrice - $rule->fixed_price);
                $newPrice = $rule->fixed_price;
                break;
            
            case 'bulk_pricing':
                if ($quantity >= $rule->min_quantity) {
                    $bulkDiscount = ($currentPrice * $rule->discount_percentage) / 100;
                    $discount = $bulkDiscount;
                    $newPrice = $currentPrice - $discount;
                }
                break;
            
            case 'buy_x_get_y':
                $freeItems = intval($quantity / $rule->buy_quantity) * $rule->get_quantity;
                $paidItems = $quantity - $freeItems;
                $discount = $freeItems * ($currentPrice / $quantity);
                $newPrice = $currentPrice - $discount;
                break;
        }

        // Apply minimum price constraint
        if ($rule->min_price && $newPrice < $rule->min_price) {
            $newPrice = $rule->min_price;
            $discount = $currentPrice - $newPrice;
        }

        return [
            'applied' => $discount > 0,
            'discount' => $discount,
            'new_price' => max(0, $newPrice),
        ];
    }

    protected function getRuleDescription(PricingRule $rule): string
    {
        switch ($rule->rule_type) {
            case 'percentage_discount':
                return "{$rule->discount_percentage}% discount";
            
            case 'fixed_discount':
                return "$" . number_format($rule->discount_amount, 2) . " off";
            
            case 'fixed_price':
                return "Fixed price: $" . number_format($rule->fixed_price, 2);
            
            case 'bulk_pricing':
                return "Bulk discount: {$rule->discount_percentage}% off for {$rule->min_quantity}+ items";
            
            case 'buy_x_get_y':
                return "Buy {$rule->buy_quantity} get {$rule->get_quantity} free";
            
            default:
                return $rule->name;
        }
    }

    public function testRule(PricingRule $rule, int $productId, ?int $clientId, int $quantity, float $basePrice): array
    {
        $product = Product::findOrFail($productId);
        $client = $clientId ? Client::find($clientId) : null;

        $isApplicable = $this->isRuleApplicable($rule, $product, $client, $quantity);
        
        if (!$isApplicable) {
            return [
                'applicable' => false,
                'reason' => 'Rule conditions not met',
                'original_price' => $basePrice,
                'final_price' => $basePrice,
                'discount' => 0,
            ];
        }

        $result = $this->applyRule($rule, $basePrice, $quantity);

        return [
            'applicable' => true,
            'original_price' => $basePrice,
            'final_price' => $result['new_price'],
            'discount' => $result['discount'],
            'description' => $this->getRuleDescription($rule),
        ];
    }

    public function getRuleUsageAnalytics(PricingRule $rule): array
    {
        // This would require tracking rule applications in invoice items
        // For now, return placeholder data
        return [
            'total_applications' => 0,
            'total_savings' => 0,
            'average_savings' => 0,
            'usage_trend' => [],
        ];
    }
}