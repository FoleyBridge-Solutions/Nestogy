<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\QuoteTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteTemplateFactory extends Factory
{
    protected $model = QuoteTemplate::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'category' => $this->faker->randomElement(['basic', 'standard', 'premium', 'custom']),
            'template_items' => json_encode([]),
            'service_config' => json_encode([]),
            'pricing_config' => json_encode([]),
            'tax_config' => json_encode([]),
            'terms_conditions' => $this->faker->optional()->sentence,
            'is_active' => $this->faker->boolean(70),
            'created_by' => \App\Domains\Core\Models\User::factory(),
        ];
    }
}
