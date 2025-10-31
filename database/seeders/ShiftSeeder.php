<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\HR\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Shift Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating shifts for company: {$company->name}");

            // Create standard shifts for each company
            $shifts = [
                [
                    'name' => 'Morning Shift',
                    'start_time' => '08:00:00',
                    'end_time' => '16:00:00',
                    'break_minutes' => 60,
                    'days_of_week' => [1, 2, 3, 4, 5], // Mon-Fri
                    'is_active' => true,
                    'color' => '#3B82F6',
                    'description' => 'Standard morning shift, Monday to Friday',
                ],
                [
                    'name' => 'Evening Shift',
                    'start_time' => '16:00:00',
                    'end_time' => '00:00:00',
                    'break_minutes' => 45,
                    'days_of_week' => [1, 2, 3, 4, 5],
                    'is_active' => true,
                    'color' => '#F59E0B',
                    'description' => 'Evening shift, Monday to Friday',
                ],
                [
                    'name' => 'Night Shift',
                    'start_time' => '00:00:00',
                    'end_time' => '08:00:00',
                    'break_minutes' => 45,
                    'days_of_week' => [1, 2, 3, 4, 5],
                    'is_active' => true,
                    'color' => '#8B5CF6',
                    'description' => 'Overnight shift, Monday to Friday',
                ],
                [
                    'name' => 'Day Shift',
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                    'break_minutes' => 60,
                    'days_of_week' => [1, 2, 3, 4, 5],
                    'is_active' => true,
                    'color' => '#10B981',
                    'description' => 'Standard day shift with lunch break',
                ],
                [
                    'name' => 'Weekend Shift',
                    'start_time' => '10:00:00',
                    'end_time' => '18:00:00',
                    'break_minutes' => 30,
                    'days_of_week' => [0, 6], // Sat-Sun
                    'is_active' => true,
                    'color' => '#EF4444',
                    'description' => 'Weekend coverage shift',
                ],
                [
                    'name' => 'Flex Shift',
                    'start_time' => '10:00:00',
                    'end_time' => '18:00:00',
                    'break_minutes' => 60,
                    'days_of_week' => [1, 2, 3, 4, 5],
                    'is_active' => true,
                    'color' => '#EC4899',
                    'description' => 'Flexible shift hours',
                ],
            ];

            foreach ($shifts as $shiftData) {
                Shift::factory()
                    ->for($company)
                    ->create($shiftData);
            }

            $this->command->info("Completed shifts for company: {$company->name}");
        }

        $this->command->info('Shift Seeder completed!');
    }
}
