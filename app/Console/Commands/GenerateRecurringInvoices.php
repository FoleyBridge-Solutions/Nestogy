<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Financial\Services\RecurringBillingService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-recurring
                            {--company= : Generate for specific company ID}
                            {--contract= : Generate for specific contract ID}
                            {--dry-run : Preview invoices without creating them}
                            {--date= : Generate for specific date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring invoices from active contracts';

    protected RecurringBillingService $billingService;
    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        RecurringBillingService $billingService,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->billingService = $billingService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting recurring invoice generation...');
        
        $options = $this->parseOptions();
        
        if ($options['dryRun']) {
            $this->warn('Running in DRY RUN mode - no invoices will be created');
        }
        
        try {
            $result = $options['contractId'] 
                ? $this->processSingleContract($options)
                : $this->processBulkInvoices($options);
            
            $this->logGeneration($options, $result);
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Parse command options into structured array
     */
    private function parseOptions(): array
    {
        return [
            'companyId' => $this->option('company'),
            'contractId' => $this->option('contract'),
            'dryRun' => $this->option('dry-run'),
            'date' => $this->option('date') ? Carbon::parse($this->option('date')) : now()
        ];
    }

    /**
     * Process single contract invoice generation
     */
    private function processSingleContract(array $options): ?array
    {
        $contract = \App\Domains\Contract\Models\Contract::find($options['contractId']);
        
        if (!$contract) {
            throw new \InvalidArgumentException('Contract not found');
        }
        
        $this->info("Generating invoice for contract: {$contract->contract_number}");
        $invoice = $this->billingService->generateInvoiceFromContract($contract, $options['dryRun']);
        
        $this->displaySingleContractResult($invoice, $options['dryRun']);
        
        return ['invoice' => $invoice];
    }

    /**
     * Display result for single contract processing
     */
    private function displaySingleContractResult($invoice, bool $dryRun): void
    {
        if ($dryRun) {
            $this->info('Invoice preview generated (not saved)');
            return;
        }
        
        if ($invoice) {
            $this->info("Invoice {$invoice->invoice_number} generated successfully");
            $this->displayInvoiceSummary([$invoice]);
        }
    }

    /**
     * Process bulk invoice generation
     */
    private function processBulkInvoices(array $options): array
    {
        $this->info('Generating bulk invoices for all due contracts...');
        
        $result = $this->billingService->generateBulkInvoices(
            $options['dryRun'], 
            $options['companyId']
        );
        
        $this->displayBulkResults($result);
        $this->handleBulkErrors($result);
        $this->processGeneratedInvoices($result, $options['dryRun']);
        
        return $result;
    }

    /**
     * Display bulk processing results
     */
    private function displayBulkResults(array $result): void
    {
        $this->info("Processing complete!");
        $this->info("Contracts processed: {$result['processed']}");
        $this->info("Invoices generated: {$result['generated']}");
    }

    /**
     * Handle bulk processing errors
     */
    private function handleBulkErrors(array $result): void
    {
        if ($result['failed'] <= 0) {
            return;
        }
        
        $this->warn("Failed generations: {$result['failed']}");
        
        if (empty($result['errors'])) {
            return;
        }
        
        $this->error('Errors encountered:');
        foreach ($result['errors'] as $error) {
            $this->error(" - {$error}");
        }
    }

    /**
     * Process successfully generated invoices
     */
    private function processGeneratedInvoices(array $result, bool $dryRun): void
    {
        if ($dryRun || empty($result['invoices'])) {
            return;
        }
        
        $this->displayInvoiceSummary($result['invoices']);
        $this->sendInvoiceNotifications($result['invoices']);
    }

    /**
     * Send notifications for generated invoices
     */
    private function sendInvoiceNotifications(array $invoices): void
    {
        foreach ($invoices as $invoice) {
            $this->notificationService->notifyInvoiceGenerated($invoice);
        }
    }

    /**
     * Log the generation process
     */
    private function logGeneration(array $options, ?array $result): void
    {
        Log::info('Recurring invoice generation completed', [
            'company_id' => $options['companyId'],
            'contract_id' => $options['contractId'],
            'dry_run' => $options['dryRun'],
            'date' => $options['date']->toDateString(),
            'result' => $result
        ]);
    }

    /**
     * Handle command errors
     */
    private function handleError(\Exception $e): int
    {
        $this->error('Failed to generate recurring invoices: ' . $e->getMessage());
        
        Log::error('Recurring invoice generation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return Command::FAILURE;
    }

    /**
     * Display invoice summary table
     */
    private function displayInvoiceSummary(array $invoices)
    {
        if (empty($invoices)) {
            return;
        }

        $this->newLine();
        $this->info('Generated Invoices Summary:');

        $tableData = collect($invoices)->map(function ($invoice) {
            return [
                $invoice->invoice_number,
                $invoice->client->name ?? 'N/A',
                $invoice->invoice_date->format('Y-m-d'),
                $invoice->due_date->format('Y-m-d'),
                '$' . number_format($invoice->total, 2),
                $invoice->status
            ];
        })->toArray();

        $this->table(
            ['Invoice #', 'Client', 'Invoice Date', 'Due Date', 'Total', 'Status'],
            $tableData
        );

        $totalAmount = collect($invoices)->sum('total');
        $this->info('Total Amount: $' . number_format($totalAmount, 2));
    }
}
