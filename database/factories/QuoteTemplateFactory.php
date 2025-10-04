<?php

namespace Database\Factories;

use App\Models\QuoteTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteTemplateFactory extends Factory
{
    protected $model = QuoteTemplate::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'category' => $this->faker->randomElement(['basic', 'standard', 'premium', 'custom']),
            'template_items' => $this->faker->optional()->randomNumber(),
            'service_config' => $this->faker->optional()->randomNumber(),
            'pricing_config' => $this->faker->optional()->randomNumber(),
            'tax_config' => $this->faker->optional()->randomFloat(2, 0, 1000),
            'terms_conditions' => $this->faker->optional()->randomNumber(),
            'is_active' => $this->faker->boolean(70),
            'created_by' => \App\Models\User::factory()
        ];
    }
}
