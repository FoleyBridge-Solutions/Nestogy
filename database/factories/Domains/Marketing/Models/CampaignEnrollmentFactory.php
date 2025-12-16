<?php

namespace Database\Factories\Domains\Marketing\Models;

use App\Domains\Marketing\Models\CampaignEnrollment;
use App\Domains\Marketing\Models\MarketingCampaign;
use App\Domains\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignEnrollmentFactory extends Factory
{
    protected $model = CampaignEnrollment::class;

    public function definition(): array
    {
        return [
            'campaign_id' => MarketingCampaign::factory(),
            'lead_id' => Lead::factory(),
            'contact_id' => null,
            'status' => 'active',
            'current_step' => 1,
            'enrolled_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'last_activity_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'next_send_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'completed_at' => null,
            'emails_sent' => 0,
            'emails_opened' => 0,
            'emails_clicked' => 0,
            'converted' => false,
            'converted_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'converted' => true,
            'converted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unsubscribed',
        ]);
    }
}
