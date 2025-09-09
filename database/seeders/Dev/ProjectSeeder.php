<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Project Seeder...');
        $faker = Faker::create();

        DB::transaction(function () use ($faker) {
            $companies = Company::where('id', '>', 1)->get();

            foreach ($companies as $company) {
                $this->command->info("Creating projects for company: {$company->name}");
                
                $users = User::where('company_id', $company->id)->pluck('id')->toArray();
                $clients = Client::where('company_id', $company->id)
                    ->where('lead', false)
                    ->pluck('id')->toArray();
                
                // Create 30-40 projects per company
                $projectCount = $faker->numberBetween(30, 40);
                
                for ($i = 0; $i < $projectCount; $i++) {
                    $this->createProject($company, $clients, $users, $faker);
                }
                
                $this->command->info("Completed projects for company: {$company->name}");
            }
        });

        $this->command->info('Project Seeder completed!');
    }

    /**
     * Create a single project
     */
    private function createProject($company, $clients, $users, $faker)
    {
        $projectTypes = [
            'infrastructure' => [
                'Server Migration to Cloud',
                'Network Infrastructure Upgrade',
                'Data Center Consolidation',
                'Disaster Recovery Implementation',
                'VMware to Hyper-V Migration'
            ],
            'cloud' => [
                'Office 365 Migration',
                'AWS Infrastructure Setup',
                'Azure AD Implementation',
                'Cloud Backup Solution',
                'Multi-Cloud Strategy Implementation'
            ],
            'security' => [
                'Security Audit and Remediation',
                'Firewall Upgrade Project',
                'Endpoint Protection Deployment',
                'SIEM Implementation',
                'Zero Trust Architecture'
            ],
            'software' => [
                'ERP System Implementation',
                'CRM Deployment',
                'Custom Application Development',
                'Database Migration',
                'Business Intelligence Platform'
            ],
            'relocation' => [
                'Office Relocation IT Services',
                'Branch Office Setup',
                'Merger IT Integration',
                'Remote Work Infrastructure',
                'Satellite Office Connection'
            ]
        ];
        
        $type = $faker->randomElement(array_keys($projectTypes));
        $projectName = $faker->randomElement($projectTypes[$type]);
        
        // Determine project dates and status
        $startDate = $faker->dateTimeBetween('-6 months', 'now');
        $duration = $faker->numberBetween(30, 180); // 1-6 months
        $dueDate = Carbon::instance($startDate)->addDays($duration);
        
        // Status distribution: 30% completed, 40% in progress, 20% planning, 10% on hold
        $statusWeights = [
            'completed' => 30,
            'in_progress' => 40,
            'planning' => 20,
            'on_hold' => 10
        ];
        $status = $this->weightedRandom($statusWeights);
        
        // Adjust dates based on status
        if ($status === 'completed') {
            $completedAt = $faker->dateTimeBetween($startDate, min($dueDate, 'now'));
        } else {
            $completedAt = null;
        }
        
        // Determine if project is overdue
        $isOverdue = ($status === 'in_progress' && $dueDate < now()) ? true : false;
        
        Project::create([
            'company_id' => $company->id,
            'client_id' => !empty($clients) ? $faker->randomElement($clients) : null,
            'project_number' => 'PRJ-' . str_pad($faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => $projectName . ' - ' . $faker->company(),
            'description' => $this->generateProjectDescription($type, $projectName, $faker),
            'type' => $type,
            'status' => $status,
            'priority' => $faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'budget' => $faker->randomFloat(2, 5000, 100000),
            'spent' => $status === 'completed' ? $faker->randomFloat(2, 4000, 95000) : 
                      ($status === 'in_progress' ? $faker->randomFloat(2, 1000, 50000) : 0),
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'completed_at' => $completedAt,
            'project_manager_id' => !empty($users) ? $faker->randomElement($users) : null,
            'created_by' => !empty($users) ? $faker->randomElement($users) : null,
            'billable' => $faker->boolean(85), // 85% billable
            'hourly_rate' => $faker->randomElement([150, 175, 200, 225, 250]),
            'estimated_hours' => $faker->numberBetween(20, 500),
            'actual_hours' => $status === 'completed' ? $faker->numberBetween(15, 550) : 
                            ($status === 'in_progress' ? $faker->numberBetween(5, 250) : 0),
            'progress_percentage' => $status === 'completed' ? 100 : 
                                   ($status === 'in_progress' ? $faker->numberBetween(10, 90) : 
                                   ($status === 'planning' ? $faker->numberBetween(0, 10) : 0)),
            'notes' => $faker->optional(0.3)->paragraph(),
            'risks' => $isOverdue ? 'Project is currently behind schedule' : $faker->optional(0.2)->sentence(),
            'created_at' => $startDate,
            'updated_at' => $status === 'completed' ? $completedAt : Carbon::now(),
        ]);
    }

    /**
     * Generate project description
     */
    private function generateProjectDescription($type, $projectName, $faker)
    {
        $descriptions = [
            'infrastructure' => "Complete infrastructure overhaul focusing on modernization and scalability. This project includes hardware refresh, virtualization optimization, and performance improvements.",
            'cloud' => "Cloud transformation initiative to improve flexibility and reduce operational costs. Includes migration planning, implementation, and post-migration optimization.",
            'security' => "Comprehensive security enhancement project to address current vulnerabilities and implement industry best practices. Includes assessment, remediation, and ongoing monitoring setup.",
            'software' => "End-to-end software implementation to streamline business processes and improve operational efficiency. Includes requirements gathering, customization, training, and go-live support.",
            'relocation' => "Complete IT infrastructure setup and migration for office relocation. Includes network design, equipment procurement, installation, and cutover coordination."
        ];
        
        $baseDescription = $descriptions[$type] ?? $faker->paragraph();
        
        return $baseDescription . "\n\nKey Deliverables:\n" .
               "- " . $faker->sentence() . "\n" .
               "- " . $faker->sentence() . "\n" .
               "- " . $faker->sentence() . "\n\n" .
               "Success Criteria: " . $faker->sentence();
    }

    /**
     * Weighted random selection
     */
    private function weightedRandom($weights)
    {
        $rand = rand(1, array_sum($weights));
        $cumulative = 0;
        
        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $key;
            }
        }
        
        return array_key_first($weights);
    }
}