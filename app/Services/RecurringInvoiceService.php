<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Models\ContractMilestone;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * RecurringInvoiceService
 * 
 * Manages automated recurring invoice generation based on active contracts,
 * handles various billing frequencies, milestone-based invoicing, and contract compliance.
 */
class RecurringInvoiceService
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Create recurring invoice schedule from contract
     */
    public function createRecurringScheduleFromContract(Contract $contract, array $scheduleData): RecurringInvoice
    {
        return DB::transaction(function () use ($contract, $scheduleData) {
            Log::info('Creating recurring invoice schedule from contract', [
                'contract_id' => $contract->id,
                'schedule_data' => $scheduleData
            ]);

            $recurringInvoice = RecurringInvoice::create([
                'company_id' => $contract->company_id,
                'client_id' => $contract->client_id,
                'contract_id' => $contract->id,
                'title' => $scheduleData['title'] ?? $contract->title . ' - Recurring Billing',
                'description' => $scheduleData['description'] ?? 'Automated recurring invoice from contract',
                'billing_frequency' => $scheduleData['billing_frequency'], // monthly, quarterly, annually, etc.
                'amount' => $scheduleData['amount'] ?? $contract->contract_value,
                'start_date' => Carbon::parse($scheduleData['start_date']),
                'end_date' => isset($scheduleData['end_date']) ? Carbon::parse($scheduleData['end_date']) : $contract->end_date,
                'next_invoice_date' => $this->calculateNextInvoiceDate(
                    Carbon::parse($scheduleData['start_date']), 
                    $scheduleData['billing_frequency']
                ),
                'invoice_due_days' => $scheduleData['invoice_due_days'] ?? 30,
                'auto_generate' => $scheduleData['auto_generate'] ?? true,
                'auto_send' => $scheduleData['auto_send'] ?? false,
                'payment_terms' => $scheduleData['payment_terms'] ?? $contract->payment_terms,
                'tax_rate' => $scheduleData['tax_rate'] ?? 0,
                'discount_percentage' => $scheduleData['discount_percentage'] ?? 0,
                'billing_cycle_day' => $scheduleData['billing_cycle_day'] ?? null,
                'proration_enabled' => $scheduleData['proration_enabled'] ?? true,
                'escalation_percentage' => $scheduleData['escalation_percentage'] ?? 0,
                'escalation_frequency' => $scheduleData['escalation_frequency'] ?? 'annual',
                'last_escalation_date' => null,
                'status' => 'active',
                'invoices_generated' => 0,
                'total_revenue_generated' => 0,
                'metadata' => [
                    'contract_type' => $contract->contract_type,
                    'service_details' => $contract->service_details ?? [],
                    'billing_preferences' => $scheduleData['billing_preferences'] ?? [],
                ],
                'created_by' => auth()->id(),
            ]);

            // Copy contract line items if specified
            if ($scheduleData['copy_contract_items'] ?? false) {
                $this->copyContractItemsToRecurring($contract, $recurringInvoice);
            }

            // Create milestone-based schedule if applicable
            if ($scheduleData['milestone_based'] ?? false) {
                $this->createMilestoneBasedSchedule($contract, $recurringInvoice);
            }

            $contract->update(['has_recurring_billing' => true]);

            Log::info('Recurring invoice schedule created', [
                'recurring_invoice_id' => $recurringInvoice->id,
                'contract_id' => $contract->id
            ]);

            return $recurringInvoice;
        });
    }

    /**
     * Generate due invoices from recurring schedules
     */
    public function generateDueInvoices(Carbon $asOfDate = null): Collection
    {
        $asOfDate = $asOfDate ?? now();
        $generatedInvoices = collect();

        $dueRecurringInvoices = RecurringInvoice::with(['contract', 'client'])
            ->where('status', 'active')
            ->where('auto_generate', true)
            ->where('next_invoice_date', '<=', $asOfDate)
            ->where(function ($query) use ($asOfDate) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $asOfDate);
            })
            ->get();

        Log::info('Processing due recurring invoices', [
            'count' => $dueRecurringInvoices->count(),
            'as_of_date' => $asOfDate->toDateString()
        ]);

        foreach ($dueRecurringInvoices as $recurring) {
            try {
                $invoice = $this->generateInvoiceFromRecurring($recurring, $asOfDate);
                if ($invoice) {
                    $generatedInvoices->push($invoice);
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate recurring invoice', [
                    'recurring_invoice_id' => $recurring->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $generatedInvoices;
    }

    /**
     * Generate single invoice from recurring schedule
     */
    public function generateInvoiceFromRecurring(RecurringInvoice $recurring, Carbon $invoiceDate = null): ?Invoice
    {
        $invoiceDate = $invoiceDate ?? now();

        return DB::transaction(function () use ($recurring, $invoiceDate) {
            // Validate contract is still active
            if (!$recurring->contract->isActive()) {
                Log::warning('Skipping recurring invoice generation - contract not active', [
                    'recurring_invoice_id' => $recurring->id,
                    'contract_id' => $recurring->contract_id,
                    'contract_status' => $recurring->contract->status
                ]);
                return null;
            }

            // Calculate billing period
            $billingPeriod = $this->calculateBillingPeriod($recurring, $invoiceDate);
            
            // Check for proration if needed
            $amount = $this->calculateInvoiceAmount($recurring, $billingPeriod, $invoiceDate);

            // Apply escalation if due
            $amount = $this->applyEscalationIfDue($recurring, $amount, $invoiceDate);

            $invoiceData = [
                'client_id' => $recurring->client_id,
                'contract_id' => $recurring->contract_id,
                'recurring_invoice_id' => $recurring->id,
                'date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays($recurring->invoice_due_days),
                'scope' => $recurring->title,
                'description' => $this->generateInvoiceDescription($recurring, $billingPeriod),
                'amount' => $amount,
                'tax_rate' => $recurring->tax_rate,
                'discount_percentage' => $recurring->discount_percentage,
                'payment_terms' => $recurring->payment_terms,
                'status' => 'Draft',
                'billing_period_start' => $billingPeriod['start'],
                'billing_period_end' => $billingPeriod['end'],
                'metadata' => [
                    'generated_from_recurring' => true,
                    'recurring_invoice_id' => $recurring->id,
                    'billing_cycle' => $recurring->invoices_generated + 1,
                    'escalation_applied' => $amount > $recurring->amount,
                ],
            ];

            $invoice = $this->invoiceService->createInvoice($recurring->client, $invoiceData);

            // Add line items from recurring schedule
            $this->addRecurringItemsToInvoice($recurring, $invoice, $billingPeriod);

            // Update recurring schedule
            $this->updateRecurringScheduleAfterGeneration($recurring, $invoice, $invoiceDate);

            // Auto-send if configured
            if ($recurring->auto_send) {
                $this->sendGeneratedInvoice($invoice);
            }

            Log::info('Recurring invoice generated successfully', [
                'invoice_id' => $invoice->id,
                'recurring_invoice_id' => $recurring->id,
                'amount' => $amount,
                'billing_period' => $billingPeriod
            ]);

            return $invoice;
        });
    }

    /**
     * Generate milestone-based invoice
     */
    public function generateMilestoneInvoice(ContractMilestone $milestone): ?Invoice
    {
        if (!$milestone->is_billable || $milestone->status !== 'completed') {
            return null;
        }

        return DB::transaction(function () use ($milestone) {
            $contract = $milestone->contract;
            
            $invoiceData = [
                'client_id' => $contract->client_id,
                'contract_id' => $contract->id,
                'contract_milestone_id' => $milestone->id,
                'date' => now(),
                'due_date' => now()->addDays(30),
                'scope' => $milestone->title,
                'description' => "Milestone completion: {$milestone->title}",
                'amount' => $milestone->amount,
                'status' => 'Draft',
                'metadata' => [
                    'milestone_based' => true,
                    'milestone_id' => $milestone->id,
                ],
            ];

            $invoice = $this->invoiceService->createInvoice($contract->client, $invoiceData);

            // Mark milestone as invoiced
            $milestone->update([
                'is_invoiced' => true,
                'invoice_id' => $invoice->id,
                'invoiced_at' => now(),
            ]);

            Log::info('Milestone-based invoice generated', [
                'invoice_id' => $invoice->id,
                'milestone_id' => $milestone->id,
                'amount' => $milestone->amount
            ]);

            return $invoice;
        });
    }

    /**
     * Pause recurring invoice schedule
     */
    public function pauseRecurringSchedule(RecurringInvoice $recurring, string $reason = null): bool
    {
        $recurring->update([
            'status' => 'paused',
            'paused_at' => now(),
            'pause_reason' => $reason,
        ]);

        Log::info('Recurring invoice schedule paused', [
            'recurring_invoice_id' => $recurring->id,
            'reason' => $reason
        ]);

        return true;
    }

    /**
     * Resume recurring invoice schedule
     */
    public function resumeRecurringSchedule(RecurringInvoice $recurring): bool
    {
        // Recalculate next invoice date if needed
        $nextDate = $this->calculateNextInvoiceDate(
            $recurring->next_invoice_date ?? now(),
            $recurring->billing_frequency
        );

        $recurring->update([
            'status' => 'active',
            'next_invoice_date' => $nextDate,
            'paused_at' => null,
            'pause_reason' => null,
        ]);

        Log::info('Recurring invoice schedule resumed', [
            'recurring_invoice_id' => $recurring->id,
            'next_invoice_date' => $nextDate->toDateString()
        ]);

        return true;
    }

    /**
     * Process contract-based recurring billing
     */
    public function processContractRecurringBilling(): array
    {
        $results = [
            'contracts_processed' => 0,
            'invoices_generated' => 0,
            'errors' => [],
            'summary' => []
        ];

        // Get contracts with active recurring billing
        $activeContracts = Contract::with(['recurringInvoices', 'client'])
            ->where('status', 'active')
            ->where('has_recurring_billing', true)
            ->get();

        Log::info('Processing contract recurring billing', [
            'active_contracts_count' => $activeContracts->count()
        ]);

        foreach ($activeContracts as $contract) {
            try {
                $contractResults = $this->processContractRecurring($contract);
                $results['contracts_processed']++;
                $results['invoices_generated'] += $contractResults['invoices_generated'];
                $results['summary'][$contract->id] = $contractResults;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Contract recurring billing processing failed', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Get recurring invoice statistics
     */
    public function getRecurringInvoiceStatistics(int $companyId): array
    {
        $stats = [
            'active_schedules' => RecurringInvoice::where('company_id', $companyId)
                ->where('status', 'active')->count(),
            'paused_schedules' => RecurringInvoice::where('company_id', $companyId)
                ->where('status', 'paused')->count(),
            'total_recurring_revenue' => RecurringInvoice::where('company_id', $companyId)
                ->sum('total_revenue_generated'),
            'upcoming_invoices' => RecurringInvoice::where('company_id', $companyId)
                ->where('status', 'active')
                ->where('next_invoice_date', '<=', now()->addDays(7))
                ->count(),
            'overdue_generations' => RecurringInvoice::where('company_id', $companyId)
                ->where('status', 'active')
                ->where('next_invoice_date', '<', now())
                ->count(),
        ];

        // Group by frequency
        $byFrequency = RecurringInvoice::where('company_id', $companyId)
            ->where('status', 'active')
            ->groupBy('billing_frequency')
            ->selectRaw('billing_frequency, COUNT(*) as count, SUM(amount) as total_amount')
            ->get()
            ->keyBy('billing_frequency');

        $stats['by_frequency'] = $byFrequency->toArray();

        return $stats;
    }

    /**
     * Helper methods
     */

    protected function calculateNextInvoiceDate(Carbon $baseDate, string $frequency): Carbon
    {
        $nextDate = $baseDate->copy();

        switch ($frequency) {
            case 'weekly':
                return $nextDate->addWeek();
            case 'bi_weekly':
                return $nextDate->addWeeks(2);
            case 'monthly':
                return $nextDate->addMonth();
            case 'quarterly':
                return $nextDate->addMonths(3);
            case 'semi_annually':
                return $nextDate->addMonths(6);
            case 'annually':
                return $nextDate->addYear();
            case 'bi_annually':
                return $nextDate->addYears(2);
            default:
                throw new \InvalidArgumentException("Unsupported billing frequency: {$frequency}");
        }
    }

    protected function calculateBillingPeriod(RecurringInvoice $recurring, Carbon $invoiceDate): array
    {
        $periodStart = $recurring->next_invoice_date->copy();
        $periodEnd = $this->calculateNextInvoiceDate($periodStart, $recurring->billing_frequency)->subDay();

        return [
            'start' => $periodStart,
            'end' => $periodEnd,
        ];
    }

    protected function calculateInvoiceAmount(RecurringInvoice $recurring, array $billingPeriod, Carbon $invoiceDate): float
    {
        $baseAmount = $recurring->amount;

        // Apply proration if enabled and needed
        if ($recurring->proration_enabled) {
            $baseAmount = $this->applyProrationIfNeeded($recurring, $baseAmount, $billingPeriod, $invoiceDate);
        }

        // Apply discount
        if ($recurring->discount_percentage > 0) {
            $baseAmount = $baseAmount * (1 - $recurring->discount_percentage / 100);
        }

        return round($baseAmount, 2);
    }

    protected function applyEscalationIfDue(RecurringInvoice $recurring, float $amount, Carbon $invoiceDate): float
    {
        if (!$recurring->escalation_percentage || $recurring->escalation_percentage <= 0) {
            return $amount;
        }

        $escalationDue = false;
        $lastEscalation = $recurring->last_escalation_date;

        if (!$lastEscalation) {
            // First escalation check
            $contractStart = $recurring->start_date;
            $monthsSinceStart = $contractStart->diffInMonths($invoiceDate);
            
            if ($recurring->escalation_frequency === 'annual' && $monthsSinceStart >= 12) {
                $escalationDue = true;
            } elseif ($recurring->escalation_frequency === 'biennial' && $monthsSinceStart >= 24) {
                $escalationDue = true;
            }
        } else {
            // Check since last escalation
            $monthsSinceEscalation = $lastEscalation->diffInMonths($invoiceDate);
            
            if ($recurring->escalation_frequency === 'annual' && $monthsSinceEscalation >= 12) {
                $escalationDue = true;
            } elseif ($recurring->escalation_frequency === 'biennial' && $monthsSinceEscalation >= 24) {
                $escalationDue = true;
            }
        }

        if ($escalationDue) {
            $escalatedAmount = $amount * (1 + $recurring->escalation_percentage / 100);
            
            // Update recurring schedule with new base amount and escalation date
            $recurring->update([
                'amount' => $escalatedAmount,
                'last_escalation_date' => $invoiceDate,
            ]);

            Log::info('Price escalation applied to recurring invoice', [
                'recurring_invoice_id' => $recurring->id,
                'old_amount' => $amount,
                'new_amount' => $escalatedAmount,
                'escalation_percentage' => $recurring->escalation_percentage
            ]);

            return round($escalatedAmount, 2);
        }

        return $amount;
    }

    protected function generateInvoiceDescription(RecurringInvoice $recurring, array $billingPeriod): string
    {
        $description = $recurring->description;
        
        $description .= "\nBilling Period: " . $billingPeriod['start']->format('M d, Y') . 
                       " - " . $billingPeriod['end']->format('M d, Y');

        if ($recurring->contract) {
            $description .= "\nContract: " . $recurring->contract->contract_number;
        }

        return $description;
    }

    protected function copyContractItemsToRecurring(Contract $contract, RecurringInvoice $recurring): void
    {
        // Implementation would copy contract line items to recurring invoice template
        // This would depend on how contract items are structured
    }

    protected function createMilestoneBasedSchedule(Contract $contract, RecurringInvoice $recurring): void
    {
        // Implementation would create milestone-based billing schedule
        // This would link upcoming milestones to invoice generation dates
    }

    protected function addRecurringItemsToInvoice(RecurringInvoice $recurring, Invoice $invoice, array $billingPeriod): void
    {
        // Implementation would add line items to the generated invoice
        // Based on the recurring schedule configuration
    }

    protected function updateRecurringScheduleAfterGeneration(RecurringInvoice $recurring, Invoice $invoice, Carbon $invoiceDate): void
    {
        $nextInvoiceDate = $this->calculateNextInvoiceDate($invoiceDate, $recurring->billing_frequency);

        $recurring->update([
            'last_invoice_date' => $invoiceDate,
            'next_invoice_date' => $nextInvoiceDate,
            'invoices_generated' => $recurring->invoices_generated + 1,
            'total_revenue_generated' => $recurring->total_revenue_generated + $invoice->amount,
        ]);
    }

    protected function sendGeneratedInvoice(Invoice $invoice): void
    {
        // Implementation would send the invoice via email
        // Using the email service
    }

    protected function processContractRecurring(Contract $contract): array
    {
        $results = ['invoices_generated' => 0, 'errors' => []];

        foreach ($contract->recurringInvoices()->where('status', 'active')->get() as $recurring) {
            if ($recurring->next_invoice_date <= now()) {
                try {
                    $invoice = $this->generateInvoiceFromRecurring($recurring);
                    if ($invoice) {
                        $results['invoices_generated']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = $e->getMessage();
                }
            }
        }

        return $results;
    }

    protected function applyProrationIfNeeded(RecurringInvoice $recurring, float $baseAmount, array $billingPeriod, Carbon $invoiceDate): float
    {
        // Implementation for proration logic
        // Would calculate partial periods for first/last invoices
        return $baseAmount;
    }
}