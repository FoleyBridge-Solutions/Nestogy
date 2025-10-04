<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'product_id' => \App\Models\Product::factory(),
            'service_type' => $this->faker->randomElement(['consulting', 'support', 'maintenance', 'development', 'training', 'implementation', 'custom']),
            'estimated_hours' => $this->faker->optional()->randomFloat(2, 1, 999.99),
            'sla_days' => $this->faker->optional()->randomNumber(),
            'response_time_hours' => $this->faker->optional()->randomNumber(),
            'resolution_time_hours' => $this->faker->optional()->randomNumber(),
            'deliverables' => $this->faker->optional()->randomNumber(),
            'dependencies' => $this->faker->optional()->randomNumber(),
            'requirements' => $this->faker->optional()->sentence,
            'requires_scheduling' => $this->faker->boolean(),
            'min_notice_hours' => $this->faker->numberBetween(1, 48),
            'duration_minutes' => $this->faker->optional()->randomNumber(),
            'availability_schedule' => $this->faker->optional()->randomNumber(),
            'required_skills' => json_encode([]),
            'required_resources' => json_encode([]),
            'has_setup_fee' => $this->faker->boolean(),
            'setup_fee' => $this->faker->optional()->randomFloat(2, 0, 1000),
            'has_cancellation_fee' => $this->faker->boolean(),
            'cancellation_fee' => $this->faker->optional()->randomFloat(2, 0, 1000),
            'cancellation_notice_hours' => $this->faker->numberBetween(24, 168),
            'minimum_commitment_months' => $this->faker->optional()->randomNumber(),
            'maximum_duration_months' => $this->faker->optional()->randomNumber(),
            'auto_renew' => $this->faker->boolean(),
            'renewal_notice_days' => $this->faker->numberBetween(7, 90)
        ];
    }
}
