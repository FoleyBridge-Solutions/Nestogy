<?php

namespace Database\Factories;

use App\Models\TaxApiSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxApiSettingsFactory extends Factory
{
    protected $model = TaxApiSettings::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'provider' => null,
            'enabled' => null,
            'credentials' => null,
            'configuration' => null,
            'monthly_api_calls' => null,
            'monthly_limit' => null,
            'last_api_call' => null,
            'monthly_cost' => null,
            'status' => 'active',
            'last_error' => null,
            'last_health_check' => null,
            'health_data' => null,
            'audit_log' => null
        ];
    }
}
