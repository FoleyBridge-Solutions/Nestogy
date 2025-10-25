<?php

namespace Database\Factories\Domains\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeTimeEntryFactory extends Factory
{
    protected $model = EmployeeTimeEntry::class;

    public function definition(): array
    {
        $clockIn = Carbon::instance($this->faker->dateTimeBetween('-7 days', 'now'));
        $clockOut = $this->faker->boolean(80) ? $clockIn->copy()->addHours(rand(4, 10)) : null;
        
        $totalMinutes = $clockOut ? $clockIn->diffInMinutes($clockOut) : 0;
        $breakMinutes = $this->faker->randomElement([0, 15, 30, 45, 60]);
        $workMinutes = max(0, $totalMinutes - $breakMinutes);

        return [
            'user_id' => User::factory(),
            'company_id' => Company::first()?->id ?? 1,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'clock_in_ip' => $this->faker->ipv4(),
            'clock_out_ip' => $clockOut ? $this->faker->ipv4() : null,
            'clock_in_latitude' => $this->faker->optional()->latitude(),
            'clock_in_longitude' => $this->faker->optional()->longitude(),
            'clock_out_latitude' => $clockOut ? $this->faker->optional()->latitude() : null,
            'clock_out_longitude' => $clockOut ? $this->faker->optional()->longitude() : null,
            'total_minutes' => $workMinutes,
            'regular_minutes' => $workMinutes,
            'overtime_minutes' => 0,
            'double_time_minutes' => 0,
            'break_minutes' => $breakMinutes,
            'entry_type' => $this->faker->randomElement([
                EmployeeTimeEntry::TYPE_CLOCK,
                EmployeeTimeEntry::TYPE_MANUAL,
                EmployeeTimeEntry::TYPE_IMPORTED,
                EmployeeTimeEntry::TYPE_ADJUSTED,
            ]),
            'status' => $this->faker->randomElement([
                EmployeeTimeEntry::STATUS_IN_PROGRESS,
                EmployeeTimeEntry::STATUS_COMPLETED,
                EmployeeTimeEntry::STATUS_APPROVED,
                EmployeeTimeEntry::STATUS_REJECTED,
                EmployeeTimeEntry::STATUS_PAID,
            ]),
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'exported_to_payroll' => false,
            'exported_at' => null,
            'payroll_batch_id' => null,
        ];
    }
}
