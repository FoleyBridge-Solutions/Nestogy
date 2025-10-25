<?php

namespace Database\Factories\Domains\Company\Models;

use App\Domains\Company\Models\AccountHold;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountHoldFactory extends Factory
{
    protected $model = AccountHold::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->word(),
        ];
    }
}
