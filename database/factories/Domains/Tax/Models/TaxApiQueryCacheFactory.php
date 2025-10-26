<?php

namespace Database\Factories\Domains\Tax\Models;

use App\Domains\Tax\Models\TaxApiQueryCache;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxApiQueryCacheFactory extends Factory
{
    protected $model = TaxApiQueryCache::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'api_provider' => $this->faker->randomElement(['vat_comply', 'nominatim', 'fcc']),
            'query_type' => $this->faker->randomElement(['rate_lookup', 'address_validation', 'jurisdiction']),
            'query_hash' => $this->faker->sha256,
            'query_parameters' => [],
            'api_response' => ['tax_amount' => 0],
            'api_called_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => $this->faker->randomElement(['active', 'expired', 'error']),
            'error_message' => $this->faker->optional()->sentence,
            'response_time_ms' => $this->faker->optional()->randomFloat(2, 10, 999.99)
        ];
    }
}
