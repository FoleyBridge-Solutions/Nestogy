<?php

namespace Database\Factories;

use App\Models\SettingsConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingsConfigurationFactory extends Factory
{
    protected $model = SettingsConfiguration::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'domain' => $this->faker->optional()->word,
            'category' => $this->faker->optional()->word,
            'settings' => $this->faker->optional()->word,
            'metadata' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'last_modified_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_modified_by' => $this->faker->optional()->word
        ];
    }
}
