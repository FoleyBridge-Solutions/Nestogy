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
            'hours' => $this->faker->optional()->word,
            'billable' => $this->faker->optional()->word,
            'rate' => $this->faker->optional()->word,
            'description' => $this->faker->optional()->sentence,
            'date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'start_time' => $this->faker->optional()->word,
            'end_time' => $this->faker->optional()->word
        ];
    }
}
