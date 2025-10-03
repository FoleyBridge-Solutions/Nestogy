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
            'rating' => null,
            'feedback' => null,
            'rating_type' => null
        ];
    }
}
