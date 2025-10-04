<?php

namespace Database\Factories;

use App\Models\UsageAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageAlertFactory extends Factory
{
    protected $model = UsageAlert::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'alert_created_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
