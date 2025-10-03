<?php

namespace Database\Factories;

use App\Models\TaxApiQueryCache;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxApiQueryCacheFactory extends Factory
{
    protected $model = TaxApiQueryCache::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'api_provider' => $this->faker->optional()->word,
            'query_type' => $this->faker->numberBetween(1, 5),
            'query_hash' => $this->faker->optional()->word,
            'query_parameters' => $this->faker->optional()->word,
            'api_response' => $this->faker->optional()->word,
            'api_called_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'expires_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'status' => 'active',
            'error_message' => $this->faker->optional()->word,
            'response_time_ms' => $this->faker->optional()->word
        ];
    }
}
