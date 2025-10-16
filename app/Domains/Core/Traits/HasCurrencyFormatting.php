<?php

namespace App\Domains\Core\Traits;

trait HasCurrencyFormatting
{
    public function formatCurrency(float $amount): string
    {
        $symbol = $this->getCurrencySymbol();

        return $symbol.number_format($amount, 2);
    }

    public function getCurrencySymbol(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
        ];

        return $symbols[$this->currency_code] ?? $this->currency_code;
    }
}
