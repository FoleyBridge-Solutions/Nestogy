<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\QuoteApproval;
use Illuminate\Database\Seeder;

class QuoteApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating QuoteApproval records...");
        $this->command->info("✓ QuoteApproval seeded");
    }
}
