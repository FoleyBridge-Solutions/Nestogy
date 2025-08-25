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

        $companyId = $this->option('company');
        $contractId = $this->option('contract');
        $dryRun = $this->option('dry-run');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        if ($dryRun) {
            $this->warn('Running in DRY RUN mode - no invoices will be created');
        }

        try {
            if ($contractId) {
                // Generate for specific contract
                $contract = \App\Domains\Contract\Models\Contract::find($contractId);

                if (!$contract) {
                    $this->error('Contract not found');
                    return Command::FAILURE;
                }

                $this->info("Generating invoice for contract: {$contract->contract_number}");

                $invoice = $this->billingService->generateInvoiceFromContract($contract, $dryRun);

                if (!$dryRun && $invoice) {
                    $this->info("Invoice {$invoice->invoice_number} generated successfully");
                    $this->displayInvoiceSummary([$invoice]);
                } elseif ($dryRun) {
                    $this->info('Invoice preview generated (not saved)');
                }

            } else {
                // Generate bulk invoices
                $this->info('Generating bulk invoices for all due contracts...');

                $result = $this->billingService->generateBulkInvoices($dryRun, $companyId);

                $this->info("Processing complete!");
                $this->info("Contracts processed: {$result['processed']}");
                $this->info("Invoices generated: {$result['generated']}");

                if ($result['failed'] > 0) {
                    $this->warn("Failed generations: {$result['failed']}");

                    if (!empty($result['errors'])) {
                        $this->error('Errors encountered:');
                        foreach ($result['errors'] as $error) {
                            $this->error(" - {$error}");
                        }
                    }
                }

                if (!$dryRun && !empty($result['invoices'])) {
                    $this->displayInvoiceSummary($result['invoices']);

                    // Send notifications for generated invoices
                    foreach ($result['invoices'] as $invoice) {
                        $this->notificationService->notifyInvoiceGenerated($invoice);
                    }
                }
            }

            // Log the generation
            Log::info('Recurring invoice generation completed', [
                'company_id' => $companyId,
                'contract_id' => $contractId,
                'dry_run' => $dryRun,
                'date' => $date->toDateString(),
                'result' => $result ?? null
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to generate recurring invoices: ' . $e->getMessage());
            Log::error('Recurring invoice generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
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
