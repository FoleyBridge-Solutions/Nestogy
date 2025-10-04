<?php

namespace Database\Factories;

use App\Models\CreditNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteFactory extends Factory
{
    protected $model = CreditNote::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'number' => $this->faker->unique()->numerify('CN-######'),
            'created_by' => \App\Models\User::factory(),
            'credit_date' => $this->faker->date(),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
