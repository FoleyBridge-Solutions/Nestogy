<?php

namespace Database\Factories;

use App\Domains\Contract\Models\Contract;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 years');
        $termMonths = $startDate->diff($endDate)->m + ($startDate->diff($endDate)->y * 12);
        $contractTypes = ['managed_services', 'support', 'project', 'software', 'hardware', 'voip', 'cloud'];
        
        return [
            'company_id' => Company::first()?->id ?? 1,
            'client_id' => Client::inRandomOrder()->first()?->id ?? Client::factory(),
            'contract_number' => 'CNT-' . $this->faker->unique()->numberBetween(1000, 9999),
            'contract_type' => $this->faker->randomElement($contractTypes),
            'status' => $this->faker->randomElement(['draft', 'active', 'expired', 'terminated']),
            'signature_status' => $this->faker->randomElement(['pending', 'signed', 'expired', null]),
            'title' => $this->faker->catchPhrase() . ' Agreement',
            'description' => $this->faker->paragraphs(2, true),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'term_months' => $termMonths,
            'renewal_type' => $this->faker->randomElement(['auto', 'manual', 'none']),
            'renewal_notice_days' => $this->faker->randomElement([30, 60, 90]),
            'auto_renewal' => $this->faker->boolean(60),
            'contract_value' => $this->faker->randomFloat(2, 5000, 100000),
            'currency_code' => 'USD',
            'payment_terms' => $this->faker->randomElement(['Net 15', 'Net 30', 'Net 45', 'Net 60']),
            'pricing_structure' => [
                'type' => $this->faker->randomElement(['fixed', 'hourly', 'tiered']),
                'rate' => $this->faker->randomFloat(2, 100, 500),
            ],
            'sla_terms' => [
                'response_time' => $this->faker->randomElement(['1 hour', '4 hours', '24 hours']),
                'resolution_time' => $this->faker->randomElement(['4 hours', '24 hours', '48 hours']),
                'uptime_guarantee' => $this->faker->randomElement(['99.9%', '99.5%', '99%']),
            ],
            'terms_and_conditions' => $this->faker->paragraphs(3, true),
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'signed_at' => $this->faker->optional()->dateTimeBetween($startDate, 'now'),
        ];
    }

    /**
     * Indicate that the contract is active.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
                'signed_date' => $this->faker->dateTimeBetween($attributes['start_date'], 'now'),
            ];
        });
    }

    /**
     * Indicate that the contract is a managed services agreement.
     */
    public function managedServices(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'managed_services',
            'title' => 'Managed Services Agreement',
            'billing_frequency' => 'monthly',
            'auto_renew' => true,
        ]);
    }

    /**
     * Create a contract for a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'company_id' => $client->company_id,
        ]);
    }
}