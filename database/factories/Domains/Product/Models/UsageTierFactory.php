<?php

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\UsageTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageTierFactory extends Factory
{
    protected $model = UsageTier::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
