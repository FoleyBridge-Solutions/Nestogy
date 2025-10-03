<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'opening_balance' => null,
            'currency_code' => null,
            'notes' => null,
            'type' => null
        ];
    }
}
