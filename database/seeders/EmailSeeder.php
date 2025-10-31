<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EmailSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Skipping emails - not critical for testing.');
    }
}
