<?php

namespace Database\Factories;

use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\ClientCredit;
use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Payment;
use App\Domains\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientCreditFactory extends Factory
{
    protected $model = ClientCredit::class;

    public function definition(): array
    {
        $client = Client::factory()->create();
        $amount = fake()->randomFloat(2, 10, 1000);

        return [
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'source_type' => Payment::class,
            'source_id' => Payment::factory(),
            'amount' => $amount,
            'used_amount' => 0,
            'available_amount' => $amount,
            'currency' => 'USD',
            'type' => fake()->randomElement(['overpayment', 'prepayment', 'promotional', 'goodwill']),
            'status' => ClientCredit::STATUS_ACTIVE,
            'credit_date' => now()->toDateString(),
            'expiry_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'reference_number' => 'CR-' . now()->year . '-' . str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'reason' => fake()->sentence(),
            'notes' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
        ];
    }

    public function depleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_amount' => $attributes['amount'],
            'available_amount' => 0,
            'status' => ClientCredit::STATUS_DEPLETED,
            'depleted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientCredit::STATUS_EXPIRED,
            'expiry_date' => now()->subDay(),
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientCredit::STATUS_VOIDED,
            'voided_at' => now(),
        ]);
    }
}
