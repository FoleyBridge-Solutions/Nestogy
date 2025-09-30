<?php

namespace App\Livewire\Financial;

use App\Traits\QuotePricingCalculations;
use Livewire\Component;

class QuotePricingSummary extends Component
{
    use QuotePricingCalculations;

    public $pricing = [];

    public $currency_code = 'USD';

    public $showBreakdown = false;

    public $taxBreakdown = [];

    protected $listeners = [
        'pricingUpdated' => 'updatePricing',
    ];

    public function mount($pricing = [], $currency_code = 'USD')
    {
        $this->pricing = $pricing ?: [
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'savings' => 0,
            'recurring' => ['monthly' => 0, 'annual' => 0],
        ];

        $this->currency_code = $currency_code;
        $this->calculateTaxBreakdown();
    }

    public function updatePricing($newPricing)
    {
        $this->pricing = array_merge($this->pricing, $newPricing);
        $this->calculateTaxBreakdown();
    }

    public function toggleBreakdown()
    {
        $this->showBreakdown = ! $this->showBreakdown;
    }

    protected function calculateTaxBreakdown()
    {
        if (($this->pricing['tax'] ?? 0) > 0) {
            $this->taxBreakdown = [
                'jurisdictions' => [
                    [
                        'name' => 'State Tax',
                        'rate' => 6.25,
                        'amount' => ($this->pricing['tax'] ?? 0) * 0.8,
                    ],
                    [
                        'name' => 'Local Tax',
                        'rate' => 2.0,
                        'amount' => ($this->pricing['tax'] ?? 0) * 0.2,
                    ],
                ],
                'total_tax' => $this->pricing['tax'] ?? 0,
                'effective_rate' => ($this->pricing['subtotal'] ?? 0) > 0
                    ? (($this->pricing['tax'] ?? 0) / ($this->pricing['subtotal'] ?? 0)) * 100
                    : 0,
            ];
        } else {
            $this->taxBreakdown = [];
        }
    }

    // === COMPUTED PROPERTIES ===
    public function getFormattedSubtotalProperty()
    {
        return $this->formatCurrency($this->pricing['subtotal'] ?? 0);
    }

    public function getFormattedDiscountProperty()
    {
        return $this->formatCurrency($this->pricing['discount'] ?? 0);
    }

    public function getFormattedTaxProperty()
    {
        return $this->formatCurrency($this->pricing['tax'] ?? 0);
    }

    public function getFormattedTotalProperty()
    {
        return $this->formatCurrency($this->pricing['total'] ?? 0);
    }

    public function getFormattedSavingsProperty()
    {
        return $this->formatCurrency($this->pricing['savings'] ?? 0);
    }

    public function getFormattedRecurringMonthlyProperty()
    {
        return $this->formatCurrency($this->pricing['recurring']['monthly'] ?? 0);
    }

    public function getFormattedRecurringAnnualProperty()
    {
        return $this->formatCurrency($this->pricing['recurring']['annual'] ?? 0);
    }

    public function getHasRecurringProperty()
    {
        return ($this->pricing['recurring']['monthly'] ?? 0) > 0 ||
               ($this->pricing['recurring']['annual'] ?? 0) > 0;
    }

    public function getHasSavingsProperty()
    {
        return ($this->pricing['savings'] ?? 0) > 0;
    }

    public function getHasDiscountProperty()
    {
        return ($this->pricing['discount'] ?? 0) > 0;
    }

    public function getHasTaxProperty()
    {
        return ($this->pricing['tax'] ?? 0) > 0;
    }

    public function getTaxRateProperty()
    {
        $subtotal = $this->pricing['subtotal'] ?? 0;
        $tax = $this->pricing['tax'] ?? 0;

        if ($subtotal > 0) {
            return number_format(($tax / $subtotal) * 100, 2).'%';
        }

        return '0%';
    }

    protected function formatCurrency($amount, $currencyCode = null)
    {
        $currency = $currencyCode ?? $this->currency_code;

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
        ];

        $symbol = $symbols[$currency] ?? $currency;

        return $symbol.number_format($amount, 2);
    }

    public function render()
    {
        return view('livewire.financial.quote-pricing-summary');
    }
}
