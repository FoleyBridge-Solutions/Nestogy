<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['name' => $this->faker->company(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip' => $this->faker->postcode(),
            'country' => 'United States',
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
            'website' => $this->faker->optional()->url(),
            'logo' => null,
            'locale' => 'en_US',
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'CAD']),
            'client_record_id' => null,
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
            'minimum_billing_increment' => 0.25,
        ];
    }

    /**
     * Indicate that the company is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the company is suspended.
     */
    public function suspended(string $reason = 'Payment overdue'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Set a specific currency for the company.
     */
    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }

    /**
     * Create a company in a specific country.
     */
    public function inCountry(string $country): static
    {
        $statesByCountry = [
            'United States' => $this->faker->stateAbbr(),
            'Canada' => $this->faker->randomElement(['AB', 'BC', 'MB', 'NB', 'NL', 'NS', 'ON', 'PE', 'QC', 'SK']),
            'United Kingdom' => $this->faker->randomElement(['England', 'Scotland', 'Wales', 'Northern Ireland']),
            'Australia' => $this->faker->randomElement(['NSW', 'VIC', 'QLD', 'WA', 'SA', 'TAS', 'ACT', 'NT']),
        ];

        return $this->state(fn (array $attributes) => [
            'country' => $country,
            'state' => $statesByCountry[$country] ?? null,
        ]);
    }
}
