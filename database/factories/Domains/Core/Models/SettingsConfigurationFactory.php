<?php

namespace Database\Factories\Domains\Core\Models;

use App\Domains\Core\Models\SettingsConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingsConfigurationFactory extends Factory
{
    protected $model = SettingsConfiguration::class;

    public function definition(): array
    {
        return [
            'company_id' => null, // Must be set when creating
            'domain' => $this->faker->randomElement(['general', 'billing', 'security', 'notifications', 'integrations']),
            'category' => $this->faker->randomElement(['system', 'user', 'company', 'feature']),
            'settings' => json_encode([
                'enabled' => $this->faker->boolean(80),
                'value' => $this->faker->word(),
            ]),
            'metadata' => json_encode([
                'created_by' => 'system',
                'version' => '1.0',
            ]),
            'is_active' => $this->faker->boolean(90),
            'last_modified_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_modified_by' => null, // Will be set to a user from the same company if needed
        ];
    }
}
