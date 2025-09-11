<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Client;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        
        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'name' => $firstName . ' ' . $lastName,
            'title' => $this->faker->jobTitle(),
            'email' => strtolower($firstName . '.' . $lastName) . '@' . $this->faker->domainName(),
            'phone' => $this->faker->phoneNumber(),
            'extension' => $this->faker->optional()->numberBetween(100, 999),
            'mobile' => $this->faker->optional()->phoneNumber(),
            'department' => $this->faker->randomElement(['IT', 'Finance', 'Operations', 'Executive', 'HR', 'Sales']),
            'primary' => false,
            'important' => false,
            'billing' => false,
            'technical' => false,
            'has_portal_access' => $this->faker->boolean(30),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the contact is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'primary' => true,
        ]);
    }

    /**
     * Indicate that the contact is a billing contact.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing' => true,
            'department' => 'Finance',
        ]);
    }

    /**
     * Indicate that the contact is a technical contact.
     */
    public function technical(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical' => true,
            'department' => 'IT',
            'has_portal_access' => true,
        ]);
    }

    /**
     * Indicate that the contact is important (VIP).
     */
    public function important(): static
    {
        return $this->state(fn (array $attributes) => [
            'important' => true,
        ]);
    }

    /**
     * Create a contact for a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'company_id' => $client->company_id,
        ]);
    }
}