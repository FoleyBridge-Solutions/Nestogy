<?php

namespace Database\Factories;

use App\Domains\Tax\Models\TaxExemption;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxExemptionFactory extends Factory
{
    protected $model = TaxExemption::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
