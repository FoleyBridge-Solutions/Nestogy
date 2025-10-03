<?php

namespace Database\Factories;

use App\Models\DunningSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

class DunningSequenceFactory extends Factory
{
    protected $model = DunningSequence::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
