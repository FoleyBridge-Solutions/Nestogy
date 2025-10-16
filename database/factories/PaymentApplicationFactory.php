<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Payment;
use App\Domains\Financial\Models\PaymentApplication;
use App\Domains\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentApplicationFactory extends Factory
{
    protected $model = PaymentApplication::class;

    public function definition(): array
    {
        $payment = Payment::factory()->create();
        $invoice = Invoice::factory()->create([
            'company_id' => $payment->company_id,
            'client_id' => $payment->client_id,
        ]);

        return [
            'company_id' => $payment->company_id,
            'payment_id' => $payment->id,
            'applicable_type' => Invoice::class,
            'applicable_id' => $invoice->id,
            'amount' => fake()->randomFloat(2, 10, 1000),
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
