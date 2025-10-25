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
            'prefix' => $this->faker->optional()->randomNumber(),
            'number' => $this->faker->numberBetween(1, 100),
            'scope' => $this->faker->optional()->randomNumber(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'approval_status' => $this->faker->randomElement(['pending', 'manager_approved', 'executive_approved', 'rejected', 'not_required']),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expire' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'discount_amount' => $this->faker->randomFloat(2, 0, 10000),
            'amount' => $this->faker->randomFloat(2, 0, 10000),
            'currency_code' => 'USD',
            'note' => $this->faker->optional()->randomNumber(),
            'url_key' => $this->faker->optional()->url,
            'created_by' => \App\Domains\Core\Models\User::factory(),
        ];
    }
}
