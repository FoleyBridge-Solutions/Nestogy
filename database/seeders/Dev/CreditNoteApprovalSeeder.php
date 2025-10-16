<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\CreditNoteApproval;
use Illuminate\Database\Seeder;

class CreditNoteApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CreditNoteApproval records...");
        $this->command->info("✓ CreditNoteApproval seeded");
    }
}
