<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Ticket\Services\TicketService;
use App\Domains\Ticket\Services\SLAService;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Client;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CheckSlaBreaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:check-sla-breaches 
                            {--company= : Check for specific company ID}
                            {--dry-run : Run without triggering escalations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for SLA breaches and trigger escalations';

    protected TicketService $ticketService;
    protected SLAService $slaService;
    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        TicketService $ticketService,
        SLAService $slaService,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->ticketService = $ticketService;
        $this->slaService = $slaService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SLA breach check...');
        
        $companyId = $this->option('company');
        $dryRun = $this->option('dry-run');
        
        try {
            // Get tickets that need SLA checking
            $breachedTickets = $this->findSlaBreachedTickets($companyId);
            
            $count = $breachedTickets->count();
            
            if ($count > 0) {
                $this->warn("Found {$count} tickets with SLA breaches");
                
                $this->table(
                    ['Ticket #', 'Subject', 'Client', 'Priority', 'SLA Status', 'Hours Overdue'],
                    $breachedTickets->map(function ($ticket) {
                        $slaInfo = $this->ticketService->getTicketSlaInfo($ticket);
                        $hoursOverdue = 0;
                        $slaStatus = 'OK';
                        
                        if ($slaInfo['is_response_breached'] ?? false) {
                            $responseDeadline = $slaInfo['response_deadline'] ?? null;
                            if ($responseDeadline) {
                                $hoursOverdue = now()->diffInHours($responseDeadline);
                                $slaStatus = 'Response Breached';
                            }
                        } elseif ($slaInfo['is_resolution_breached'] ?? false) {
                            $resolutionDeadline = $slaInfo['resolution_deadline'] ?? null;
                            if ($resolutionDeadline) {
                                $hoursOverdue = now()->diffInHours($resolutionDeadline);
                                $slaStatus = 'Resolution Breached';
                            }
                        } elseif ($slaInfo['is_approaching_response_breach'] ?? false) {
                            $slaStatus = 'Approaching Breach';
                        }
                        
                        return [
                            $ticket->prefix . $ticket->number,
                            substr($ticket->subject, 0, 40) . '...',
                            $ticket->client->name ?? 'N/A',
                            $ticket->priority,
                            $slaStatus,
                            $hoursOverdue
                        ];
                    })->toArray()
                );
                
                if (!$dryRun) {
                    // Send escalation notifications
                    foreach ($breachedTickets as $ticket) {
                        $this->sendSlaNotifications($ticket);
                    }
                    
                    $this->info('SLA breach notifications sent successfully');
                } else {
                    $this->info('Dry run - no notifications sent');
                }
            } else {
                $this->info('No tickets with SLA breaches found');
            }
            
            // Log the check
            Log::info('SLA breach check completed', [
                'breached_count' => $count,
                'company_id' => $companyId,
                'dry_run' => $dryRun
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to check SLA breaches: ' . $e->getMessage());
            Log::error('SLA breach check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Find tickets with SLA breaches
     */
    private function findSlaBreachedTickets($companyId = null)
    {
        $query = Ticket::with(['client', 'assignedTo'])
            ->whereIn('status', ['Open', 'In Progress'])
            ->whereHas('client');
            
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        $tickets = $query->get();
        
        // Filter tickets that have SLA breaches or are approaching breach
        return $tickets->filter(function ($ticket) {
            if (!$ticket->client) {
                return false;
            }
            
            return $this->ticketService->isResponseSlaBreached($ticket) ||
                   $this->ticketService->isResolutionSlaBreached($ticket) ||
                   $this->ticketService->isSlaApproachingBreach($ticket, 'response');
        });
    }
    
    /**
     * Send SLA notifications for a ticket
     */
    private function sendSlaNotifications(Ticket $ticket)
    {
        $slaInfo = $this->ticketService->getTicketSlaInfo($ticket);
        $sla = $slaInfo['sla'] ?? null;
        
        if (!$sla || !$sla->notify_on_breach) {
            return;
        }
        
        // Determine notification type
        $notificationType = 'sla_warning';
        if ($slaInfo['is_response_breached'] ?? false) {
            $notificationType = 'sla_response_breach';
        } elseif ($slaInfo['is_resolution_breached'] ?? false) {
            $notificationType = 'sla_resolution_breach';
        }
        
        // Send notifications
        try {
            $this->notificationService->notifyTicketSlaIssue(
                $ticket,
                $notificationType,
                $slaInfo
            );
            
            // Also notify escalation manager if configured
            if ($sla->escalation_enabled) {
                $manager = $this->getEscalationManager($ticket);
                if ($manager) {
                    $this->notificationService->notifyTicketEscalated(
                        $ticket,
                        $ticket->assignedTo,
                        $manager
                    );
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send SLA notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the manager for escalation
     */
    private function getEscalationManager($ticket)
    {
        // Logic to determine escalation manager
        // Could be based on department, client tier, etc.
        return \App\Models\User::where('company_id', $ticket->company_id)
            ->where('role', 'manager')
            ->first();
    }
}