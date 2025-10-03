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
            'domain' => null,
            'category' => null,
            'settings' => null,
            'metadata' => null,
            'is_active' => true,
            'last_modified_at' => null,
            'last_modified_by' => null
        ];
    }
}
