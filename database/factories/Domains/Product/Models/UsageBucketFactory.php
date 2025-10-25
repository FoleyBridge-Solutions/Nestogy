<?php

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\UsageBucket;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageBucketFactory extends Factory
{
    protected $model = UsageBucket::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
