<?php

namespace Database\Factories;

use App\Models\ProductBundle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBundleFactory extends Factory
{
    protected $model = ProductBundle::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'sku' => $this->faker->optional()->word,
            'bundle_type' => $this->faker->numberBetween(1, 5),
            'pricing_type' => $this->faker->numberBetween(1, 5),
            'fixed_price' => $this->faker->randomFloat(2, 0, 10000),
            'discount_percentage' => $this->faker->optional()->word,
            'min_value' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'available_from' => $this->faker->optional()->word,
            'available_until' => $this->faker->optional()->word,
            'max_quantity' => $this->faker->optional()->word,
            'image_url' => $this->faker->optional()->url,
            'show_items_separately' => $this->faker->optional()->word,
            'sort_order' => $this->faker->optional()->word
        ];
    }
}
