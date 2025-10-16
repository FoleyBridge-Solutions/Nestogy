<?php

namespace Database\Factories;

use App\Domains\Core\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSettingFactory extends Factory
{
    protected $model = UserSetting::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'user_id' => \App\Domains\Core\Models\User::factory(),
            'role' => 1,
            'remember_me_token' => $this->faker->optional()->randomNumber(),
            'force_mfa' => $this->faker->boolean(),
            'records_per_page' => $this->faker->numberBetween(10, 100),
            'dashboard_financial_enable' => $this->faker->boolean(),
            'dashboard_technical_enable' => $this->faker->boolean(),
            'theme' => $this->faker->randomElement(['light', 'dark', 'auto']),
            'preferences' => json_encode([])
        ];
    }
}
