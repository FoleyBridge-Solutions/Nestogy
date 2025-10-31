<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeSchedule;
use App\Domains\HR\Models\EmployeeTimeEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeeTimeEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Employee Time Entry Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating time entries for company: {$company->name}");

            $users = User::where('company_id', $company->id)->get();

            if ($users->isEmpty()) {
                $this->command->warn("No users found for company: {$company->name}. Skipping.");
                continue;
            }

            // Get completed schedules from the past 60 days
            $schedules = EmployeeSchedule::where('company_id', $company->id)
                ->where('status', EmployeeSchedule::STATUS_COMPLETED)
                ->where('scheduled_date', '>=', Carbon::now()->subDays(60))
                ->where('scheduled_date', '<=', Carbon::now())
                ->with('shift')
                ->get();

            if ($schedules->isEmpty()) {
                $this->command->info("No completed schedules found for company: {$company->name}. Creating random time entries.");
                
                // Create random time entries for users without schedules
                foreach ($users as $user) {
                    $this->createRandomTimeEntries($company, $user);
                }
            } else {
                // Create time entries based on schedules
                foreach ($schedules as $schedule) {
                    $this->createTimeEntryFromSchedule($company, $schedule);
                }

                // Also create some manual/adjusted entries
                foreach ($users->random(min($users->count(), rand(3, 8))) as $user) {
                    $this->createManualTimeEntries($company, $user);
                }
            }

            $this->command->info("Completed time entries for company: {$company->name}");
        }

        $this->command->info('Employee Time Entry Seeder completed!');
    }

    /**
     * Create time entry from schedule
     */
    private function createTimeEntryFromSchedule(Company $company, EmployeeSchedule $schedule): void
    {
        $scheduledDate = Carbon::parse($schedule->scheduled_date);
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);

        // Combine date with time
        $clockIn = $scheduledDate->copy()
            ->setTime($startTime->hour, $startTime->minute)
            ->addMinutes(rand(-10, 15)); // Some variance in clock-in time

        // Handle overnight shifts
        if ($endTime->lessThan($startTime)) {
            $clockOut = $scheduledDate->copy()->addDay()
                ->setTime($endTime->hour, $endTime->minute)
                ->addMinutes(rand(-10, 15));
        } else {
            $clockOut = $scheduledDate->copy()
                ->setTime($endTime->hour, $endTime->minute)
                ->addMinutes(rand(-10, 15));
        }

        $totalMinutes = $clockIn->diffInMinutes($clockOut);
        $breakMinutes = $schedule->shift->break_minutes ?? 0;
        $workMinutes = max(0, $totalMinutes - $breakMinutes);

        // Calculate overtime (simple: over 8 hours)
        $regularMinutes = min($workMinutes, 480); // 8 hours
        $overtimeMinutes = max(0, $workMinutes - 480);

        EmployeeTimeEntry::factory()
            ->for($schedule->user)
            ->create([
                'company_id' => $company->id,
                'shift_id' => $schedule->shift_id,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'total_minutes' => $workMinutes,
                'regular_minutes' => $regularMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'break_minutes' => $breakMinutes,
                'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
                'status' => fake()->weighted([
                    EmployeeTimeEntry::STATUS_PAID => 60,
                    EmployeeTimeEntry::STATUS_APPROVED => 30,
                    EmployeeTimeEntry::STATUS_COMPLETED => 10,
                ]),
            ]);
    }

    /**
     * Create random time entries for users
     */
    private function createRandomTimeEntries(Company $company, User $user, int $count = null): void
    {
        $count = $count ?? rand(20, 40);

        for ($i = 0; $i < $count; $i++) {
            $clockIn = Carbon::now()
                ->subDays(rand(1, 60))
                ->setTime(rand(6, 10), rand(0, 59));

            $workHours = rand(4, 10);
            $clockOut = $clockIn->copy()->addHours($workHours)->addMinutes(rand(0, 59));

            $totalMinutes = $clockIn->diffInMinutes($clockOut);
            $breakMinutes = fake()->randomElement([0, 15, 30, 45, 60]);
            $workMinutes = max(0, $totalMinutes - $breakMinutes);

            $regularMinutes = min($workMinutes, 480);
            $overtimeMinutes = max(0, $workMinutes - 480);

            EmployeeTimeEntry::factory()
                ->for($user)
                ->create([
                    'company_id' => $company->id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'total_minutes' => $workMinutes,
                    'regular_minutes' => $regularMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                    'break_minutes' => $breakMinutes,
                    'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
                    'status' => fake()->weighted([
                        EmployeeTimeEntry::STATUS_PAID => 50,
                        EmployeeTimeEntry::STATUS_APPROVED => 30,
                        EmployeeTimeEntry::STATUS_COMPLETED => 20,
                    ]),
                ]);
        }
    }

    /**
     * Create manual/adjusted time entries
     */
    private function createManualTimeEntries(Company $company, User $user): void
    {
        $count = rand(2, 5);

        for ($i = 0; $i < $count; $i++) {
            $clockIn = Carbon::now()
                ->subDays(rand(1, 30))
                ->setTime(rand(6, 10), 0);

            $workHours = rand(4, 9);
            $clockOut = $clockIn->copy()->addHours($workHours);

            $totalMinutes = $clockIn->diffInMinutes($clockOut);
            $breakMinutes = 60;
            $workMinutes = max(0, $totalMinutes - $breakMinutes);

            EmployeeTimeEntry::factory()
                ->for($user)
                ->create([
                    'company_id' => $company->id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'total_minutes' => $workMinutes,
                    'regular_minutes' => $workMinutes,
                    'overtime_minutes' => 0,
                    'break_minutes' => $breakMinutes,
                    'entry_type' => fake()->randomElement([
                        EmployeeTimeEntry::TYPE_MANUAL,
                        EmployeeTimeEntry::TYPE_ADJUSTED,
                    ]),
                    'status' => fake()->weighted([
                        EmployeeTimeEntry::STATUS_APPROVED => 60,
                        EmployeeTimeEntry::STATUS_COMPLETED => 40,
                    ]),
                ]);
        }
    }
}
