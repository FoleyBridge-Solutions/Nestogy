<?php

namespace Database\Factories;

use App\Models\UsageAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageAlertFactory extends Factory
{
    protected $model = UsageAlert::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
