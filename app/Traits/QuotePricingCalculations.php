<?php

namespace App\Traits;

trait QuotePricingCalculations
{
    /**
     * Calculate subtotal for a single item
     */
    public function calculateItemSubtotal($item): float
    {
        $quantity = (float)($item['quantity'] ?? 0);
        $unitPrice = (float)($item['unit_price'] ?? 0);
        $discount = (float)($item['discount'] ?? 0);
        
        $baseAmount = $quantity * $unitPrice;
        return max(0, $baseAmount - $discount);
    }

    /**
     * Update all pricing calculations
     */
    public function updatePricing(): void
    {
        // Calculate subtotal from all items
        $subtotal = array_sum(array_map(function ($item) {
            return $this->calculateItemSubtotal($item);
        }, $this->selectedItems));

        // Calculate total discount
        $discountAmount = $this->calculateDiscountAmount($subtotal);

        // Calculate tax
        $taxAmount = $this->calculateTax();

        // Calculate total
        $total = $subtotal - $discountAmount + $taxAmount;

        // Calculate recurring revenue
        $recurring = $this->calculateRecurringRevenue();

        // Calculate savings
        $savings = $this->calculateSavings();

        // Update pricing state
        $this->pricing = [
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'tax' => $taxAmount,
            'total' => $total,
            'savings' => $savings,
            'recurring' => $recurring
        ];
    }

    /**
     * Calculate discount amount based on type
     */
    protected function calculateDiscountAmount($subtotal): float
    {
        $discountAmount = (float)($this->discount_amount ?? 0);
        
        if (($this->discount_type ?? 'fixed') === 'percentage') {
            return $subtotal * ($discountAmount / 100);
        }
        
        return $discountAmount;
    }

    /**
     * Calculate total tax from all items
     */
    protected function calculateTax(): float
    {
        return array_sum(array_map(function ($item) {
            $itemSubtotal = $this->calculateItemSubtotal($item);
            $taxRate = (float)($item['tax_rate'] ?? 0);
            return $itemSubtotal * ($taxRate / 100);
        }, $this->selectedItems));
    }

    /**
     * Calculate recurring revenue projections
     */
    public function calculateRecurringRevenue(): array
    {
        $monthly = 0;
        $annual = 0;

        foreach ($this->selectedItems as $item) {
            $amount = $this->calculateItemSubtotal($item);
            $billingCycle = $item['billing_cycle'] ?? 'one_time';
            
            switch ($billingCycle) {
                case 'monthly':
                    $monthly += $amount;
                    $annual += $amount * 12;
                    break;
                case 'quarterly':
                    $monthly += $amount / 3;
                    $annual += $amount * 4;
                    break;
                case 'semi_annually':
                    $monthly += $amount / 6;
                    $annual += $amount * 2;
                    break;
                case 'annually':
                    $monthly += $amount / 12;
                    $annual += $amount;
                    break;
            }
        }

        return [
            'monthly' => $monthly,
            'annual' => $annual
        ];
    }

    /**
     * Calculate total savings from volume discounts, promos, etc.
     */
    protected function calculateSavings(): float
    {
        return array_sum(array_map(function ($item) {
            return (float)($item['savings'] ?? 0);
        }, $this->selectedItems));
    }

    /**
     * Get applied pricing rules for display
     */
    public function getAppliedPricingRules(): array
    {
        $rules = [];
        
        // Volume discount rules
        if (count($this->selectedItems) >= 10) {
            $rules[] = [
                'type' => 'volume_discount',
                'description' => '10+ items bulk discount',
                'value' => '5%'
            ];
        }

        // Early payment discount
        if (isset($this->billingConfig['paymentTerms']) && $this->billingConfig['paymentTerms'] <= 15) {
            $rules[] = [
                'type' => 'early_payment',
                'description' => 'Early payment discount',
                'value' => '2%'
            ];
        }

        return $rules;
    }

    /**
     * Format currency amount
     */
    public function formatCurrency($amount, $currencyCode = null): string
    {
        $currency = $currencyCode ?? ($this->currency_code ?? 'USD');
        
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥'
        ];

