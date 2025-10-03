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
            'prefix' => $this->faker->optional()->word,
            'number' => $this->faker->optional()->word,
            'scope' => $this->faker->optional()->word,
            'status' => 'active',
            'approval_status' => 'active',
            'date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'expire' => $this->faker->optional()->word,
            'valid_until' => $this->faker->optional()->word,
            'discount_amount' => $this->faker->randomFloat(2, 0, 10000),
            'amount' => $this->faker->randomFloat(2, 0, 10000),
            'currency_code' => 'USD',
            'note' => $this->faker->optional()->word,
            'terms_conditions' => $this->faker->optional()->word,
            'url_key' => $this->faker->optional()->url,
            'auto_renew' => $this->faker->optional()->word,
            'auto_renew_days' => $this->faker->optional()->word,
            'template_name' => $this->faker->words(3, true),
            'voip_config' => $this->faker->optional()->word,
            'pricing_model' => $this->faker->optional()->word,
            'sent_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'viewed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'accepted_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'declined_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'created_by' => $this->faker->optional()->word,
            'approved_by' => $this->faker->optional()->word
        ];
    }
}
