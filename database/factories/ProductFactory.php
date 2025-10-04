<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'category_id' => \App\Models\Category::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'type' => fake()->randomElement(['product', 'service']),
            'base_price' => fake()->randomFloat(2, 10, 1000),
            'cost' => fake()->optional()->randomFloat(2, 5, 500),
            'currency_code' => 'USD',
            'unit_type' => 'units',
            'billing_model' => 'one_time',
            'is_active' => true,
        ];
    }
}
