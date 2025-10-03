<?php

namespace Database\Factories;

use App\Models\RecurringInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringInvoiceFactory extends Factory
{
    protected $model = RecurringInvoice::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
