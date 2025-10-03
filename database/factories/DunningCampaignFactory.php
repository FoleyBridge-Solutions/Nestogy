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
            'company_id' => 1,
        ];
    }
}
