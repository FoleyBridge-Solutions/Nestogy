<?php

namespace Database\Factories;

use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSettingFactory extends Factory
{
    protected $model = UserSetting::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'role' => $this->faker->optional()->word,
            'remember_me_token' => $this->faker->optional()->word,
            'force_mfa' => $this->faker->optional()->word,
            'records_per_page' => $this->faker->optional()->word,
            'dashboard_financial_enable' => $this->faker->optional()->word,
            'dashboard_technical_enable' => $this->faker->optional()->word,
            'theme' => $this->faker->optional()->word,
            'preferences' => $this->faker->optional()->word
        ];
    }
}
