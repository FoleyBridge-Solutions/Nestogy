<?php

namespace Database\Factories;

use App\Models\Recurring;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringFactory extends Factory
{
    protected $model = Recurring::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'prefix' => $this->faker->optional()->word,
            'number' => $this->faker->optional()->word,
            'scope' => $this->faker->optional()->word,
            'frequency' => $this->faker->optional()->word,
            'last_sent' => $this->faker->optional()->word,
            'next_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'status' => 'active',
            'billing_type' => $this->faker->numberBetween(1, 5),
            'discount_amount' => $this->faker->randomFloat(2, 0, 10000),
            'discount_type' => $this->faker->numberBetween(1, 5),
            'amount' => $this->faker->randomFloat(2, 0, 10000),
            'currency_code' => 'USD',
            'note' => $this->faker->optional()->word,
            'internal_notes' => $this->faker->optional()->sentence,
            'voip_config' => $this->faker->optional()->word,
            'pricing_model' => $this->faker->optional()->word,
            'service_tiers' => $this->faker->optional()->word,
            'usage_allowances' => $this->faker->optional()->word,
            'overage_rates' => $this->faker->optional()->word,
            'auto_invoice_generation' => $this->faker->optional()->word,
            'invoice_terms_days' => $this->faker->optional()->word,
            'email_invoice' => $this->faker->safeEmail,
            'email_template' => $this->faker->safeEmail,
            'proration_enabled' => $this->faker->boolean(70),
            'proration_method' => $this->faker->optional()->word,
            'contract_escalation' => $this->faker->optional()->word,
            'escalation_percentage' => $this->faker->optional()->word,
            'escalation_months' => $this->faker->optional()->word,
            'last_escalation' => $this->faker->optional()->word,
            'tax_settings' => $this->faker->optional()->word,
            'max_invoices' => $this->faker->optional()->word,
            'invoices_generated' => $this->faker->optional()->word,
            'metadata' => $this->faker->optional()->word
        ];
    }
}
