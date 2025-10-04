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
            'company_id' => \App\Models\Company::factory(),
            'event_type' => $this->faker->randomElement(['create', 'update', 'delete', 'view', 'login']),
            'model_type' => $this->faker->optional()->word,
            'model_id' => $this->faker->optional()->numberBetween(1, 100),
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted', 'viewed', 'logged_in']),
            'old_values' => $this->faker->optional()->passthrough(json_encode(['key' => 'value'])),
            'new_values' => $this->faker->optional()->passthrough(json_encode(['key' => 'new_value'])),
            'metadata' => json_encode([]),
            'ip_address' => $this->faker->optional()->ipv4,
            'user_agent' => $this->faker->optional()->userAgent,
            'session_id' => $this->faker->optional()->uuid,
            'request_method' => $this->faker->optional()->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'request_url' => $this->faker->optional()->url,
            'request_headers' => $this->faker->optional()->passthrough(json_encode(['Accept' => 'application/json'])),
            'request_body' => $this->faker->optional()->passthrough(json_encode(['data' => 'value'])),
            'response_status' => $this->faker->optional()->numberBetween(200, 500),
            'execution_time' => $this->faker->optional()->randomFloat(3, 0, 10),
            'severity' => $this->faker->randomElement(['info', 'warning', 'error', 'critical']),
        ];
    }
}
