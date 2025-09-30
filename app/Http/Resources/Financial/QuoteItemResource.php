<?php

namespace App\Http\Resources\Financial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Quote Item API Resource
 * Standardizes quote item JSON responses
 */
class QuoteItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quote_id' => $this->quote_id,

            // Product references
            'product_id' => $this->product_id,
            'service_id' => $this->service_id,
            'bundle_id' => $this->bundle_id,

            // Product information (when loaded)
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'description' => $this->product->description,
                ];
            }),

            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'description' => $this->service->description,
                ];
            }),

            'bundle' => $this->whenLoaded('bundle', function () {
                return [
                    'id' => $this->bundle->id,
                    'name' => $this->bundle->name,
                    'description' => $this->bundle->description,
                ];
            }),

            // Item details
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'type' => $this->type,

            // Pricing
            'quantity' => (float) $this->quantity,
            'unit_price' => $this->formatCurrency($this->unit_price),
            'discount' => $this->formatCurrency($this->discount),
            'tax_rate' => (float) $this->tax_rate,
            'subtotal' => $this->formatCurrency($this->subtotal),

            // Raw pricing values for calculations
            'pricing_raw' => [
                'unit_price' => (float) $this->unit_price,
                'discount' => (float) $this->discount,
                'subtotal' => (float) $this->subtotal,
                'line_total' => $this->getLineTotal(),
            ],

            // Configuration
            'billing_cycle' => $this->billing_cycle,
            'billing_cycle_label' => $this->getBillingCycleLabel(),
            'order' => $this->order,

            // Computed values
            'discount_percentage' => $this->getDiscountPercentage(),
            'total_with_tax' => $this->formatCurrency($this->getTotalWithTax()),
            'monthly_value' => $this->formatCurrency($this->getMonthlyValue()),
            'annual_value' => $this->formatCurrency($this->getAnnualValue()),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Format currency value
     */
    protected function formatCurrency(?float $amount): string
    {
        if ($amount === null) {
            return '$0.00';
        }

        return number_format($amount, 2, '.', ',');
    }

    /**
     * Get billing cycle label
     */
    protected function getBillingCycleLabel(): string
    {
        return match ($this->billing_cycle) {
            'one_time' => 'One-time',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semi_annually' => 'Semi-annually',
            'annually' => 'Annually',
            default => ucfirst(str_replace('_', ' ', $this->billing_cycle))
        };
    }

    /**
     * Calculate line total (subtotal - discount)
     */
    protected function getLineTotal(): float
    {
        return max(0, (float) $this->subtotal - (float) $this->discount);
    }

    /**
     * Calculate discount percentage
     */
    protected function getDiscountPercentage(): float
    {
        $subtotal = (float) $this->subtotal;
        $discount = (float) $this->discount;

        if ($subtotal <= 0) {
            return 0;
        }

        return round(($discount / $subtotal) * 100, 2);
    }

    /**
     * Calculate total with tax
     */
    protected function getTotalWithTax(): float
    {
        $lineTotal = $this->getLineTotal();
        $taxAmount = $lineTotal * ((float) $this->tax_rate / 100);

        return $lineTotal + $taxAmount;
    }

    /**
     * Get monthly equivalent value
     */
    protected function getMonthlyValue(): float
    {
        $lineTotal = $this->getLineTotal();

        return match ($this->billing_cycle) {
            'monthly' => $lineTotal,
            'quarterly' => $lineTotal / 3,
            'semi_annually' => $lineTotal / 6,
            'annually' => $lineTotal / 12,
            'one_time' => 0,
            default => 0
        };
    }

    /**
     * Get annual equivalent value
     */
    protected function getAnnualValue(): float
    {
        $lineTotal = $this->getLineTotal();

        return match ($this->billing_cycle) {
            'monthly' => $lineTotal * 12,
            'quarterly' => $lineTotal * 4,
            'semi_annually' => $lineTotal * 2,
            'annually' => $lineTotal,
            'one_time' => 0,
            default => 0
        };
    }

    /**
     * Create a minimal resource for simple listings
     *
     * @return array<string, mixed>
     */
    public function toArrayMinimal(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'quantity' => (float) $this->quantity,
            'unit_price' => $this->formatCurrency($this->unit_price),
            'subtotal' => $this->formatCurrency($this->subtotal),
            'billing_cycle' => $this->billing_cycle,
            'order' => $this->order,
        ];
    }

    /**
     * Create an export-friendly format
     *
     * @return array<string, mixed>
     */
    public function toArrayExport(Request $request): array
    {
        return [
            'Item Name' => $this->name,
            'Description' => $this->description,
            'Category' => $this->category,
            'Type' => $this->type,
            'Quantity' => $this->quantity,
            'Unit Price' => $this->unit_price,
            'Discount' => $this->discount,
            'Subtotal' => $this->subtotal,
            'Tax Rate' => $this->tax_rate.'%',
            'Total with Tax' => $this->getTotalWithTax(),
            'Billing Cycle' => $this->getBillingCycleLabel(),
            'Monthly Value' => $this->getMonthlyValue(),
            'Annual Value' => $this->getAnnualValue(),
        ];
    }
}
