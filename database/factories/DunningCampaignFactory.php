<?php

namespace Database\Factories;

use App\Models\DunningCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class DunningCampaignFactory extends Factory
{
    protected $model = DunningCampaign::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'status' => 'active',
            'created_by' => \App\Models\User::factory(),
            'updated_by' => \App\Models\User::factory(),
        ];
    }
}
