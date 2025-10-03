<?php

namespace Database\Factories;

use App\Models\TicketRating;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketRatingFactory extends Factory
{
    protected $model = TicketRating::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'rating' => $this->faker->optional()->word,
            'feedback' => $this->faker->optional()->word,
            'rating_type' => $this->faker->numberBetween(1, 5)
        ];
    }
}
