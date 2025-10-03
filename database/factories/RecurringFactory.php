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
            'prefix' => null,
            'number' => null,
            'scope' => null,
            'frequency' => null,
            'last_sent' => null,
            'next_date' => null,
            'end_date' => null,
            'status' => 'active',
            'billing_type' => null,
            'discount_amount' => null,
            'discount_type' => null,
            'amount' => null,
            'currency_code' => null,
            'note' => null,
            'internal_notes' => null,
            'voip_config' => null,
            'pricing_model' => null,
            'service_tiers' => null,
            'usage_allowances' => null,
            'overage_rates' => null,
            'auto_invoice_generation' => null,
            'invoice_terms_days' => null,
            'email_invoice' => $this->faker->safeEmail,
            'email_template' => $this->faker->safeEmail,
            'proration_enabled' => null,
            'proration_method' => null,
            'contract_escalation' => null,
            'escalation_percentage' => null,
            'escalation_months' => null,
            'last_escalation' => null,
            'tax_settings' => null,
            'max_invoices' => null,
            'invoices_generated' => null,
            'metadata' => null
        ];
    }
}
