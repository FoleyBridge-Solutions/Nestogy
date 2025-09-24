<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\Company;
use App\Models\Client;
use App\Models\User;
use App\Models\Contact;
use Carbon\Carbon;

class SimpleTicketSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating tickets (simplified)...');
        
        $companies = Company::where('id', '>', 1)->get();
        $totalTickets = 0;
        
        foreach ($companies as $company) {
            $clients = Client::where('company_id', $company->id)->limit(20)->get();
            $users = User::where('company_id', $company->id)->get();
            
            if ($clients->isEmpty() || $users->isEmpty()) {
                continue;
            }
            
            foreach ($clients as $client) {
                // Create 5-10 tickets per client
                $numTickets = rand(5, 10);
                
                for ($i = 0; $i < $numTickets; $i++) {
                    $createdAt = fake()->dateTimeBetween('-6 months', 'now');
                    
                    Ticket::create([
                        'company_id' => $company->id,
                        'client_id' => $client->id,
                        'created_by' => $users->random()->id,
                        'assigned_to' => fake()->boolean(80) ? $users->random()->id : null,
                        'prefix' => 'TKT',
                        'number' => fake()->unique()->numberBetween(100000, 999999),
                        'subject' => fake()->sentence(6),
                        'details' => fake()->paragraphs(2, true),
                        'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
                        'status' => fake()->randomElement(['open', 'in_progress', 'resolved', 'closed']),
                        'category' => fake()->randomElement(['Hardware', 'Software', 'Network', 'Security']),
                        'source' => fake()->randomElement(['email', 'phone', 'portal']),
                        'billable' => fake()->boolean(70),
                        'onsite' => fake()->boolean(20),
                        'created_at' => $createdAt,
                        'updated_at' => fake()->dateTimeBetween($createdAt, 'now'),
                    ]);
                    
                    $totalTickets++;
                }
            }
            
            $this->command->info("  Created tickets for {$company->name}");
        }
        
        $this->command->info("Created {$totalTickets} tickets total.");
    }
}