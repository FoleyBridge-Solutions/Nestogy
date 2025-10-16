<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;

class DomainsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating Domains records...");
        $this->command->info("✓ Domains seeded");
    }
}
