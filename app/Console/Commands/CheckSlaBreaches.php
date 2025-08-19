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
            $breachedTickets = $this->findSlaBreachedTickets($companyId);
            $count = $breachedTickets->count();

            $this->processBreachedTickets($breachedTickets, $count, $dryRun);
            $this->logCheckCompletion($count, $companyId, $dryRun);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            return $this->handleException($e);
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
     * Process breached tickets display and notifications
     */
    private function processBreachedTickets($breachedTickets, $count, $dryRun)
    {
        if ($count === 0) {
            $this->info('No tickets with SLA breaches found');
            return;
        }

        $this->warn("Found {$count} tickets with SLA breaches");
        $this->displayBreachedTicketsTable($breachedTickets);
        $this->handleNotifications($breachedTickets, $dryRun);
    }

    /**
     * Display table of breached tickets
     */
    private function displayBreachedTicketsTable($breachedTickets)
    {
        $tableData = $breachedTickets->map(function ($ticket) {
            return $this->buildTicketTableRow($ticket);
        })->toArray();

        $this->table(
            ['Ticket #', 'Subject', 'Client', 'Priority', 'SLA Status', 'Hours Overdue'],
            $tableData
        );
    }

    /**
     * Build table row data for a ticket
     */
    private function buildTicketTableRow($ticket)
    {
        $slaInfo = $this->ticketService->getTicketSlaInfo($ticket);
        [$slaStatus, $hoursOverdue] = $this->extractSlaStatusAndOverdue($slaInfo);

        return [
            $ticket->prefix . $ticket->number,
            substr($ticket->subject, 0, 40) . '...',
            $ticket->client->name ?? 'N/A',
            $ticket->priority,
            $slaStatus,
            $hoursOverdue
        ];
    }

    /**
     * Extract SLA status and hours overdue from SLA info
     */
    private function extractSlaStatusAndOverdue($slaInfo)
    {
        $hoursOverdue = 0;
        $slaStatus = 'OK';

        if ($slaInfo['is_response_breached'] ?? false) {
            [$slaStatus, $hoursOverdue] = $this->calculateBreachDetails(
                $slaInfo['response_deadline'] ?? null,
                'Response Breached'
            );
        } elseif ($slaInfo['is_resolution_breached'] ?? false) {
            [$slaStatus, $hoursOverdue] = $this->calculateBreachDetails(
                $slaInfo['resolution_deadline'] ?? null,
                'Resolution Breached'
            );
        } elseif ($slaInfo['is_approaching_response_breach'] ?? false) {
            $slaStatus = 'Approaching Breach';
        }

        return [$slaStatus, $hoursOverdue];
    }

    /**
     * Calculate breach details from deadline
     */
    private function calculateBreachDetails($deadline, $status)
    {
        if (!$deadline) {
            return [$status, 0];
        }

        return [$status, now()->diffInHours($deadline)];
    }

    /**
     * Handle notifications for breached tickets
     */
    private function handleNotifications($breachedTickets, $dryRun)
    {
        if (!$dryRun) {
            foreach ($breachedTickets as $ticket) {
                $this->sendSlaNotifications($ticket);
            }
            $this->info('SLA breach notifications sent successfully');
        } else {
            $this->info('Dry run - no notifications sent');
        }
    }

    /**
     * Log completion of the check
     */
    private function logCheckCompletion($count, $companyId, $dryRun)
    {
        Log::info('SLA breach check completed', [
            'breached_count' => $count,
            'company_id' => $companyId,
            'dry_run' => $dryRun
        ]);
    }

    /**
     * Handle exceptions during execution
     */
    private function handleException(\Exception $e)
    {
        $this->error('Failed to check SLA breaches: ' . $e->getMessage());
        Log::error('SLA breach check failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return Command::FAILURE;
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
