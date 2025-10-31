<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\FinancialReport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FinancialReportSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Financial Report Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating financial reports for company: {$company->name}");

            // Create monthly financial reports for the past 12 months
            for ($i = 0; $i < 12; $i++) {
                $reportDate = Carbon::now()->subMonths($i)->startOfMonth();

                FinancialReport::factory()
                    ->for($company)
                    ->create([
                        'report_date' => $reportDate,
                        'period_type' => 'month',
                    ]);
            }

            // Create quarterly reports for the past 4 quarters
            for ($i = 0; $i < 4; $i++) {
                $reportDate = Carbon::now()->subQuarters($i)->startOfQuarter();

                FinancialReport::factory()
                    ->for($company)
                    ->create([
                        'report_date' => $reportDate,
                        'period_type' => 'quarter',
                    ]);
            }

            // Create annual report for the past year
            FinancialReport::factory()
                ->for($company)
                ->create([
                    'report_date' => Carbon::now()->subYear()->startOfYear(),
                    'period_type' => 'year',
                ]);

            $this->command->info("Completed financial reports for company: {$company->name}");
        }

        $this->command->info('Financial Report Seeder completed!');
    }
}
