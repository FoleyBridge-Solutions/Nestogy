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
            'description' => $this->faker->optional()->sentence,
            'category' => $this->faker->optional()->word,
            'template_items' => $this->faker->optional()->word,
            'service_config' => $this->faker->optional()->word,
            'pricing_config' => $this->faker->optional()->word,
            'tax_config' => $this->faker->optional()->word,
            'terms_conditions' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'created_by' => $this->faker->optional()->word
        ];
    }
}
