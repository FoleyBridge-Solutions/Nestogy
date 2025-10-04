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
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'application_number' => $this->faker->unique()->numerify('APP-######'),
            'applied_by' => \App\Models\User::factory(),
        ];
    }
}
