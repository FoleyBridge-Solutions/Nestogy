<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\QuoteInvoiceConversion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteInvoiceConversionFactory extends Factory
{
    protected $model = QuoteInvoiceConversion::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'quote_id' => \App\Domains\Financial\Models\Quote::factory(),
            'invoice_id' => \App\Domains\Financial\Models\Invoice::factory(),
            'conversion_type' => 'full',
            'status' => 'completed',
        ];
    }
}
