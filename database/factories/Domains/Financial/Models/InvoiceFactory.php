<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Financial\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-6 months', 'now');
        $dueDate = (clone $date)->modify('+30 days');

        $amount = $this->faker->randomFloat(2, 100, 10000);
        $discountAmount = $this->faker->optional(0.3)->randomFloat(2, 0, $amount * 0.2);

        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'prefix' => 'INV',
            'number' => $this->faker->unique()->numberBetween(1000, 9999),
            'scope' => $this->faker->optional()->word(),
            'date' => $date,
            'due_date' => $dueDate,
            'status' => $this->faker->randomElement(['draft', 'sent', 'viewed', 'paid', 'overdue', 'cancelled']),
            'discount_amount' => $discountAmount ?? 0,
            'amount' => $amount,
            'currency_code' => 'USD',
            'note' => $this->faker->optional()->paragraph(),
            'url_key' => $this->faker->uuid(),
            'category_id' => \App\Domains\Financial\Models\Category::factory(),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }

    /**
     * Indicate that the invoice is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the invoice has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    /**
     * Indicate that the invoice has been viewed.
     */
    public function viewed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'viewed',
            ];
        });
    }

    /**
     * Indicate that the invoice has been paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
            ];
        });
    }

    /**
     * Indicate that the invoice is overdue.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $dueDate = $this->faker->dateTimeBetween('-60 days', '-1 day');
            $invoiceDate = (clone $dueDate)->modify('-30 days');

            return [
                'status' => 'overdue',
                'date' => $invoiceDate,
                'due_date' => $dueDate,
            ];
        });
    }

    /**
     * Indicate that the invoice is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Set a specific total amount.
     */
    public function withTotal(float $total): static
    {
        return $this->state(function (array $attributes) use ($total) {
            return [
                'amount' => $total,
            ];
        });
    }

    /**
     * Set specific currency.
     */
    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency_code' => $currency,
        ]);
    }

    /**
     * Create invoice for a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'company_id' => $client->company_id,
        ]);
    }

    /**
     * Create invoice for a specific company.
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    /**
     * Create invoice with discount.
     */
    public function withDiscount(float $discountAmount): static
    {
        return $this->state(function (array $attributes) use ($discountAmount) {
            return [
                'discount_amount' => $discountAmount,
            ];
        });
    }

    /**
     * Create recent invoice (last 30 days).
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            $date = $this->faker->dateTimeBetween('-30 days', 'now');
            $dueDate = (clone $date)->modify('+30 days');

            return [
            'date' => $date,
                'due_date' => $dueDate,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        });
    }
}
