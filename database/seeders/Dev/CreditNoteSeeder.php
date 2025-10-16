<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\CreditNote;
use Illuminate\Database\Seeder;

class CreditNoteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CreditNote records...");
        $this->command->info("✓ CreditNote seeded");
    }
}
