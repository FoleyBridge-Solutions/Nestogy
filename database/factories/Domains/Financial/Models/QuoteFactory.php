<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'category_id' => \App\Domains\Financial\Models\Category::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'prefix' => $this->faker->optional()->word,
            'number' => $this->faker->numberBetween(1, 100),
            'scope' => $this->faker->optional()->word,
            'status' => $this->faker->randomElement(['Draft', 'Sent', 'Viewed', 'Accepted', 'Declined', 'Expired']),
            'approval_status' => $this->faker->randomElement(['pending', 'manager_approved', 'executive_approved', 'rejected', 'not_required']),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expire' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'discount_amount' => $this->faker->randomFloat(2, 0, 10000),
            'amount' => $this->faker->randomFloat(2, 0, 10000),
            'currency_code' => 'USD',
            'note' => $this->faker->optional()->sentence,
            'url_key' => bin2hex(random_bytes(16)),
            'created_by' => \App\Domains\Core\Models\User::factory(),
        ];
    }
    
    /**
     * Indicate that the quote is fully approved (executive_approved).
     */
    public function fullyApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'executive_approved',
        ]);
    }
    
    /**
     * Indicate that the quote is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'executive_approved',
        ]);
    }
    
    /**
     * Indicate that the quote does not require approval.
     */
    public function noApprovalRequired(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'not_required',
        ]);
    }
}
