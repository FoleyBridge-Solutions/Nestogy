<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\CommunicationLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CommunicationLogSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::limit(3)->get();
        $users = User::limit(2)->get();

        if ($clients->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No clients or users found. Skipping communication log seeding.');

            return;
        }

        $sampleCommunications = [
            [
                'type' => 'inbound',
                'channel' => 'phone',
                'subject' => 'Server outage report',
                'notes' => 'Client called to report their main server is down. Escalated to Level 2 support team. Estimated resolution time: 2 hours.',
                'follow_up_required' => true,
                'follow_up_date' => Carbon::now()->addDays(1),
            ],
            [
                'type' => 'outbound',
                'channel' => 'email',
                'subject' => 'Monthly security review scheduled',
                'notes' => 'Sent email to schedule monthly security review. Waiting for client confirmation on preferred date and time.',
                'follow_up_required' => true,
                'follow_up_date' => Carbon::now()->addDays(3),
            ],
            [
                'type' => 'meeting',
                'channel' => 'video_call',
                'subject' => 'Quarterly business review',
                'notes' => 'Conducted Q3 business review meeting. Discussed upcoming technology initiatives, budget planning for Q4, and service level performance metrics. Client satisfied with current service delivery.',
                'follow_up_required' => false,
            ],
            [
                'type' => 'inbound',
                'channel' => 'email',
                'subject' => 'New user onboarding request',
                'notes' => 'HR department requested setup for 3 new employees starting next Monday. Need to create accounts, assign licenses, and schedule orientation.',
                'follow_up_required' => true,
                'follow_up_date' => Carbon::now()->addDays(2),
            ],
            [
                'type' => 'support',
                'channel' => 'chat',
                'subject' => 'Printer connectivity issue',
                'notes' => 'Helped troubleshoot printer connectivity issue via chat. Issue resolved by updating printer drivers and reconfiguring network settings.',
                'follow_up_required' => false,
            ],
            [
                'type' => 'outbound',
                'channel' => 'phone',
                'subject' => 'Backup verification check',
                'notes' => 'Called to verify backup completion after last night\'s scheduled backup. All systems backed up successfully. No issues to report.',
                'follow_up_required' => false,
            ],
            [
                'type' => 'billing',
                'channel' => 'email',
                'subject' => 'Invoice payment confirmation',
                'notes' => 'Client confirmed receipt of invoice #INV-2024-001 and payment will be processed within 30 days as per contract terms.',
                'follow_up_required' => true,
                'follow_up_date' => Carbon::now()->addDays(30),
            ],
            [
                'type' => 'technical',
                'channel' => 'in_person',
                'subject' => 'Network infrastructure upgrade',
                'notes' => 'On-site visit to assess network infrastructure for planned upgrade. Identified key improvement areas and provided preliminary timeline. Client approved moving forward with detailed proposal.',
                'follow_up_required' => true,
                'follow_up_date' => Carbon::now()->addDays(7),
            ],
        ];

        foreach ($clients as $client) {
            // Get contacts for this client
            $contacts = $client->contacts()->limit(2)->get();

            // Create 3-5 communications per client
            $numCommunications = rand(3, 5);
            $selectedCommunications = array_slice($sampleCommunications, 0, $numCommunications);

            foreach ($selectedCommunications as $communication) {
                $contact = $contacts->isNotEmpty() ? $contacts->random() : null;

                CommunicationLog::create(array_merge($communication, [
                    'client_id' => $client->id,
                    'user_id' => $users->random()->id,
                    'contact_id' => $contact ? $contact->id : null,
                    'contact_name' => $contact ? $contact->name : 'John Doe',
                    'contact_email' => $contact ? $contact->email : 'john.doe@example.com',
                    'contact_phone' => $contact ? $contact->phone : '+1 (555) 123-4567',
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                ]));
            }
        }

        $this->command->info('Communication logs seeded successfully.');
    }
}
