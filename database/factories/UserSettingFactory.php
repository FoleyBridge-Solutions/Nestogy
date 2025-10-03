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
            'role' => null,
            'remember_me_token' => null,
            'force_mfa' => null,
            'records_per_page' => null,
            'dashboard_financial_enable' => null,
            'dashboard_technical_enable' => null,
            'theme' => null,
            'preferences' => null
        ];
    }
}
