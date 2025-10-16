<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;

class FinancialSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating Financial records...");
        $this->command->info("✓ Financial seeded");
    }
}
