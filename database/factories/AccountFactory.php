<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'opening_balance' => $this->faker->randomFloat(2, 0, 10000),
            'currency_code' => 'USD',
            'notes' => $this->faker->optional()->sentence,
            'type' => $this->faker->numberBetween(1, 5)
        ];
    }
}
