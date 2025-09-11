<?php

namespace Database\Factories;

use App\Models\TicketReply;
use App\Models\User;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketReply>
 */
class TicketReplyFactory extends Factory
{
    protected $model = TicketReply::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [TicketReply::TYPE_PUBLIC, TicketReply::TYPE_PRIVATE, TicketReply::TYPE_INTERNAL];
        $sentiments = [
            TicketReply::SENTIMENT_POSITIVE,
            TicketReply::SENTIMENT_WEAK_POSITIVE,
            TicketReply::SENTIMENT_NEUTRAL,
            TicketReply::SENTIMENT_WEAK_NEGATIVE,
            TicketReply::SENTIMENT_NEGATIVE
        ];

        $hasTimeTracked = $this->faker->boolean(30); // 30% chance of time tracking
        $hasSentiment = $this->faker->boolean(60); // 60% chance of sentiment analysis

        return [
            'company_id' => 1,
            'reply' => $this->faker->paragraphs($this->faker->numberBetween(1, 3), true),
            'type' => $this->faker->randomElement($types),
            'time_worked' => $hasTimeTracked ? sprintf('%02d:%02d:00', 
                $this->faker->numberBetween(0, 4), 
                $this->faker->numberBetween(0, 59)
            ) : null,
            'replied_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'ticket_id' => Ticket::inRandomOrder()->first()?->id ?? Ticket::factory(),
            'sentiment_score' => $hasSentiment ? $this->faker->randomFloat(2, -1, 1) : null,
            'sentiment_label' => $hasSentiment ? $this->faker->randomElement($sentiments) : null,
            'sentiment_analyzed_at' => $hasSentiment ? $this->faker->dateTimeThisMonth() : null,
            'sentiment_confidence' => $hasSentiment ? $this->faker->randomFloat(2, 0.5, 1.0) : null,
        ];
    }

    /**
     * Indicate that the reply is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketReply::TYPE_PUBLIC,
        ]);
    }

    /**
     * Indicate that the reply is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketReply::TYPE_PRIVATE,
        ]);
    }

    /**
     * Indicate that the reply is internal.
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketReply::TYPE_INTERNAL,
        ]);
    }

    /**
     * Indicate that the reply has time tracked.
     */
    public function withTimeTracked(int $hours = null, int $minutes = null): static
    {
        return $this->state(function (array $attributes) use ($hours, $minutes) {
            $h = $hours ?? $this->faker->numberBetween(0, 4);
            $m = $minutes ?? $this->faker->numberBetween(0, 59);
            return [
                'time_worked' => sprintf('%02d:%02d:00', $h, $m),
            ];
        });
    }

    /**
     * Indicate that the reply has positive sentiment.
     */
    public function positiveSentiment(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment_score' => $this->faker->randomFloat(2, 0.3, 1.0),
            'sentiment_label' => $this->faker->randomElement([
                TicketReply::SENTIMENT_POSITIVE,
                TicketReply::SENTIMENT_WEAK_POSITIVE
            ]),
            'sentiment_analyzed_at' => $this->faker->dateTimeThisMonth(),
            'sentiment_confidence' => $this->faker->randomFloat(2, 0.7, 1.0),
        ]);
    }

    /**
     * Indicate that the reply has negative sentiment.
     */
    public function negativeSentiment(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment_score' => $this->faker->randomFloat(2, -1.0, -0.3),
            'sentiment_label' => $this->faker->randomElement([
                TicketReply::SENTIMENT_NEGATIVE,
                TicketReply::SENTIMENT_WEAK_NEGATIVE
            ]),
            'sentiment_analyzed_at' => $this->faker->dateTimeThisMonth(),
            'sentiment_confidence' => $this->faker->randomFloat(2, 0.7, 1.0),
        ]);
    }

    /**
     * Indicate that the reply has neutral sentiment.
     */
    public function neutralSentiment(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment_score' => $this->faker->randomFloat(2, -0.3, 0.3),
            'sentiment_label' => TicketReply::SENTIMENT_NEUTRAL,
            'sentiment_analyzed_at' => $this->faker->dateTimeThisMonth(),
            'sentiment_confidence' => $this->faker->randomFloat(2, 0.5, 0.8),
        ]);
    }

    /**
     * Indicate that the reply has no sentiment analysis.
     */
    public function withoutSentiment(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment_score' => null,
            'sentiment_label' => null,
            'sentiment_analyzed_at' => null,
            'sentiment_confidence' => null,
        ]);
    }
}