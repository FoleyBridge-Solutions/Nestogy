<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'code' => $this->faker->regexify('[A-Z]{3}[0-9]{3}'),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'description' => $this->faker->optional()->sentence,
            'color' => $this->faker->optional()->randomNumber(),
            'is_active' => $this->faker->boolean(70)
        ];
    }
}
