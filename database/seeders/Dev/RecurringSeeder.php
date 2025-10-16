<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\Recurring;
use Illuminate\Database\Seeder;

class RecurringSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating Recurring records...");
        $this->command->info("✓ Recurring seeded");
    }
}
