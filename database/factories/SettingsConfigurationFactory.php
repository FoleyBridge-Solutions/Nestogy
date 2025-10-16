<?php

namespace Database\Factories;

use App\Domains\Core\Models\SettingsConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingsConfigurationFactory extends Factory
{
    protected $model = SettingsConfiguration::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'domain' => 'general',
            'category' => 'system',
            'settings' => json_encode([]),
            'metadata' => json_encode([]),
            'is_active' => $this->faker->boolean(70),
            'last_modified_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_modified_by' => \App\Domains\Core\Models\User::factory()
        ];
    }
}
