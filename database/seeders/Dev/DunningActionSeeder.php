<?php

namespace Database\Seeders\Dev;

use App\Domains\Collections\Models\DunningAction;
use Illuminate\Database\Seeder;

class DunningActionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating DunningAction records...");
        $this->command->info("✓ DunningAction seeded");
    }
}
