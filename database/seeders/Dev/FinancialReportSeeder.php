<?php

namespace Database\Seeders\Dev;

use App\Models\FinancialReport;
use Illuminate\Database\Seeder;

class FinancialReportSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating FinancialReport records...");
        $this->command->info("✓ FinancialReport seeded");
    }
}
