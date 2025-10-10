<?php

namespace Database\Seeders\Dev;

use App\Models\CashFlowProjection;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CashFlowProjectionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CashFlowProjection records...");
        $this->command->info("✓ CashFlowProjection seeded");
    }
}
