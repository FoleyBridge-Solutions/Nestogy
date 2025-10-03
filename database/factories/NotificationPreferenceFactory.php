<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
