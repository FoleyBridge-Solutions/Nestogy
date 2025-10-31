<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\File;
use Illuminate\Database\Seeder;

class FileSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting File Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create 30-80 file records per company
            File::factory()
                ->count(rand(30, 80))
                ->for($company)
                ->create();
        }

        $this->command->info('File Seeder completed!');
    }
}
