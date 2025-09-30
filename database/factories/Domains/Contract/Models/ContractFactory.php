<?php

namespace Database\Factories\Domains\Contract\Models;

use App\Domains\Contract\Models\Contract;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Contract\Models\Contract>
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
        $contractTypes = ['managed_services', 'support', 'project', 'software', 'hardware'];

        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'contract_number' => 'CNT-'.$this->faker->unique()->numberBetween(1000, 9999),
            'title' => $this->faker->catchPhrase().' Agreement',
            'description' => $this->faker->paragraphs(2, true),
            'contract_type' => $this->faker->randomElement($contractTypes),
            'status' => $this->faker->randomElement(['draft', 'active', 'expired', 'terminated']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_value' => $this->faker->randomFloat(2, 5000, 100000),
            'currency_code' => 'USD',
            'auto_renew' => $this->faker->boolean(60),
            'renewal_notice_days' => $this->faker->randomElement([30, 60, 90]),
            'payment_terms' => $this->faker->randomElement(['Net 15', 'Net 30', 'Net 45']),
            'created_by' => User::factory(),
            'signed_date' => $this->faker->optional()->dateTimeBetween($startDate, 'now'),
            'signature_status' => $this->faker->randomElement(['pending', 'signed', null]),
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
            'contract_type' => 'managed_services',
            'title' => 'Managed Services Agreement',
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
