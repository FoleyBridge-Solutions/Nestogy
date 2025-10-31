<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\PhysicalMail\Models\PhysicalMailSettings;
use Illuminate\Database\Seeder;

class PhysicalMailSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Physical Mail Settings Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Each company gets 1 physical mail settings config
            PhysicalMailSettings::factory()
                ->for($company)
                ->create();
        }

        $this->command->info('Physical Mail Settings Seeder completed!');
    }
}
