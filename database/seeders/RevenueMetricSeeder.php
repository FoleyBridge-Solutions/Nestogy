<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\RevenueMetric;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RevenueMetricSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Revenue Metric Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating revenue metrics for company: {$company->name}");

            // Create monthly revenue metrics for the past 24 months
            $startDate = Carbon::now()->subMonths(24)->startOfMonth();

            for ($i = 0; $i < 24; $i++) {
                $metricDate = $startDate->copy()->addMonths($i);

                RevenueMetric::factory()
                    ->for($company)
                    ->create([
                        'metric_date' => $metricDate,
                        'period_type' => 'month',
                    ]);
            }

            // Create quarterly metrics for the past 8 quarters
            $startQuarter = Carbon::now()->subQuarters(8)->startOfQuarter();

            for ($i = 0; $i < 8; $i++) {
                $metricDate = $startQuarter->copy()->addQuarters($i);

                RevenueMetric::factory()
                    ->for($company)
                    ->create([
                        'metric_date' => $metricDate,
                        'period_type' => 'quarter',
                    ]);
            }

            // Create annual metrics for the past 2 years
            for ($i = 0; $i < 2; $i++) {
                $metricDate = Carbon::now()->subYears($i)->startOfYear();

                RevenueMetric::factory()
                    ->for($company)
                    ->create([
                        'metric_date' => $metricDate,
                        'period_type' => 'year',
                    ]);
            }

            $this->command->info("Completed revenue metrics for company: {$company->name}");
        }

        $this->command->info('Revenue Metric Seeder completed!');
    }
}
