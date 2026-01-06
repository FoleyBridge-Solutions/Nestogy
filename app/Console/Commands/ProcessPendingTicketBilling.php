<?php

namespace App\Console\Commands;

use App\Domains\Financial\Services\TicketBillingService;
use App\Domains\Ticket\Models\Ticket;
use App\Jobs\ProcessTicketBilling;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Process Pending Ticket Billing Command
 * 
 * Finds closed/resolved tickets that haven't been billed yet and
 * queues them for processing. Designed to catch any tickets that
 * were missed by the automatic event-driven system.
 */
class ProcessPendingTicketBilling extends Command
{
    protected $signature = 'billing:process-pending-tickets 
                            {--limit=100 : Maximum number of tickets to process}
                            {--company= : Process tickets for specific company ID}
                            {--client= : Process tickets for specific client ID}
                            {--dry-run : Show what would be processed without actually processing}
                            {--force : Force billing even if already invoiced}';

    protected $description = 'Process billing for closed/resolved tickets that haven\'t been invoiced yet';

    protected TicketBillingService $billingService;

    public function __construct(TicketBillingService $billingService)
    {
        parent::__construct();
        $this->billingService = $billingService;
    }

    public function handle(): int
    {
        $this->info('Starting pending ticket billing process...');

        if (!config('billing.ticket.enabled', true)) {
            $this->error('Ticket billing is disabled in configuration.');
            return 1;
        }

        $limit = (int) $this->option('limit');
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Build query for pending tickets
        $query = $this->buildPendingTicketsQuery();

        // Apply filters
        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        if ($clientId = $this->option('client')) {
            $query->where('client_id', $clientId);
        }

        // Get total count
        $totalCount = $query->count();
        $this->info("Found {$totalCount} ticket(s) pending billing");

        if ($totalCount === 0) {
            $this->info('No tickets to process. Exiting.');
            return 0;
        }

        // Apply limit
        $tickets = $query->limit($limit)->get();
        $this->info("Processing {$tickets->count()} ticket(s)");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No billing will be processed');
            $this->displayTicketTable($tickets);
            return 0;
        }

        // Process tickets
        $processed = 0;
        $queued = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($tickets->count());
        $bar->start();

        foreach ($tickets as $ticket) {
            try {
                // Check if ticket can be billed
                if (!$force && !$this->billingService->canBillTicket($ticket)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Queue the billing job
                $queue = config('billing.ticket.queue', 'billing');
                ProcessTicketBilling::dispatch($ticket->id, ['force' => $force])
                    ->onQueue($queue);

                $queued++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError processing ticket #{$ticket->number}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('Billing process completed');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Found', $totalCount],
                ['Processed', $tickets->count()],
                ['Queued for Billing', $queued],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($totalCount > $limit) {
            $remaining = $totalCount - $limit;
            $this->warn("Note: {$remaining} ticket(s) remain pending. Run again to process more.");
        }

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Build query for pending tickets
     */
    protected function buildPendingTicketsQuery()
    {
        $statuses = [
            Ticket::STATUS_CLOSED,
            Ticket::STATUS_RESOLVED,
        ];

        return Ticket::query()
            ->with(['client', 'contact', 'timeEntries'])
            ->whereIn('status', $statuses)
            ->where('billable', true)
            ->whereNotNull('client_id')
            ->when(!$this->option('force'), function ($query) {
                // Only unbilled tickets unless force is set
                $query->whereNull('invoice_id');
            })
            ->when(config('billing.ticket.include_unbilled_only', true), function ($query) {
                // Additional check for time entries or contract rates
                $query->where(function ($q) {
                    // Has billable time entries that haven't been billed
                    $q->whereHas('timeEntries', function ($teQuery) {
                        $teQuery->where('billable', true)
                            ->where('is_billed', false);
                    })
                    // OR has client with active contract
                    ->orWhereHas('client.contracts', function ($contractQuery) {
                        $contractQuery->whereIn('status', ['active', 'signed'])
                            ->whereDate('start_date', '<=', now())
                            ->where(function ($dateQuery) {
                                $dateQuery->whereNull('end_date')
                                    ->orWhereDate('end_date', '>=', now());
                            });
                    });
                });
            })
            ->orderBy('closed_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Display table of tickets that would be processed
     */
    protected function displayTicketTable($tickets): void
    {
        $rows = [];

        foreach ($tickets as $ticket) {
            $timeEntries = $ticket->timeEntries()
                ->where('billable', true)
                ->where('is_billed', false)
                ->count();

            $totalHours = $ticket->timeEntries()
                ->where('billable', true)
                ->where('is_billed', false)
                ->sum('hours_worked');

            $rows[] = [
                $ticket->id,
                $ticket->number,
                $ticket->subject,
                $ticket->client->name ?? 'N/A',
                $ticket->status,
                $timeEntries,
                number_format($totalHours, 2),
                $ticket->closed_at?->format('Y-m-d') ?? 'N/A',
            ];
        }

        $this->table(
            ['ID', 'Number', 'Subject', 'Client', 'Status', 'Time Entries', 'Hours', 'Closed'],
            $rows
        );
    }
}
