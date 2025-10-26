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
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'bucket_name' => $this->faker->words(3, true),
            'bucket_code' => 'BUCKET-'.strtoupper(uniqid()),
            'bucket_type' => 'included',
            'usage_type' => 'voice',
            'bucket_capacity' => 1000.0,
            'allocated_amount' => 0,
            'used_amount' => 0,
            'capacity_unit' => 'minutes',
            'is_active' => true,
            'bucket_status' => 'active',
        ];
    }
}
