<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\CreditNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteFactory extends Factory
{
    protected $model = CreditNote::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'created_by' => \App\Domains\Core\Models\User::factory(),
            'number' => $this->faker->unique()->numerify('CN-######'),
            'type' => 'manual',
            'status' => 'draft',
            'credit_date' => $this->faker->date(),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
