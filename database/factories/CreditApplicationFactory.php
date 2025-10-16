<?php

namespace Database\Factories;

use App\Domains\Company\Models\CreditApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditApplicationFactory extends Factory
{
    protected $model = CreditApplication::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'application_number' => $this->faker->unique()->numerify('APP-######'),
            'applied_by' => \App\Domains\Core\Models\User::factory(),
        ];
    }
}
