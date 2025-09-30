<?php

namespace Database\Seeders\Dev;

use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Project Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating projects for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();
            $users = User::where('company_id', $company->id)->get();

            if ($clients->isEmpty() || $users->isEmpty()) {
                continue;
            }

            foreach ($clients as $client) {
                // Not all clients have projects
                if (fake()->boolean(60)) {
                    // Create 1-3 projects per client
                    $numProjects = rand(1, 3);

                    for ($i = 0; $i < $numProjects; $i++) {
                        $project = new Project;
                        $project->company_id = $company->id;
                        $project->client_id = $client->id;
                        $project->prefix = 'PRJ';
                        $project->number = fake()->unique()->numberBetween(1, 99999);
                        $project->name = fake()->catchPhrase().' Project';
                        $project->description = fake()->paragraphs(3, true);
                        $project->due = fake()->dateTimeBetween('now', '+6 months');
                        $project->manager_id = fake()->boolean(70) ? $users->random()->id : null;
                        $project->completed_at = fake()->boolean(30) ? fake()->dateTimeBetween('-1 month', 'now') : null;
                        $project->save();
                    }
                }
            }

            $this->command->info("Completed projects for company: {$company->name}");
        }

        $this->command->info('Project Seeder completed!');
    }
}
