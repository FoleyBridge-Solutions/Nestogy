<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Ticket\Services\TicketService;
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
    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        TicketService $ticketService,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->ticketService = $ticketService;
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
            // Get tickets needing escalation
            $escalatedTickets = $this->ticketService->checkAndTriggerEscalations($companyId);
            
            $count = $escalatedTickets->count();
            
            if ($count > 0) {
                $this->warn("Found {$count} tickets requiring escalation");
                
                $this->table(
                    ['Ticket #', 'Subject', 'Client', 'Priority', 'Assigned To', 'Hours Overdue'],
                    $escalatedTickets->map(function ($ticket) {
                        $hoursOverdue = 0;
                        if ($ticket->response_deadline && $ticket->response_deadline < now()) {
                            $hoursOverdue = now()->diffInHours($ticket->response_deadline);
                        }
                        
                        return [
                            $ticket->ticket_number,
                            substr($ticket->subject, 0, 40) . '...',
                            $ticket->client->name ?? 'N/A',
                            $ticket->priority,
                            $ticket->assignedTo->name ?? 'Unassigned',
                            $hoursOverdue
                        ];
                    })->toArray()
                );
                
                if (!$dryRun) {
                    // Send escalation notifications
                    foreach ($escalatedTickets as $ticket) {
                        $this->notificationService->notifyTicketEscalated(
                            $ticket,
                            $ticket->assignedTo,
                            $this->getEscalationManager($ticket)
                        );
                    }
                    
                    $this->info('Escalation notifications sent successfully');
                } else {
                    $this->info('Dry run - no escalations triggered');
                }
            } else {
                $this->info('No tickets requiring escalation found');
            }
            
            // Log the check
            Log::info('SLA breach check completed', [
                'escalated_count' => $count,
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