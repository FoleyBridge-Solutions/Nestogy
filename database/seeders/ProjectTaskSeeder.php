<?php

namespace Database\Seeders;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\ProjectTask;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class ProjectTaskSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating project tasks...');

        $projects = Project::all();
        
        foreach ($projects as $project) {
            $users = User::where('company_id', $project->company_id)->pluck('id')->toArray();
            
            if (empty($users)) {
                continue;
            }
            
            $taskCount = rand(5, 20);
            
            for ($i = 0; $i < $taskCount; $i++) {
                ProjectTask::create([
                    'project_id' => $project->id,
                    'company_id' => $project->company_id,
                    'name' => fake()->sentence(4),
                    'description' => fake()->optional(0.7)->paragraph(),
                    'assigned_to' => fake()->randomElement($users),
                    'status' => fake()->randomElement(['not_started', 'in_progress', 'completed', 'on_hold']),
                    'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
                    'start_date' => fake()->dateTimeBetween($project->start_date ?? '-1 month', 'now'),
                    'due_date' => fake()->optional(0.8)->dateTimeBetween('now', '+2 months'),
                    'estimated_hours' => fake()->optional(0.6)->randomFloat(1, 1, 40),
                    'actual_hours' => fake()->optional(0.4)->randomFloat(1, 1, 50),
                    'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
                    'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
                ]);
            }
        }

        $this->command->info('Project tasks created successfully.');
    }
}
