<?php

namespace Database\Factories;

use App\Models\QuickActionFavorite;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuickActionFavoriteFactory extends Factory
{
    protected $model = QuickActionFavorite::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'system_action' => null,
            'position' => null
        ];
    }
}
