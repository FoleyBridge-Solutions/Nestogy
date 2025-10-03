<?php

namespace Database\Factories;

use App\Models\InAppNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class InAppNotificationFactory extends Factory
{
    protected $model = InAppNotification::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
