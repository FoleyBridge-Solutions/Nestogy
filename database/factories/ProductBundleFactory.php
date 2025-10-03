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
            'description' => $this->faker->sentence,
            'sku' => null,
            'bundle_type' => null,
            'pricing_type' => null,
            'fixed_price' => null,
            'discount_percentage' => null,
            'min_value' => null,
            'is_active' => true,
            'available_from' => null,
            'available_until' => null,
            'max_quantity' => null,
            'image_url' => null,
            'show_items_separately' => null,
            'sort_order' => null
        ];
    }
}
