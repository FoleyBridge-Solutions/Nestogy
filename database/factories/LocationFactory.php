<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'name' => $this->faker->company().' - '.$this->faker->randomElement(['Main Office', 'Branch', 'Warehouse', 'Data Center']),
            'description' => $this->faker->optional()->sentence(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip' => $this->faker->postcode(),
            'country' => 'United States',
            'phone' => $this->faker->phoneNumber(),
            'hours' => 'Mon-Fri: 8:00 AM - 5:00 PM',
            'primary' => false,
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the location is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'primary' => true,
        ]);
    }

    /**
     * Create a location for a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'company_id' => $client->company_id,
        ]);
    }
}
