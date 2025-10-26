<?php

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\UsageAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageAlertFactory extends Factory
{
    protected $model = UsageAlert::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'alert_name' => $this->faker->words(3, true),
            'alert_code' => 'ALERT-'.strtoupper(uniqid()),
            'alert_type' => 'threshold',
            'usage_type' => 'voice',
            'threshold_type' => 'percentage',
            'threshold_value' => 80.0,
            'threshold_unit' => 'percent',
            'is_active' => true,
            'alert_status' => 'normal',
        ];
    }
}
