<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\MailQueue;
use Illuminate\Database\Seeder;

class MailQueueSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Mail Queue Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create 20-50 queued/sent emails per company
            MailQueue::factory()
                ->count(rand(20, 50))
                ->for($company)
                ->create();
        }

        $this->command->info('Mail Queue Seeder completed!');
    }
}
