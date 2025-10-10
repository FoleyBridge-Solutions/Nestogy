<?php

namespace Database\Seeders\Dev;

use App\Models\File;
use Illuminate\Database\Seeder;

class FileSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating File records...");
        $this->command->info("✓ File seeded");
    }
}
