<?php

namespace Database\Factories;

use App\Models\DunningSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

class DunningSequenceFactory extends Factory
{
    protected $model = DunningSequence::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'campaign_id' => \App\Models\DunningCampaign::factory(),
            'name' => $this->faker->words(3, true),
            'step_number' => 1,
            'action_type' => $this->faker->randomElement(['email', 'sms', 'phone_call', 'letter', 'service_suspension', 'legal_notice']),
            'created_by' => \App\Models\User::factory(),
            'updated_by' => \App\Models\User::factory(),
        ];
    }
}
