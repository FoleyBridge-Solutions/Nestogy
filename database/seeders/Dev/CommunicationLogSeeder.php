<?php

namespace Database\Seeders\Dev;

use App\Models\CommunicationLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunicationLogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating CommunicationLog records...');
$companies = Company::where('id', '>', 1)->get();
        $count = 0;
        
        foreach ($companies as $company) {
            $clients = Client::where('company_id', $company->id)->get();
            $users = User::where('company_id', $company->id)->get();
            
            if ($clients->isEmpty() || $users->isEmpty()) continue;
            
            foreach ($clients as $client) {
                $logCount = rand(10, 50);
                for ($i = 0; $i < $logCount; $i++) {
                    CommunicationLog::factory()->create([
                        'company_id' => $company->id,
                        'client_id' => $client->id,
                        'user_id' => $users->random()->id,
                        'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
                    ]);
                    $count++;
                }
            }
        }
        
        $this->command->info("✓ Created {$count} communication logs");
    }
}
