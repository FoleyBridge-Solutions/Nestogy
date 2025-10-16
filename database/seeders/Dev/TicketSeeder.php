<?php

namespace Database\Seeders\Dev;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Client\Models\Contact;
use App\Domains\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating comprehensive ticket history (2 years)...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();
        $totalTickets = 0;

        foreach ($companies as $company) {
            $this->command->info("Creating tickets for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();
            $users = User::where('company_id', $company->id)->get();

            if ($clients->isEmpty() || $users->isEmpty()) {
                continue;
            }

            // Split users into technicians and other staff
            $technicians = $users->filter(function ($user) {
                return str_contains(strtolower($user->role ?? ''), 'tech') ||
                       str_contains(strtolower($user->role ?? ''), 'admin');
            });

            if ($technicians->isEmpty()) {
                $technicians = $users;
            }

            foreach ($clients as $clientIndex => $client) {
                // Get contacts for this client
                $contacts = Contact::where('client_id', $client->id)->pluck('id')->toArray();

                // Ticket volume based on client size and status
                $employeeCount = $client->employee_count ?? 50;
                $isActive = $client->status === 'active';

                if (! $isActive) {
                    // Inactive clients only have old tickets
                    $ticketsPerMonth = 0.5;
                    $monthsActive = 12; // They were active for 1 year
                } else {
                    // Active clients have ongoing tickets - more realistic volumes
                    $ticketsPerMonth = match (true) {
                        $employeeCount > 500 => rand(8, 15),   // Large clients
                        $employeeCount > 100 => rand(4, 8),    // Medium-large clients
                        $employeeCount > 50 => rand(2, 5),     // Medium clients
                        $employeeCount > 20 => rand(1, 3),     // Small-medium clients
                        default => rand(0.5, 2),               // Small clients
                    };
                    $monthsActive = 24; // Full 2 years
                }

                $totalClientTickets = (int) ($ticketsPerMonth * $monthsActive);

                // Distribute tickets across the time period
                for ($i = 0; $i < $totalClientTickets; $i++) {
                    // Random date in the past 2 years (or when client was active)
                    if (! $isActive) {
                        // Old tickets from when they were active
                        $createdAt = fake()->dateTimeBetween('-2 years', '-1 year');
                    } else {
                        $createdAt = fake()->dateTimeBetween('-2 years', 'now');
                    }

                    // Ticket status based on age
                    $daysOld = Carbon::parse($createdAt)->diffInDays(now());
                    if ($daysOld > 30) {
                        $status = fake()->randomElement(['closed', 'closed', 'closed', 'resolved', 'resolved']);
                        $closedAt = fake()->dateTimeBetween($createdAt, Carbon::parse($createdAt)->addDays(rand(1, 14)));
                    } elseif ($daysOld > 7) {
                        $status = fake()->randomElement(['open', 'in_progress', 'resolved', 'closed']);
                        $closedAt = $status === 'closed' ? fake()->dateTimeBetween($createdAt, 'now') : null;
                    } else {
                        $status = fake()->randomElement(['open', 'open', 'in_progress', 'pending']);
                        $closedAt = null;
                    }

                    // Priority distribution
                    $priority = fake()->randomElement([
                        'low', 'low', 'low',
                        'medium', 'medium', 'medium', 'medium',
                        'high', 'high',
                        'urgent',
                    ]);

                    // Response and resolution times based on priority
                    $responseTime = match ($priority) {
                        'urgent' => rand(5, 30),      // minutes
                        'high' => rand(30, 120),        // minutes
                        'medium' => rand(120, 480),     // 2-8 hours
                        'low' => rand(480, 1440),       // 8-24 hours
                    };

                    $resolutionTime = match ($priority) {
                        'urgent' => rand(60, 240),     // 1-4 hours
                        'high' => rand(240, 480),        // 4-8 hours
                        'medium' => rand(480, 2880),     // 8-48 hours
                        'low' => rand(1440, 10080),      // 1-7 days
                    };

                    // Ticket type
                    $type = fake()->randomElement([
                        'incident', 'incident', 'incident',  // Most common
                        'service_request', 'service_request',
                        'problem',
                        'change_request',
                    ]);

                    $isResolved = in_array($status, ['resolved', 'closed']);
                    $resolvedAt = $isResolved ? Carbon::parse($createdAt)->addMinutes($resolutionTime) : null;

                    $ticket = Ticket::factory()
                        ->state([
                            'company_id' => $company->id,
                            'client_id' => $client->id,
                            'contact_id' => ! empty($contacts) ? fake()->randomElement($contacts) : null,
                            'created_by' => $users->random()->id,
                            'assigned_to' => fake()->boolean(90) ? $technicians->random()->id : null,
                            'status' => $status,
                            'priority' => $priority,
                            'type' => $type,
                            'created_at' => $createdAt,
                            'updated_at' => fake()->dateTimeBetween($createdAt, 'now'),
                            'closed_at' => $closedAt,
                            'closed_by' => $closedAt ? $technicians->random()->id : null,
                            'first_response_at' => fake()->boolean(80) ?
                                Carbon::parse($createdAt)->addMinutes($responseTime) : null,
                            'is_resolved' => $isResolved,
                            'resolved_at' => $resolvedAt,
                            'resolved_by' => $resolvedAt ? $technicians->random()->id : null,
                            'satisfaction_rating' => $isResolved ?
                                fake()->optional(0.4)->numberBetween(1, 5) : null,
                            'time_spent' => $isResolved ?
                                fake()->numberBetween(15, 480) : 0,
                            'billable' => fake()->boolean(70),
                            'tags' => json_encode(fake()->randomElements([
                                'password-reset', 'email-issue', 'printer', 'network', 'software',
                                'hardware', 'vpn', 'backup', 'security', 'performance', 'login',
                                'microsoft-365', 'server', 'database', 'website',
                            ], rand(0, 3))),
                        ])
                        ->create();

                    $totalTickets++;
                }

                // Show progress
                if ($clientIndex % 10 == 0) {
                    $this->command->info("  Processed {$clientIndex} clients...");
                }
            }

            $this->command->info("Completed tickets for company: {$company->name}");
        }

        $this->command->info("Created {$totalTickets} tickets with 2 years of history!");
    }
}
