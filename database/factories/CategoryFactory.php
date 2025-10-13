<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $types = array_keys(Category::TYPE_LABELS);
        $icons = ['folder', 'folder-open', 'tag', 'cube', 'document-text', 'chart-bar', 'book-open'];

        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement($types),
            'code' => $this->faker->optional()->bothify('CAT-###'),
            'slug' => $this->faker->optional()->slug(),
            'description' => $this->faker->optional()->sentence(),
            'color' => $this->faker->optional()->hexColor(),
            'icon' => $this->faker->optional()->randomElement($icons),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'metadata' => [],
        ];
    }

    public function expenseCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Category::TYPE_EXPENSE_CATEGORY,
        ]);
    }
}
