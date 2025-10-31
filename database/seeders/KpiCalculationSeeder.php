<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\KpiCalculation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class KpiCalculationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting KPI Calculation Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create daily KPI calculations for the past 30 days
            for ($i = 0; $i < 30; $i++) {
                $calculationDate = Carbon::now()->subDays($i);

                KpiCalculation::factory()
                    ->for($company)
                    ->create([
                        'calculation_date' => $calculationDate,
                    ]);
            }

            // Create monthly KPI calculations for the past 12 months
            for ($i = 0; $i < 12; $i++) {
                $calculationDate = Carbon::now()->subMonths($i)->startOfMonth();

                KpiCalculation::factory()
                    ->for($company)
                    ->create([
                        'calculation_date' => $calculationDate,
                        'period_type' => 'month',
                    ]);
            }
        }

        $this->command->info('KPI Calculation Seeder completed!');
    }
}
