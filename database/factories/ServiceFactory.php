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
            'company_id' => 1,
            'service_type' => $this->faker->numberBetween(1, 5),
            'estimated_hours' => $this->faker->optional()->word,
            'sla_days' => $this->faker->optional()->word,
            'response_time_hours' => $this->faker->optional()->word,
            'resolution_time_hours' => $this->faker->optional()->word,
            'deliverables' => $this->faker->optional()->word,
            'dependencies' => $this->faker->optional()->word,
            'requirements' => $this->faker->optional()->word,
            'requires_scheduling' => $this->faker->optional()->word,
            'min_notice_hours' => $this->faker->optional()->word,
            'duration_minutes' => $this->faker->optional()->word,
            'availability_schedule' => $this->faker->optional()->word,
            'required_skills' => $this->faker->optional()->word,
            'required_resources' => $this->faker->optional()->word,
            'has_setup_fee' => $this->faker->optional()->word,
            'setup_fee' => $this->faker->optional()->word,
            'has_cancellation_fee' => $this->faker->optional()->word,
            'cancellation_fee' => $this->faker->optional()->word,
            'cancellation_notice_hours' => $this->faker->optional()->word,
            'minimum_commitment_months' => $this->faker->optional()->word,
            'maximum_duration_months' => $this->faker->optional()->word,
            'auto_renew' => $this->faker->optional()->word,
            'renewal_notice_days' => $this->faker->optional()->word
        ];
    }
}
