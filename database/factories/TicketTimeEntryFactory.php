<?php

namespace Database\Factories;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketTimeEntryFactory extends Factory
{
    protected $model = TicketTimeEntry::class;

    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $endedAt = $this->faker->optional(0.8)->dateTimeBetween($startedAt, 'now');
        
        $hoursWorked = $endedAt 
            ? $this->faker->randomFloat(2, 0.25, 8.0)
            : null;

        return [
            'company_id' => Company::factory(),
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'work_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'hours_worked' => $hoursWorked,
            'description' => $this->faker->sentence(),
            'work_performed' => $this->faker->optional()->paragraph(),
            'billable' => $this->faker->boolean(80),
            'hourly_rate' => $this->faker->randomFloat(2, 75, 200),
            'amount' => $hoursWorked ? $hoursWorked * $this->faker->randomFloat(2, 75, 200) : null,
            'entry_type' => $this->faker->randomElement(['manual', 'timer']),
            'work_type' => $this->faker->randomElement(['general_support', 'troubleshooting', 'installation', 'maintenance']),
        ];
    }

    public function timer(): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_type' => 'timer',
            'ended_at' => null,
            'hours_worked' => null,
            'amount' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $attributes['started_at'];
            $endedAt = $this->faker->dateTimeBetween($startedAt, 'now');
            $hoursWorked = $this->faker->randomFloat(2, 0.25, 8.0);
            
            return [
                'ended_at' => $endedAt,
                'hours_worked' => $hoursWorked,
                'amount' => $hoursWorked * ($attributes['hourly_rate'] ?? 100),
            ];
        });
    }

    public function billable(): static
    {
        return $this->state(fn (array $attributes) => [
            'billable' => true,
        ]);
    }

    public function nonBillable(): static
    {
        return $this->state(fn (array $attributes) => [
            'billable' => false,
            'amount' => null,
        ]);
    }
}
