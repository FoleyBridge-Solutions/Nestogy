<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethods = [
            Payment::METHOD_CASH,
            Payment::METHOD_CHECK,
            Payment::METHOD_CREDIT_CARD,
            Payment::METHOD_BANK_TRANSFER,
            Payment::METHOD_PAYPAL,
            Payment::METHOD_OTHER,
        ];

        $gateways = ['stripe', 'paypal', 'square', 'manual'];
        $gateway = $this->faker->randomElement($gateways);

        return [
            'company_id' => 1,
            'client_id' => Client::inRandomOrder()->first()?->id ?? Client::factory(),
            'invoice_id' => Invoice::inRandomOrder()->first()?->id ?? Invoice::factory(),
            'processed_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'payment_method' => $this->faker->randomElement($paymentMethods),
            'payment_reference' => $this->faker->optional(0.7)->bothify('PMT-########'),
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'currency' => 'USD',
            'gateway' => $gateway,
            'gateway_transaction_id' => $gateway ? $this->faker->uuid() : null,
            'gateway_fee' => $gateway ? $this->faker->randomFloat(2, 0, 50) : null,
            'status' => $this->faker->randomElement(['completed', 'pending', 'failed']),
            'payment_date' => $this->faker->dateTimeThisYear(),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'metadata' => $this->faker->optional(0.2)->passthrough([
                'customer_email' => $this->faker->email(),
                'ip_address' => $this->faker->ipv4(),
            ]),
        ];
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the payment is by credit card.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_CREDIT_CARD,
            'gateway' => 'stripe',
            'gateway_transaction_id' => $this->faker->uuid(),
            'gateway_fee' => $this->faker->randomFloat(2, 1, 50),
        ]);
    }

    /**
     * Indicate that the payment is by bank transfer.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'gateway' => 'manual',
            'gateway_transaction_id' => null,
            'gateway_fee' => null,
        ]);
    }

    /**
     * Indicate that the payment is by PayPal.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_PAYPAL,
            'gateway' => 'paypal',
            'gateway_transaction_id' => $this->faker->uuid(),
            'gateway_fee' => $this->faker->randomFloat(2, 1, 30),
        ]);
    }

    /**
     * Indicate that the payment is by cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_CASH,
            'gateway' => 'manual',
            'gateway_transaction_id' => null,
            'gateway_fee' => null,
        ]);
    }

    /**
     * Indicate that the payment is by check.
     */
    public function check(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_CHECK,
            'payment_reference' => 'CHK-'.$this->faker->numberBetween(1000, 9999),
            'gateway' => 'manual',
            'gateway_transaction_id' => null,
            'gateway_fee' => null,
        ]);
    }

    /**
     * Indicate that the payment has been refunded.
     */
    public function refunded(?float $refundAmount = null, ?string $reason = null): static
    {
        return $this->state(function (array $attributes) use ($refundAmount, $reason) {
            $amount = $refundAmount ?? $attributes['amount'];

            return [
                'refund_amount' => min($amount, $attributes['amount']),
                'refund_reason' => $reason ?? $this->faker->sentence(),
                'refunded_at' => $this->faker->dateTimeBetween($attributes['payment_date'], 'now'),
            ];
        });
    }

    /**
     * Indicate that the payment has a chargeback.
     */
    public function chargeback(?float $chargebackAmount = null, ?string $reason = null): static
    {
        return $this->state(function (array $attributes) use ($chargebackAmount, $reason) {
            $amount = $chargebackAmount ?? $attributes['amount'];

            return [
                'chargeback_amount' => min($amount, $attributes['amount']),
                'chargeback_reason' => $reason ?? 'Disputed transaction',
                'chargeback_date' => $this->faker->dateTimeBetween($attributes['payment_date'], 'now'),
            ];
        });
    }

    /**
     * Create payment for specific invoice.
     */
    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'company_id' => $invoice->company_id,
            'amount' => $invoice->amount,
            'currency' => $invoice->currency_code,
        ]);
    }

    /**
     * Create payment for specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'company_id' => $client->company_id,
        ]);
    }
}
