<?php

namespace Database\Factories;

use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'prefix' => null,
            'number' => null,
            'scope' => null,
            'status' => 'active',
            'approval_status' => 'active',
            'date' => null,
            'expire' => null,
            'valid_until' => null,
            'discount_amount' => null,
            'amount' => null,
            'currency_code' => null,
            'note' => null,
            'terms_conditions' => null,
            'url_key' => null,
            'auto_renew' => null,
            'auto_renew_days' => null,
            'template_name' => $this->faker->words(3, true),
            'voip_config' => null,
            'pricing_model' => null,
            'sent_at' => null,
            'viewed_at' => null,
            'accepted_at' => null,
            'declined_at' => null,
            'created_by' => null,
            'approved_by' => null
        ];
    }
}
