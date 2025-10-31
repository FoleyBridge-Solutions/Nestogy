<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\CashFlowProjection;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CashFlowProjectionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Cash Flow Projection Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating cash flow projections for company: {$company->name}");

            // Create weekly projections for the next 12 weeks
            for ($i = 0; $i < 12; $i++) {
                $projectionDate = Carbon::now()->addWeeks($i)->startOfWeek();

                CashFlowProjection::factory()
                    ->for($company)
                    ->create([
                        'projection_date' => $projectionDate,
                        'period_type' => 'week',
                    ]);
            }

            // Create monthly projections for the next 6 months
            for ($i = 0; $i < 6; $i++) {
                $projectionDate = Carbon::now()->addMonths($i)->startOfMonth();

                CashFlowProjection::factory()
                    ->for($company)
                    ->create([
                        'projection_date' => $projectionDate,
                        'period_type' => 'month',
                    ]);
            }

            $this->command->info("Completed cash flow projections for company: {$company->name}");
        }

        $this->command->info('Cash Flow Projection Seeder completed!');
    }
}
