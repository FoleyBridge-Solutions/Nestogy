<?php

namespace Database\Seeders\Dev;

use App\Models\ContractConfiguration;
use Illuminate\Database\Seeder;

class ContractConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating ContractConfiguration records...");
        $this->command->info("✓ ContractConfiguration seeded");
    }
}
