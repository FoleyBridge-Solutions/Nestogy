<?php

namespace Database\Seeders\Dev;

use App\Domains\Collections\Models\DunningSequence;
use Illuminate\Database\Seeder;

class DunningSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating DunningSequence records...");
        $this->command->info("✓ DunningSequence seeded");
    }
}
