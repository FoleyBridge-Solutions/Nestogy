<?php

namespace Database\Factories\Domains\Client\Models;

use App\Domains\Client\Models\CommunicationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunicationLogFactory extends Factory
{
    protected $model = CommunicationLog::class;

    public function definition(): array
    {
        return [
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'user_id' => \App\Domains\Core\Models\User::factory(),
            'type' => $this->faker->randomElement(['email', 'phone', 'meeting']),
            'channel' => $this->faker->randomElement(['email', 'phone', 'sms', 'meeting']),
            'subject' => $this->faker->sentence,
            'notes' => $this->faker->paragraph,
            'follow_up_required' => false,
        ];
    }
}
