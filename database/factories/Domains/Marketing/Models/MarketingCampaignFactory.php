<?php

namespace Database\Factories\Domains\Marketing\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Marketing\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketingCampaignFactory extends Factory
{
    protected $model = MarketingCampaign::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by_user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'type' => $this->faker->randomElement(['email', 'nurture', 'drip', 'event']),
            'status' => 'draft',
            'settings' => null,
            'target_criteria' => null,
            'auto_enroll' => false,
            'start_date' => null,
            'end_date' => null,
            'total_recipients' => 0,
            'total_sent' => 0,
            'total_delivered' => 0,
            'total_opened' => 0,
            'total_clicked' => 0,
            'total_replied' => 0,
            'total_unsubscribed' => 0,
            'total_converted' => 0,
            'total_revenue' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => $this->faker->dateTimeBetween('-3 months', '-1 month'),
            'end_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
