<?php

namespace Database\Factories\Domains\HR;

use App\Domains\HR\Models\EmployeeSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeScheduleFactory extends Factory
{
    protected $model = EmployeeSchedule::class;

    public function definition(): array
    {
        return [
            'shift_id' => \App\Domains\HR\Models\Shift::factory(),
            'scheduled_date' => $this->faker->date(),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
            'status' => $this->faker->randomElement([
                EmployeeSchedule::STATUS_SCHEDULED,
                EmployeeSchedule::STATUS_CONFIRMED,
                EmployeeSchedule::STATUS_MISSED,
                EmployeeSchedule::STATUS_COMPLETED,
            ]),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
