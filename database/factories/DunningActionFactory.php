<?php

namespace Database\Factories;

use App\Models\DunningAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class DunningActionFactory extends Factory
{
    protected $model = DunningAction::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
