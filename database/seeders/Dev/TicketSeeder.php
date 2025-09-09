<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Asset;
use App\Models\Company;
use App\Models\User;
use App\Models\Project;
use Carbon\Carbon;
use Faker\Factory as Faker;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Ticket Seeder...');
        $faker = Faker::create();

        DB::transaction(function () use ($faker) {
            $companies = Company::where('id', '>', 1)->get();

            foreach ($companies as $company) {
                $this->command->info("Creating tickets for company: {$company->name}");
                
                $users = User::where('company_id', $company->id)->get();
                $clients = Client::where('company_id', $company->id)
                    ->where('lead', false)
                    ->get();
                
                // Generate 6 months of historical tickets
                $startDate = Carbon::now()->subMonths(6);
                $endDate = Carbon::now();
                
                foreach ($clients as $client) {
                    $employeeCount = $client->employee_count ?? 10;
                    $contacts = Contact::where('client_id', $client->id)->pluck('id')->toArray();
                    $assets = Asset::where('client_id', $client->id)->pluck('id')->toArray();
                    $projects = Project::where('client_id', $client->id)->pluck('id')->toArray();
                    
                    // Determine tickets per month based on client size
                    if ($employeeCount >= 100) {
                        $ticketsPerMonth = $faker->numberBetween(50, 100);
                    } elseif ($employeeCount >= 20) {
                        $ticketsPerMonth = $faker->numberBetween(20, 50);
                    } else {
                        $ticketsPerMonth = $faker->numberBetween(5, 20);
                    }
                    
                    // Generate tickets for each month
                    for ($month = 0; $month < 6; $month++) {
                        $monthStart = Carbon::now()->subMonths(6 - $month)->startOfMonth();
                        $monthEnd = Carbon::now()->subMonths(6 - $month)->endOfMonth();
                        
                        for ($i = 0; $i < $ticketsPerMonth; $i++) {
                            $this->createTicket($client, $company, $users, $contacts, $assets, $projects, $monthStart, $monthEnd, $faker);
                        }
                    }
                }
                
                $this->command->info("Completed tickets for company: {$company->name}");
            }
        });

        $this->command->info('Ticket Seeder completed!');
    }

    /**
     * Create a single ticket
     */
    private function createTicket($client, $company, $users, $contacts, $assets, $projects, $monthStart, $monthEnd, $faker)
    {
        $createdAt = $faker->dateTimeBetween($monthStart, $monthEnd);
        
        // Determine ticket status (40% closed, 20% open, 20% in progress, 10% on hold, 10% resolved)
        $statusWeights = [
            'closed' => 40,
            'open' => 20,
            'in_progress' => 20,
            'on_hold' => 10,
            'resolved' => 10
        ];
        $status = $this->weightedRandom($statusWeights);
        
        // Determine priority (5% critical, 15% high, 50% medium, 30% low)
        $priorityWeights = [
            'critical' => 5,
            'high' => 15,
            'medium' => 50,
            'low' => 30
        ];
        $priority = $this->weightedRandom($priorityWeights);
        
        // Determine ticket type
        $ticketTypes = [
            'support' => ['Password reset', 'Email issues', 'Software installation', 'Printer not working', 'Cannot access file share'],
            'incident' => ['Server down', 'Network outage', 'Application crash', 'Data loss', 'Security breach'],
            'request' => ['New user setup', 'Software license', 'Hardware upgrade', 'Access request', 'Report generation'],
            'maintenance' => ['System update', 'Backup verification', 'Performance tuning', 'Log review', 'Security patching'],
            'project' => ['Migration task', 'Implementation step', 'Configuration change', 'Testing phase', 'Documentation update']
        ];
        
        $type = $faker->randomElement(array_keys($ticketTypes));
        $subjectPrefix = $faker->randomElement($ticketTypes[$type]);
        
        $ticket = Ticket::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'contact_id' => !empty($contacts) ? $faker->randomElement($contacts) : null,
            'asset_id' => !empty($assets) && $faker->boolean(60) ? $faker->randomElement($assets) : null,
            'project_id' => !empty($projects) && $type === 'project' ? $faker->randomElement($projects) : null,
            'ticket_number' => 'TKT-' . str_pad($faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'subject' => $subjectPrefix . ' - ' . $faker->words(3, true),
            'details' => $this->generateTicketDetails($type, $faker),
            'priority' => $priority,
            'status' => $status,
            'type' => $type,
            'source' => $faker->randomElement(['email', 'phone', 'portal', 'walk-in', 'monitoring']),
            'category' => $faker->randomElement(['Hardware', 'Software', 'Network', 'Security', 'Account', 'Other']),
            'created_by' => $users->random()->id,
            'assigned_to' => $faker->boolean(80) ? $users->random()->id : null,
            'billable' => $faker->boolean(70), // 70% billable
            'time_worked' => $status !== 'open' ? $faker->numberBetween(15, 240) : 0,
            'internal_notes' => $faker->optional(0.3)->sentence(),
            'resolution' => $status === 'closed' ? $faker->paragraph() : null,
            'closed_at' => $status === 'closed' ? Carbon::instance($createdAt)->addHours($faker->numberBetween(1, 72)) : null,
            'resolved_at' => in_array($status, ['closed', 'resolved']) ? Carbon::instance($createdAt)->addHours($faker->numberBetween(1, 48)) : null,
            'due_date' => $priority === 'critical' ? Carbon::instance($createdAt)->addHours(4) : 
                        ($priority === 'high' ? Carbon::instance($createdAt)->addDays(1) : 
                        ($priority === 'medium' ? Carbon::instance($createdAt)->addDays(3) : null)),
            'created_at' => $createdAt,
            'updated_at' => $status !== 'open' ? Carbon::instance($createdAt)->addHours($faker->numberBetween(1, 24)) : $createdAt,
        ]);
        
        return $ticket;
    }

    /**
     * Generate realistic ticket details
     */
    private function generateTicketDetails($type, $faker)
    {
        $templates = [
            'support' => [
                'User is reporting that they cannot %s. They have tried %s but the issue persists. This is affecting their ability to %s.',
                'Multiple users in the %s department are experiencing issues with %s. The problem started %s.',
                'Request for assistance with %s. User needs this resolved by %s for %s.'
            ],
            'incident' => [
                'URGENT: %s is currently down and affecting %d users. Business impact: %s. Immediate action required.',
                'Critical system failure detected in %s. Error message: %s. All users in %s location are affected.',
                'Security incident reported: %s. Detected at %s. Potential data exposure for %s.'
            ],
            'request' => [
                'New employee starting on %s requires: %s. Manager approval attached. Department: %s.',
                'Request for %s upgrade. Current version: %s. Business justification: %s.',
                'Access request for %s system. Required for %s project. Approved by %s.'
            ],
            'maintenance' => [
                'Scheduled maintenance for %s system. Window: %s to %s. Expected impact: %s.',
                'Routine %s check completed. Found %d issues requiring attention. Priority: %s.',
                'Patch deployment for %s. CVE addressed: %s. Risk level: %s.'
            ],
            'project' => [
                'Task for %s project: %s. Dependencies: %s. Estimated hours: %d.',
                'Milestone update: %s phase completed. Next steps: %s. Blockers: %s.',
                'Configuration change for %s migration. Old value: %s. New value: %s.'
            ]
        ];
        
        $template = $faker->randomElement($templates[$type]);
        
        // Simple template filling (just return a realistic description)
        return $faker->paragraph(3) . "\n\n" . 
               "Environment: " . $faker->randomElement(['Production', 'Staging', 'Development']) . "\n" .
               "Affected Users: " . $faker->numberBetween(1, 50) . "\n" .
               "Business Impact: " . $faker->randomElement(['High', 'Medium', 'Low']) . "\n\n" .
               "Additional Notes: " . $faker->sentence();
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