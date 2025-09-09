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
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->optional()->url(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip' => $this->faker->postcode(),
            'country' => 'United States',
            'status' => $this->faker->randomElement(['active', 'inactive', 'prospect']),
            'notes' => $this->faker->optional()->paragraph(),
            'tax_id' => $this->faker->optional()->numerify('##-#######'),
            'billing_address' => $this->faker->optional()->streetAddress(),
            'billing_city' => $this->faker->optional()->city(),
            'billing_state' => $this->faker->optional()->stateAbbr(),
            'billing_zip' => $this->faker->optional()->postcode(),
            'billing_country' => $this->faker->optional()->country(),
            'payment_terms' => $this->faker->randomElement([15, 30, 45, 60]),
            'credit_limit' => $this->faker->optional()->randomFloat(2, 1000, 50000),
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
            'status' => 'prospect',
        ]);
    }

    /**
     * Set specific payment terms.
     */
    public function paymentTerms(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_terms' => $days,
        ]);
    }

    /**
     * Set a credit limit.
     */
    public function withCreditLimit(float $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => $limit,
        ]);
    }

    /**
     * Create a client with billing address same as main address.
     */
    public function withSameBillingAddress(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'billing_address' => $attributes['address'],
                'billing_city' => $attributes['city'],
                'billing_state' => $attributes['state'],
                'billing_zip' => $attributes['zip'],
                'billing_country' => $attributes['country'],
            ];
        });
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