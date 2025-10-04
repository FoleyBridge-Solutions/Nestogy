<?php

namespace Database\Factories;

use App\Models\TaxApiQueryCache;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxApiQueryCacheFactory extends Factory
{
    protected $model = TaxApiQueryCache::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'api_provider' => $this->faker->randomElement(['avalara', 'taxjar', 'vertex']),
            'query_type' => $this->faker->numberBetween(1, 5),
            'query_hash' => $this->faker->sha256,
            'query_parameters' => json_encode([]),
            'api_response' => json_encode(['tax_amount' => 0]),
            'api_called_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'error_message' => $this->faker->optional()->randomNumber(),
            'response_time_ms' => $this->faker->optional()->randomFloat(2, 10, 999.99)
        ];
    }
}
