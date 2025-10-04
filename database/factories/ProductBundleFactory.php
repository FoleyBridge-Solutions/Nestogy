<?php

namespace Database\Factories;

use App\Models\ProductBundle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBundleFactory extends Factory
{
    protected $model = ProductBundle::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'sku' => $this->faker->optional()->randomNumber(),
            'bundle_type' => $this->faker->randomElement(['fixed', 'configurable', 'dynamic']),
            'pricing_type' => $this->faker->randomElement(['sum', 'fixed', 'percentage_discount']),
            'fixed_price' => $this->faker->randomFloat(2, 0, 10000),
            'discount_percentage' => $this->faker->optional()->numberBetween(1, 100),
            'min_value' => $this->faker->optional()->randomFloat(2, 0, 9999.99),
            'is_active' => $this->faker->boolean(70),
            'available_from' => $this->faker->optional()->randomNumber(),
            'available_until' => $this->faker->optional()->randomNumber(),
            'max_quantity' => $this->faker->optional()->numberBetween(1, 100),
            'image_url' => $this->faker->optional()->url,
            'show_items_separately' => $this->faker->boolean(),
            'sort_order' => $this->faker->numberBetween(1, 100)
        ];
    }
}
