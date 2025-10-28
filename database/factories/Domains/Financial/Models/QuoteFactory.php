<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-1 year', 'now');
        
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'category_id' => \App\Domains\Financial\Models\Category::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'prefix' => $this->faker->optional()->word,
            'number' => $this->faker->numberBetween(1, 100),
            'scope' => $this->faker->optional()->word,
            'status' => 'Draft',
            'approval_status' => 'not_required',
            'date' => $date,
            'expire' => $this->faker->dateTimeBetween($date, '+1 year'),
            'discount_amount' => 0,
            'amount' => 0,
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
