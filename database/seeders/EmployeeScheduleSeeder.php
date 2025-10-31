<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeSchedule;
use App\Domains\HR\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeeScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Employee Schedule Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating schedules for company: {$company->name}");

            $shifts = Shift::where('company_id', $company->id)->get();
            
            if ($shifts->isEmpty()) {
                $this->command->warn("No shifts found for company: {$company->name}. Skipping.");
                continue;
            }

            $users = User::where('company_id', $company->id)->get();

            if ($users->isEmpty()) {
                $this->command->warn("No users found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create schedules for the past 60 days and next 30 days
            $startDate = Carbon::now()->subDays(60);
            $endDate = Carbon::now()->addDays(30);

            foreach ($users as $user) {
                // Assign each user to 1-2 shifts
                $userShifts = $shifts->random(min($shifts->count(), rand(1, 2)));

                $currentDate = $startDate->copy();
                while ($currentDate->lte($endDate)) {
                    foreach ($userShifts as $shift) {
                        // Check if this shift is scheduled for this day of week
                        if (in_array($currentDate->dayOfWeek, $shift->days_of_week ?? [])) {
                            // Create schedule with status based on date
                            $status = $this->determineStatus($currentDate);

                            EmployeeSchedule::factory()
                                ->for($company)
                                ->for($user)
                                ->for($shift)
                                ->create([
                                    'scheduled_date' => $currentDate->format('Y-m-d'),
                                    'start_time' => $shift->start_time,
                                    'end_time' => $shift->end_time,
                                    'status' => $status,
                                ]);
                        }
                    }

                    $currentDate->addDay();
                }
            }

            $this->command->info("Completed schedules for company: {$company->name}");
        }

        $this->command->info('Employee Schedule Seeder completed!');
    }

    /**
     * Determine schedule status based on date
     */
    private function determineStatus(Carbon $date): string
    {
        if ($date->isFuture()) {
            return fake()->randomElement([
                EmployeeSchedule::STATUS_SCHEDULED,
                EmployeeSchedule::STATUS_CONFIRMED,
            ]);
        } elseif ($date->isToday()) {
            return EmployeeSchedule::STATUS_CONFIRMED;
        } else {
            // Past dates
            return fake()->weighted([
                EmployeeSchedule::STATUS_COMPLETED => 85,
                EmployeeSchedule::STATUS_MISSED => 10,
                EmployeeSchedule::STATUS_CONFIRMED => 5,
            ]);
        }
    }
}
