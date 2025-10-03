<?php

namespace Database\Factories;

use App\Models\CreditApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditApplicationFactory extends Factory
{
    protected $model = CreditApplication::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
