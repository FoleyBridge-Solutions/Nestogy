<?php

namespace Database\Factories\Domains\HR;

use App\Domains\HR\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Morning Shift', 'Evening Shift', 'Night Shift', 'Day Shift']),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
            'break_minutes' => $this->faker->randomElement([0, 15, 30, 45, 60]),
            'days_of_week' => $this->faker->randomElement([
                [1, 2, 3, 4, 5],
                [0, 6],
                [1, 2, 3, 4, 5, 6],
            ]),
            'is_active' => true,
            'color' => $this->faker->hexColor(),
            'description' => $this->faker->sentence(),
        ];
    }
}
