<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isCompany = $this->faker->boolean(70); // 70% chance of being a company
        
        return [
            'company_id' => Company::factory(),
            'name' => $isCompany ? $this->faker->company() : $this->faker->name(),
            'company_name' => $isCompany ? $this->faker->company() . ' Inc.' : null,
            'type' => $isCompany ? 'company' : 'individual',
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->optional()->url(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip_code' => $this->faker->postcode(),
            'country' => 'United States',
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'notes' => $this->faker->optional()->paragraph(),
            'hourly_rate' => $this->faker->randomElement([125, 150, 175, 200, 225]),
            'billing_contact' => $this->faker->name,
            'technical_contact' => $this->faker->name,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the client is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the client is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the client is a prospect.
     */
    public function prospect(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'lead' => true,
        ]);
    }





    /**
     * Create a client for a specific company.
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }
}