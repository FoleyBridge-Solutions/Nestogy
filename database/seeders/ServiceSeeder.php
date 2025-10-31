<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Service Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create 20-40 services per company
            Service::factory()
                ->count(rand(20, 40))
                ->for($company)
                ->create();
        }

        $this->command->info('Service Seeder completed!');
    }
}
