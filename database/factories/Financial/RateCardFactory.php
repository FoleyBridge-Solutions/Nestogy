<?php

namespace Database\Factories\Financial;

use App\Domains\Financial\Models\RateCard;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class RateCardFactory extends Factory
{
    protected $model = RateCard::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'client_id' => Client::factory(),
            'name' => $this->faker->words(3, true).' Rate',
            'description' => $this->faker->optional()->sentence(),
            'service_type' => $this->faker->randomElement([
                RateCard::SERVICE_TYPE_STANDARD,
                RateCard::SERVICE_TYPE_AFTER_HOURS,
                RateCard::SERVICE_TYPE_EMERGENCY,
            ]),
            'hourly_rate' => $this->faker->randomFloat(2, 50, 250),
            'effective_from' => now()->subDays($this->faker->numberBetween(0, 30)),
            'effective_to' => null,
            'is_default' => false,
            'is_active' => true,
            'applies_to_all_services' => false,
            'minimum_hours' => $this->faker->optional()->randomFloat(2, 0.5, 2),
            'rounding_increment' => $this->faker->randomElement([15, 30, 60]),
            'rounding_method' => $this->faker->randomElement([
                RateCard::ROUNDING_UP,
                RateCard::ROUNDING_NEAREST,
            ]),
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => RateCard::SERVICE_TYPE_STANDARD,
        ]);
    }

    public function afterHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => RateCard::SERVICE_TYPE_AFTER_HOURS,
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => RateCard::SERVICE_TYPE_EMERGENCY,
        ]);
    }
}
