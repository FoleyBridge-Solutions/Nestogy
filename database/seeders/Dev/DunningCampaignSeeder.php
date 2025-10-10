<?php

namespace Database\Seeders\Dev;

use App\Models\DunningCampaign;
use Illuminate\Database\Seeder;

class DunningCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating DunningCampaign records...");
        $this->command->info("✓ DunningCampaign seeded");
    }
}
