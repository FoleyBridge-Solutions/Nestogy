<?php

namespace Database\Seeders\Dev;

use App\Domains\Core\Models\AnalyticsSnapshot;
use App\Domains\Company\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnalyticsSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating analytics snapshots (2 years of daily data)...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $startDate = Carbon::now()->subYears(2);
            $endDate = Carbon::now();

            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                AnalyticsSnapshot::factory()->create([
                    'company_id' => $company->id,
                    'snapshot_date' => $currentDate->toDateString(),
                    'created_at' => $currentDate,
                ]);

                $currentDate->addDay();
            }
        }

        $this->command->info('✓ Created '.AnalyticsSnapshot::count().' analytics snapshots');
    }
}
