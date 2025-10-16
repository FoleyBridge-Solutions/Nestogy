<?php

namespace Database\Factories;

use App\Domains\Financial\Models\CreditNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteFactory extends Factory
{
    protected $model = CreditNote::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'number' => $this->faker->unique()->numerify('CN-######'),
            'created_by' => \App\Domains\Core\Models\User::factory(),
            'credit_date' => $this->faker->date(),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
