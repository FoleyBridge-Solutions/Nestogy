<?php

namespace Database\Factories;

use App\Models\UsageBucket;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageBucketFactory extends Factory
{
    protected $model = UsageBucket::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
