<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\PortalNotification;
use Illuminate\Database\Seeder;

class PortalNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Portal Notification Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $clients = Client::where('company_id', $company->id)->get();

            if ($clients->isEmpty()) {
                continue;
            }

            // Create 5-15 notifications per client
            foreach ($clients as $client) {
                PortalNotification::factory()
                    ->count(rand(5, 15))
                    ->for($company)
                    ->for($client)
                    ->create();
            }
        }

        $this->command->info('Portal Notification Seeder completed!');
    }
}
}