        $symbol = $symbols[$currency] ?? $currency;
        return $symbol . number_format($amount, 2);
    }

    /**
     * Calculate tax breakdown by jurisdiction (for advanced tax systems)
     */
    public function calculateTaxBreakdown(): array
    {
        // This would integrate with the existing tax calculation system
        // For now, return a simple structure
        
        $totalTax = $this->calculateTax();
        
        if ($totalTax <= 0) {
            return [];
        }

        return [
            'jurisdictions' => [
                [
                    'name' => 'State Tax',
                    'rate' => 6.25,
                    'amount' => $totalTax * 0.8
                ],
                [
                    'name' => 'Local Tax',
                    'rate' => 2.0,
                    'amount' => $totalTax * 0.2
                ]
            ],
            'total_tax' => $totalTax,
            'effective_rate' => $this->pricing['subtotal'] > 0 
                ? ($totalTax / $this->pricing['subtotal']) * 100 
                : 0
        ];
    }

    /**
     * Validate pricing data
     */
    public function validatePricing(): array
    {
        $errors = [];
        
        // Check for negative values
        if ($this->pricing['subtotal'] < 0) {
            $errors[] = 'Subtotal cannot be negative';
        }
        
        if ($this->pricing['total'] < 0) {
            $errors[] = 'Total amount cannot be negative';
        }
        
        // Check discount validity
        if (($this->discount_type ?? 'fixed') === 'percentage' && ($this->discount_amount ?? 0) > 100) {
            $errors[] = 'Discount percentage cannot exceed 100%';
        }
        
        if (($this->discount_amount ?? 0) < 0) {
            $errors[] = 'Discount amount cannot be negative';
        }
        
        // Check individual items
        foreach ($this->selectedItems as $index => $item) {
            if (($item['quantity'] ?? 0) <= 0) {
                $errors[] = "Item " . ($index + 1) . ": Quantity must be greater than 0";
            }
            
            if (($item['unit_price'] ?? 0) < 0) {
                $errors[] = "Item " . ($index + 1) . ": Unit price cannot be negative";
            }
        }
        
        return $errors;
    }

    /**
     * Get pricing summary for display
     */
    public function getPricingSummary(): array
    {
        $this->updatePricing();
        
        return [
            'subtotal' => [
                'amount' => $this->pricing['subtotal'],
                'formatted' => $this->formatCurrency($this->pricing['subtotal'])
            ],
            'discount' => [
                'amount' => $this->pricing['discount'],
                'formatted' => $this->formatCurrency($this->pricing['discount']),
                'type' => $this->discount_type ?? 'fixed',
                'rate' => $this->discount_amount ?? 0
            ],
            'tax' => [
                'amount' => $this->pricing['tax'],
                'formatted' => $this->formatCurrency($this->pricing['tax']),
                'breakdown' => $this->calculateTaxBreakdown()
            ],
            'total' => [
                'amount' => $this->pricing['total'],
                'formatted' => $this->formatCurrency($this->pricing['total'])
            ],
            'recurring' => [
                'monthly' => [
                    'amount' => $this->pricing['recurring']['monthly'],
                    'formatted' => $this->formatCurrency($this->pricing['recurring']['monthly'])
                ],
                'annual' => [
                    'amount' => $this->pricing['recurring']['annual'],
                    'formatted' => $this->formatCurrency($this->pricing['recurring']['annual'])
                ]
            ],
            'savings' => [
                'amount' => $this->pricing['savings'],
                'formatted' => $this->formatCurrency($this->pricing['savings'])
            ],
            'applied_rules' => $this->getAppliedPricingRules()
        ];
    }

    /**
     * Apply template pricing
     */
    public function applyTemplatePricing($template): void
    {
        if (isset($template['discount_type'])) {
            $this->discount_type = $template['discount_type'];
        }
        
        if (isset($template['discount_amount'])) {
            $this->discount_amount = $template['discount_amount'];
        }
        
        if (isset($template['pricing_model']) && is_array($template['pricing_model'])) {
            $this->billingConfig = array_merge($this->billingConfig, $template['pricing_model']);
        }
        
        $this->updatePricing();
    }

    /**
     * Calculate total with custom discount
     */
    public function calculateTotalWithDiscount($discountType, $discountAmount): float
    {
        $subtotal = $this->pricing['subtotal'];
        $tax = $this->pricing['tax'];
        
        $discount = 0;
        if ($discountType === 'percentage') {
            $discount = $subtotal * ($discountAmount / 100);
        } else {
            $discount = $discountAmount;
        }
        
        return max(0, $subtotal - $discount + $tax);
    }

    /**
     * Get item pricing details for display
     */
    public function getItemPricingDetails($item): array
    {
        return [
            'subtotal' => $this->calculateItemSubtotal($item),
            'tax_amount' => $this->calculateItemSubtotal($item) * (($item['tax_rate'] ?? 0) / 100),
            'total_with_tax' => $this->calculateItemSubtotal($item) * (1 + (($item['tax_rate'] ?? 0) / 100)),
            'formatted_subtotal' => $this->formatCurrency($this->calculateItemSubtotal($item)),
            'formatted_total' => $this->formatCurrency(
                $this->calculateItemSubtotal($item) * (1 + (($item['tax_rate'] ?? 0) / 100))
            )
        ];
    }
}