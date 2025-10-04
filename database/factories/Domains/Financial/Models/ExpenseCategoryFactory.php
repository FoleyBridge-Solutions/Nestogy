<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'code' => $this->faker->regexify('[A-Z]{3}[0-9]{3}'),
            'color' => $this->faker->optional()->hexColor,
            'is_active' => $this->faker->boolean(80),
            'requires_approval' => $this->faker->boolean(30),
            'approval_threshold' => $this->faker->optional()->randomFloat(2, 100, 10000),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'metadata' => json_encode([]),
        ];
    }
}
