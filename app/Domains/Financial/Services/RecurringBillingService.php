<?php

namespace App\Domains\Financial\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Quote;
use App\Domains\Financial\Models\RecurringInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringBillingService
{
    protected RecurringInvoiceService $recurringInvoiceService;
    protected InvoiceService $invoiceService;

    public function __construct(
        RecurringInvoiceService $recurringInvoiceService,
        InvoiceService $invoiceService
    ) {
        $this->recurringInvoiceService = $recurringInvoiceService;
        $this->invoiceService = $invoiceService;
    }

    public function generateInvoiceFromContract(Contract $contract, bool $dryRun = false): ?Invoice
    {
        Log::info('Generating invoice from contract', [
            'contract_id' => $contract->id,
            'dry_run' => $dryRun,
        ]);

        if ($dryRun) {
            return null;
        }

        return DB::transaction(function () use ($contract) {
            $invoiceData = [
                'company_id' => $contract->company_id,
                'client_id' => $contract->client_id,
                'contract_id' => $contract->id,
                'title' => "Invoice for {$contract->title}",
                'description' => $contract->description,
                'due_date' => now()->addDays(30),
                'status' => 'draft',
            ];

            return $this->invoiceService->create($invoiceData);
        });
    }

    public function generateBulkInvoices(bool $dryRun = false): array
    {
        Log::info('Generating bulk invoices', ['dry_run' => $dryRun]);

        $recurringInvoices = RecurringInvoice::where('is_active', true)
            ->where('next_invoice_date', '<=', now())
            ->get();

        $results = [
            'success' => [],
            'failed' => [],
            'total' => $recurringInvoices->count(),
        ];

        if ($dryRun) {
            $results['dry_run'] = true;
            return $results;
        }

        foreach ($recurringInvoices as $recurringInvoice) {
            try {
                $invoice = $this->recurringInvoiceService->generateInvoiceFromRecurring($recurringInvoice);
                $results['success'][] = [
                    'recurring_id' => $recurringInvoice->id,
                    'invoice_id' => $invoice->id,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'recurring_id' => $recurringInvoice->id,
                    'error' => $e->getMessage(),
                ];
                Log::error('Failed to generate invoice from recurring', [
                    'recurring_id' => $recurringInvoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    public function retryFailedPayment(Invoice $invoice): array
    {
        Log::info('Retrying failed payment', ['invoice_id' => $invoice->id]);

        if ($invoice->status === 'paid') {
            return [
                'success' => false,
                'message' => 'Invoice is already paid',
            ];
        }

        return [
            'success' => true,
            'message' => 'Payment retry initiated',
            'invoice_id' => $invoice->id,
        ];
    }

    public function generateBillingForecast(int $months = 12): array
    {
        Log::info('Generating billing forecast', ['months' => $months]);

        $recurringInvoices = RecurringInvoice::where('is_active', true)->get();
        $forecast = [];

        for ($i = 0; $i < $months; $i++) {
            $month = now()->addMonths($i)->format('Y-m');
            $forecast[$month] = [
                'month' => $month,
                'expected_revenue' => 0,
                'invoice_count' => 0,
            ];

            foreach ($recurringInvoices as $recurring) {
                if ($this->shouldInvoiceInMonth($recurring, $i)) {
                    $forecast[$month]['expected_revenue'] += $recurring->amount;
                    $forecast[$month]['invoice_count']++;
                }
            }
        }

        return $forecast;
    }

    public function createFromQuote(Quote $quote, array $recurringData): RecurringInvoice
    {
        Log::info('Creating recurring billing from quote', [
            'quote_id' => $quote->id,
            'recurring_data' => $recurringData,
        ]);

        return DB::transaction(function () use ($quote, $recurringData) {
            $recurring = RecurringInvoice::create([
                'company_id' => $quote->company_id,
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'title' => $recurringData['title'] ?? "Recurring billing for {$quote->title}",
                'description' => $recurringData['description'] ?? $quote->description,
                'billing_frequency' => $recurringData['frequency'] ?? 'monthly',
                'amount' => $quote->total,
                'start_date' => Carbon::parse($recurringData['start_date'] ?? now()),
                'next_invoice_date' => Carbon::parse($recurringData['start_date'] ?? now()),
                'is_active' => true,
            ]);

            return $recurring;
        });
    }

    protected function shouldInvoiceInMonth(RecurringInvoice $recurring, int $monthsFromNow): bool
    {
        $targetMonth = now()->addMonths($monthsFromNow);
        $nextInvoiceDate = Carbon::parse($recurring->next_invoice_date);

        if ($recurring->end_date && $targetMonth->greaterThan($recurring->end_date)) {
            return false;
        }

        switch ($recurring->billing_frequency) {
            case 'monthly':
                return true;
            case 'quarterly':
                return $monthsFromNow % 3 === 0;
            case 'semi-annually':
                return $monthsFromNow % 6 === 0;
            case 'annually':
                return $monthsFromNow % 12 === 0;
            default:
                return false;
        }
    }
}
