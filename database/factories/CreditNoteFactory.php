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
            'company_id' => 1,
        ];
    }
}
