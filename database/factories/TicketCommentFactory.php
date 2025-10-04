<?php

namespace Database\Factories;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketCommentFactory extends Factory
{
    protected $model = TicketComment::class;

    public function definition(): array
    {
        return ['ticket_id' => Ticket::factory(),
            'company_id' => \App\Models\Company::factory(),
            'content' => $this->faker->paragraph(),
            'visibility' => 'public',
            'source' => 'manual',
            'author_type' => 'user',
            'author_id' => User::factory(),
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => TicketComment::VISIBILITY_PUBLIC,
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => TicketComment::VISIBILITY_INTERNAL,
        ]);
    }

    public function fromCustomer(int $contactId): static
    {
        return $this->state(fn (array $attributes) => [
            'author_type' => TicketComment::AUTHOR_CUSTOMER,
            'author_id' => $contactId,
        ]);
    }

    public function fromStaff(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'author_type' => TicketComment::AUTHOR_USER,
            'author_id' => $userId,
        ]);
    }

    public function fromSystem(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_type' => TicketComment::AUTHOR_SYSTEM,
            'author_id' => null,
            'source' => TicketComment::SOURCE_SYSTEM,
        ]);
    }
}
