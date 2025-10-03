<?php

namespace Database\Factories;

use App\Models\AccountHold;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountHoldFactory extends Factory
{
    protected $model = AccountHold::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
