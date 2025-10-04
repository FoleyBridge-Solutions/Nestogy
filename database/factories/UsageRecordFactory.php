<?php

namespace Database\Factories;

use App\Models\UsageRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageRecordFactory extends Factory
{
    protected $model = UsageRecord::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'amount' => $this->faker->randomFloat(2, 0, 1000),
        ];
    }
}
