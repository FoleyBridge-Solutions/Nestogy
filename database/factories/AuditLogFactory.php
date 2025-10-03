<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'event_type' => $this->faker->numberBetween(1, 5),
            'model_type' => $this->faker->numberBetween(1, 5),
            'action' => $this->faker->optional()->word,
            'old_values' => $this->faker->optional()->word,
            'new_values' => $this->faker->optional()->word,
            'metadata' => $this->faker->optional()->word,
            'ip_address' => $this->faker->optional()->word,
            'user_agent' => $this->faker->optional()->word,
            'request_method' => $this->faker->optional()->word,
            'request_url' => $this->faker->optional()->url,
            'request_headers' => $this->faker->optional()->word,
            'request_body' => $this->faker->optional()->word,
            'response_status' => 'active',
            'execution_time' => $this->faker->optional()->word,
            'severity' => $this->faker->optional()->word
        ];
    }
}
