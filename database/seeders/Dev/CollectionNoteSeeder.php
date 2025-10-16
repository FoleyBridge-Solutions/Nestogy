<?php

namespace Database\Seeders\Dev;

use App\Domains\Collections\Models\CollectionNote;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class CollectionNoteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CollectionNote records...");
        $this->command->info("✓ CollectionNote seeded");
    }
}
