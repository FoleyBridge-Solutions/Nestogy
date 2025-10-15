<?php

namespace Database\Seeders\Dev;

use App\Models\TimeEntry;
use App\Models\Company;
use App\Models\User;
use App\Domains\Client\Models\Client;
use App\Models\Project;
use Illuminate\Database\Seeder;

class TimeEntrySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating TimeEntry records...');
$companies = Company::where('id', '>', 1)->get();
        $count = 0;
        
        foreach ($companies as $company) {
            $users = User::where('company_id', $company->id)->get();
            $clients = Client::where('company_id', $company->id)->get();
            $projects = Project::where('company_id', $company->id)->get();
            
            if ($users->isEmpty() || $clients->isEmpty()) continue;
            
            foreach ($users as $user) {
                $entryCount = rand(50, 200);
                for ($i = 0; $i < $entryCount; $i++) {
                    TimeEntry::factory()->create([
                        'company_id' => $company->id,
                        'user_id' => $user->id,
                        'client_id' => $clients->random()->id,
                        'project_id' => $projects->isNotEmpty() && fake()->boolean(30) ? $projects->random()->id : null,
                        'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
                    ]);
                    $count++;
                }
            }
        }
        
        $this->command->info("✓ Created {$count} time entries");
    }
}
