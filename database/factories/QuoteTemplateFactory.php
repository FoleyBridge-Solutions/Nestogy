<?php

namespace Database\Factories;

use App\Models\QuoteTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteTemplateFactory extends Factory
{
    protected $model = QuoteTemplate::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'category' => null,
            'template_items' => null,
            'service_config' => null,
            'pricing_config' => null,
            'tax_config' => null,
            'terms_conditions' => null,
            'is_active' => true,
            'created_by' => null
        ];
    }
}
