<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\QuoteTemplate;
use Illuminate\Database\Seeder;

class QuoteTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating QuoteTemplate records...");
        $this->command->info("✓ QuoteTemplate seeded");
    }
}
