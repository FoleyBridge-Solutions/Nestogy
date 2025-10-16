<?php

namespace Database\Factories;

use App\Domains\Financial\Models\ClientCredit;
use App\Domains\Financial\Models\ClientCreditApplication;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientCreditApplicationFactory extends Factory
{
    protected $model = ClientCreditApplication::class;

    public function definition(): array
    {
        $credit = ClientCredit::factory()->create();
        $invoice = Invoice::factory()->create([
            'company_id' => $credit->company_id,
            'client_id' => $credit->client_id,
        ]);

        return [
            'company_id' => $credit->company_id,
            'client_credit_id' => $credit->id,
            'applicable_type' => Invoice::class,
            'applicable_id' => $invoice->id,
            'amount' => fake()->randomFloat(2, 10, min($credit->available_amount, 500)),
            'applied_date' => now()->toDateString(),
            'applied_by' => User::factory(),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function unapplied(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'unapplied_at' => now(),
            'unapplied_by' => User::factory(),
            'unapplication_reason' => fake()->sentence(),
        ]);
    }
}
