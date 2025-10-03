<?php

namespace Database\Factories;

use App\Models\CustomQuickAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomQuickActionFactory extends Factory
{
    protected $model = CustomQuickAction::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'title' => null,
            'description' => $this->faker->sentence,
            'icon' => null,
            'color' => null,
            'type' => null,
            'target' => null,
            'parameters' => null,
            'open_in' => null,
            'visibility' => null,
            'allowed_roles' => null,
            'permission' => null,
            'position' => null,
            'is_active' => true,
            'usage_count' => null,
            'last_used_at' => null
        ];
    }
}
