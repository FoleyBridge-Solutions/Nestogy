<?php

namespace App\Console\Commands;

use App\Domains\Ticket\Models\Ticket;
use App\Mail\Digests\ManagerDaily;
use App\Models\TicketRating;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendManagerDigest extends Command
{
    protected $signature = 'digest:send-manager';
    protected $description = 'Send daily digest email to managers';

    public function handle()
    {
        $this->info('Sending manager daily digests...');

        $managers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager']);
        })->get();

        foreach ($managers as $manager) {
            // Check if manager has digest enabled
            $prefs = \App\Models\NotificationPreference::getOrCreateForUser($manager);
            
            if (!$prefs->manager_digest_enabled) {
                continue;
            }

            $data = $this->collectDigestData($manager->company_id);

            try {
                Mail::to($manager->email)->send(new ManagerDaily($manager, $data));
                $this->info("✓ Sent digest to {$manager->name}");
            } catch (\Exception $e) {
                $this->error("✗ Failed to send to {$manager->name}: {$e->getMessage()}");
            }
        }

        $this->info('Manager digests sent!');
        return 0;
    }

    protected function collectDigestData($companyId)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        return [
            // Open tickets summary
            'open_tickets' => Ticket::where('company_id', $companyId)
                ->whereIn('status', ['Open', 'In Progress', 'Awaiting Customer'])
                ->count(),

            // Tickets by priority
            'critical_tickets' => Ticket::where('company_id', $companyId)
                ->where('priority', 'Critical')
                ->whereIn('status', ['Open', 'In Progress'])
                ->count(),

            'high_priority_tickets' => Ticket::where('company_id', $companyId)
                ->where('priority', 'High')
                ->whereIn('status', ['Open', 'In Progress'])
                ->count(),

            // Unassigned tickets
            'unassigned_tickets' => Ticket::where('company_id', $companyId)
                ->whereNull('assigned_to')
                ->whereIn('status', ['Open', 'In Progress'])
                ->count(),

            // SLA breaches
            'sla_breaches' => $this->getSLABreaches($companyId),

            // Completed yesterday
            'completed_yesterday' => Ticket::where('company_id', $companyId)
                ->whereIn('status', ['Resolved', 'Closed'])
                ->whereDate('updated_at', $yesterday)
                ->with('assignee:id,name')
                ->get(['id', 'number', 'subject', 'assigned_to', 'updated_at']),

            // New tickets yesterday
            'new_tickets_yesterday' => Ticket::where('company_id', $companyId)
                ->whereDate('created_at', $yesterday)
                ->count(),

            // Satisfaction scores
            'avg_satisfaction' => TicketRating::whereHas('ticket', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
                ->avg('rating'),

            // Top performers (by resolved tickets)
            'top_performers' => User::where('company_id', $companyId)
                ->whereHas('assignedTickets', function ($q) use ($yesterday) {
                    $q->whereIn('status', ['Resolved', 'Closed'])
                      ->whereDate('updated_at', $yesterday);
                })
                ->withCount([
                    'assignedTickets as resolved_yesterday' => function ($q) use ($yesterday) {
                        $q->whereIn('status', ['Resolved', 'Closed'])
                          ->whereDate('updated_at', $yesterday);
                    }
                ])
                ->orderByDesc('resolved_yesterday')
                ->take(3)
                ->get(['id', 'name']),

            'generated_at' => now(),
        ];
    }

    protected function getSLABreaches($companyId)
    {
        $slaHours = [
            'Critical' => 1,
            'High' => 4,
            'Medium' => 24,
            'Low' => 48,
        ];

        return Ticket::where('company_id', $companyId)
            ->whereIn('status', ['Open', 'In Progress', 'Awaiting Customer'])
            ->get()
            ->filter(function ($ticket) use ($slaHours) {
                $hours = $slaHours[$ticket->priority] ?? 24;
                $deadline = $ticket->created_at->addHours($hours);
                return Carbon::now()->gt($deadline);
            })
            ->values();
    }
}
