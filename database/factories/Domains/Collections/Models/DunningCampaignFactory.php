<?php

namespace Database\Factories\Domains\Collections\Models;

use App\Domains\Collections\Models\DunningCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class DunningCampaignFactory extends Factory
{
    protected $model = DunningCampaign::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'status' => 'active',
            'campaign_type' => 'automatic',
            'created_by' => \App\Domains\Core\Models\User::factory(),
        ];
    }
}
