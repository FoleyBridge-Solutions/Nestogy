<?php

namespace Database\Factories;

use App\Models\UsageTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageTierFactory extends Factory
{
    protected $model = UsageTier::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
