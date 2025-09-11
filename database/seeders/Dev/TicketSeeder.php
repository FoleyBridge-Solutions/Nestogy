<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\Company;
use App\Models\Client;
use App\Models\User;
use App\Models\Contact;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Ticket Seeder...');
        
        // Skip root company
        $companies = Company::where('id', '>', 1)->get();
        
        foreach ($companies as $company) {
            $this->command->info("Creating tickets for company: {$company->name}");
            
            $clients = Client::where('company_id', $company->id)->get();
            $users = User::where('company_id', $company->id)->get();
            
            if ($clients->isEmpty() || $users->isEmpty()) {
                continue;
            }
            
            foreach ($clients as $client) {
                // Get contacts for this client
                $contacts = Contact::where('client_id', $client->id)->pluck('id')->toArray();
                
                // Create 5-15 tickets per client
                $numTickets = rand(5, 15);
                
                for ($i = 0; $i < $numTickets; $i++) {
                    Ticket::factory()
                        ->state([
                            'company_id' => $company->id,
                            'client_id' => $client->id,
                            'contact_id' => !empty($contacts) ? fake()->randomElement($contacts) : null,
                            'created_by' => $users->random()->id,
                            'assigned_to' => fake()->boolean(80) ? $users->random()->id : null,
                        ])
                        ->create();
                }
            }
            
            $this->command->info("Completed tickets for company: {$company->name}");
        }
        
        $this->command->info('Ticket Seeder completed!');
    }
}