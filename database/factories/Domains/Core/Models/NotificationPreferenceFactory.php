<?php

namespace Database\Factories\Domains\Core\Models;

use App\Domains\Core\Models\NotificationPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Domains\Core\Models\User::factory(),
        ];
    }
}
