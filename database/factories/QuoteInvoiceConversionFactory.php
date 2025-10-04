<?php

namespace Database\Factories;

use App\Models\QuoteInvoiceConversion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteInvoiceConversionFactory extends Factory
{
    protected $model = QuoteInvoiceConversion::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'status' => 'pending',
            'activation_status' => 'pending',
            'current_step' => 1,
        ];
    }
}
