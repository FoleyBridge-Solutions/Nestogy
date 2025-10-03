<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'hours' => null,
            'billable' => null,
            'rate' => null,
            'description' => $this->faker->sentence,
            'date' => null,
            'start_time' => null,
            'end_time' => null
        ];
    }
}
