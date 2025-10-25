<?php

namespace Database\Factories\Domains\Ticket\Models;

use App\Domains\Ticket\Models\TicketRating;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketRatingFactory extends Factory
{
    protected $model = TicketRating::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'ticket_id' => \App\Domains\Ticket\Models\Ticket::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'feedback' => $this->faker->optional()->sentence,
            'rating_type' => $this->faker->randomElement(['satisfaction', 'quality', 'responsiveness'])
        ];
    }
}
