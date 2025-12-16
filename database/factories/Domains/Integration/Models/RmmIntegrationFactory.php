<?php

namespace Database\Factories\Domains\Integration\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Integration\Models\RmmIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

class RmmIntegrationFactory extends Factory
{
    protected $model = RmmIntegration::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'rmm_type' => 'TRMM',
            'name' => 'Tactical RMM - ' . $this->faker->company(),
            'api_url' => $this->faker->url(),
            'api_key' => $this->faker->uuid(),
            'is_active' => true,
            'last_sync_at' => null,
            'settings' => [
                'sync_interval_minutes' => 15,
                'sync_agents' => true,
                'sync_alerts' => true,
                'auto_create_tickets' => true,
            ],
            'total_agents' => 0,
            'last_alerts_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_sync_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'total_agents' => $this->faker->numberBetween(10, 500),
            'last_alerts_count' => $this->faker->numberBetween(0, 50),
        ]);
    }
}
