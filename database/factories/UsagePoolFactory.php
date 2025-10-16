<?php

namespace Database\Factories;

use App\Domains\Product\Models\UsagePool;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsagePoolFactory extends Factory
{
    protected $model = UsagePool::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
