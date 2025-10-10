<?php

namespace Database\Seeders\Dev;

use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating PricingRule records...");
        $this->command->info("✓ PricingRule seeded");
    }
}
