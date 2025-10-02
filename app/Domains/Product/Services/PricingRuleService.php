<?php

namespace App\Domains\Product\Services;

use App\Models\PricingRule;
use Illuminate\Support\Facades\DB;

class PricingRuleService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return PricingRule::create($data);
        });
    }

    public function update(PricingRule $pricingRule, array $data)
    {
        return DB::transaction(function () use ($pricingRule, $data) {
            $pricingRule->update($data);
            return $pricingRule;
        });
    }

    public function delete(PricingRule $pricingRule)
    {
        return DB::transaction(function () use ($pricingRule) {
            return $pricingRule->delete();
        });
    }

    public function testRule(PricingRule $pricingRule, array $testData)
    {
        // Test the pricing rule against sample data
        return [
            'applies' => $this->ruleApplies($pricingRule, $testData),
            'original_price' => $testData['price'] ?? 0,
            'adjusted_price' => $this->calculateAdjustedPrice($pricingRule, $testData),
        ];
    }

    protected function ruleApplies(PricingRule $pricingRule, array $data)
    {
        // Check if rule conditions are met
        return true; // Simplified
    }

    protected function calculateAdjustedPrice(PricingRule $pricingRule, array $data)
    {
        $basePrice = $data['price'] ?? 0;
        
        // Apply pricing rule logic
        return $basePrice;
    }

    public function bulkUpdate(array $ruleIds, array $data)
    {
        return DB::transaction(function () use ($ruleIds, $data) {
            return PricingRule::whereIn('id', $ruleIds)->update($data);
        });
    }
}
