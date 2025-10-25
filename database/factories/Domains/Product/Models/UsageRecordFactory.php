<?php

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\UsageRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageRecordFactory extends Factory
{
    protected $model = UsageRecord::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'amount' => $this->faker->randomFloat(2, 0, 1000),
        ];
    }
}
