<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\RecurringInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringInvoiceFactory extends Factory
{
    protected $model = RecurringInvoice::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
