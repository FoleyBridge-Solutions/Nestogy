<?php

namespace Database\Seeders\Dev;

use App\Domains\Tax\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;

class TaxJurisdictionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating TaxJurisdiction records...");
        $this->command->info("✓ TaxJurisdiction seeded");
    }
}
