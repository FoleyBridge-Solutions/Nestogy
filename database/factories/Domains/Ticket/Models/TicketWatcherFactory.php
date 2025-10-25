<?php

namespace Database\Factories\Domains\Ticket\Models;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketWatcherFactory extends Factory
{
    protected $model = TicketWatcher::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'email' => $this->faker->safeEmail(),
            'added_by' => User::factory(),
            'notification_preferences' => [
                'status_changes' => true,
                'new_comments' => true,
                'assignments' => true,
                'priority_changes' => true,
            ],
            'is_active' => true,
        ];
    }
}
