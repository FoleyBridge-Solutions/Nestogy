<?php

namespace Database\Factories\Integration;

use App\Domains\Integration\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        $provider = $this->faker->randomElement(['connectwise', 'datto', 'ninja', 'generic']);

        return [
            'uuid' => Str::uuid(),
            'company_id' => 1, // Will be overridden in tests
            'provider' => $provider,
            'name' => $this->faker->company.' '.ucfirst($provider).' Integration',
            'api_endpoint' => $this->faker->url,
            'webhook_url' => $this->faker->url.'/webhook',
            'credentials_encrypted' => encrypt(json_encode([
                'api_key' => 'test-api-key-'.Str::random(32),
                'secret' => Str::random(64),
            ])),
            'field_mappings' => Integration::getDefaultFieldMappings($provider),
            'alert_rules' => Integration::getDefaultAlertRules($provider),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'last_sync' => $this->faker->optional(0.7)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function connectwise(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'connectwise',
            'name' => $this->faker->company.' ConnectWise Integration',
            'field_mappings' => Integration::getDefaultFieldMappings('connectwise'),
            'alert_rules' => Integration::getDefaultAlertRules('connectwise'),
        ]);
    }

    public function datto(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'datto',
            'name' => $this->faker->company.' Datto Integration',
            'field_mappings' => Integration::getDefaultFieldMappings('datto'),
            'alert_rules' => Integration::getDefaultAlertRules('datto'),
        ]);
    }

    public function ninja(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'ninja',
            'name' => $this->faker->company.' NinjaOne Integration',
            'field_mappings' => Integration::getDefaultFieldMappings('ninja'),
            'alert_rules' => Integration::getDefaultAlertRules('ninja'),
        ]);
    }

    public function generic(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'generic',
            'name' => $this->faker->company.' Generic RMM Integration',
            'field_mappings' => Integration::getDefaultFieldMappings('generic'),
            'alert_rules' => Integration::getDefaultAlertRules('generic'),
        ]);
    }

    public function withRecentSync(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_sync' => now()->subMinutes($this->faker->numberBetween(5, 60)),
        ]);
    }

    public function withOldSync(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_sync' => now()->subDays($this->faker->numberBetween(2, 30)),
        ]);
    }
}
