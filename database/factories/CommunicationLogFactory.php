<?php

namespace Database\Factories;

use App\Models\CommunicationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunicationLogFactory extends Factory
{
    protected $model = CommunicationLog::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'client_id' => \App\Models\Client::factory(),
            'user_id' => \App\Models\User::factory(),
            'contact_id' => null,
            'type' => $this->faker->randomElement(['inbound', 'outbound', 'internal', 'follow_up', 'meeting']),
            'channel' => $this->faker->randomElement(['phone', 'email', 'sms', 'chat', 'in_person', 'video_call']),
            'contact_name' => $this->faker->optional()->name,
            'contact_email' => $this->faker->optional()->safeEmail,
            'contact_phone' => $this->faker->optional()->phoneNumber,
            'subject' => $this->faker->sentence,
            'notes' => $this->faker->paragraph,
            'follow_up_required' => $this->faker->boolean(30),
            'follow_up_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }
}
