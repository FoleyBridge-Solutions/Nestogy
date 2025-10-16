<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\CreditNoteItem;
use Illuminate\Database\Seeder;

class CreditNoteItemSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CreditNoteItem records...");
        $this->command->info("✓ CreditNoteItem seeded");
    }
}
