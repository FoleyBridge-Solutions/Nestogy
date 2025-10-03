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
            'api_provider' => null,
            'query_type' => null,
            'query_hash' => null,
            'query_parameters' => null,
            'api_response' => null,
            'api_called_at' => null,
            'expires_at' => null,
            'status' => 'active',
            'error_message' => null,
            'response_time_ms' => null
        ];
    }
}
