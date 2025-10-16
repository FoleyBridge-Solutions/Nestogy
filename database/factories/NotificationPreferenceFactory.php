<?php

namespace Database\Factories;

use App\Domains\Core\Models\NotificationPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'user_id' => \App\Domains\Core\Models\User::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
