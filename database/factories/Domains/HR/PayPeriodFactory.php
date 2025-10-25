<?php

namespace Database\Factories\Domains\HR;

use App\Domains\HR\Models\PayPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayPeriodFactory extends Factory
{
    protected $model = PayPeriod::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $endDate = (clone $startDate)->modify('+13 days');

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'frequency' => $this->faker->randomElement([
                PayPeriod::FREQUENCY_WEEKLY,
                PayPeriod::FREQUENCY_BIWEEKLY,
                PayPeriod::FREQUENCY_SEMIMONTHLY,
                PayPeriod::FREQUENCY_MONTHLY,
            ]),
            'status' => $this->faker->randomElement([
                PayPeriod::STATUS_OPEN,
                PayPeriod::STATUS_APPROVED,
                PayPeriod::STATUS_PAID,
            ]),
        ];
    }
}
