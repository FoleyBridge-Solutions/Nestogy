<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'user_id' => \App\Models\User::factory(),
            'hours' => $this->faker->randomFloat(2, 0.25, 8),
            'billable' => $this->faker->boolean(),
            'rate' => $this->faker->optional()->randomFloat(2, 0, 1000),
            'description' => $this->faker->optional()->sentence,
            'date' => $this->faker->date(),
            'start_time' => $this->faker->optional()->time('H:i:s'),
            'end_time' => $this->faker->optional()->time('H:i:s')
        ];
    }
}
