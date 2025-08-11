<?php

namespace App\Domains\Financial\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Contract;
use App\Models\Client;
use App\Models\RecurringInvoice;
use App\Domains\Financial\Models\Payment;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * RecurringBillingService - Handles automated recurring billing for MSP contracts
 * 
 * Features automated invoice generation, billing cycles, proration, payment retry logic,
 * contract renewals, usage-based billing, and bulk processing.
 */
class RecurringBillingService
{
    /**
     * @var NotificationService
     */
    protected NotificationService $notificationService;

    /**
     * Billing cycle definitions in days
     */
    protected array $billingCycles = [
        'weekly' => 7,
        'bi-weekly' => 14,
        'monthly' => 30,
        'quarterly' => 90,
        'semi-annual' => 180,
        'annual' => 365,
    ];

    /**
     * Payment retry schedule (in hours)
     */
    protected array $retrySchedule = [
        1 => 24,    // First retry after 24 hours
        2 => 72,    // Second retry after 72 hours
        3 => 168,   // Third retry after 1 week
        4 => 336,   // Fourth retry after 2 weeks
    ];

    /**
     * Constructor
     * 
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Generate invoice from contract
     * 
     * @param Contract $contract
     * @param array $options
     * @return Invoice
     */
    public function generateInvoiceFromContract(Contract $contract, array $options = []): Invoice
    {
        try {
            DB::beginTransaction();

            // Validate contract is active and eligible for billing
            if (!$this->isContractBillable($contract)) {
                throw new \Exception('Contract is not eligible for billing');
            }

            // Get or create recurring invoice configuration
            $recurringInvoice = $this->getOrCreateRecurringInvoice($contract);

            // Calculate billing period
            $billingPeriod = $this->calculateBillingPeriod($contract, $options);

            // Create the invoice
            $invoice = new Invoice();
            $invoice->company_id = $contract->company_id;
            $invoice->client_id = $contract->client_id;
            $invoice->prefix = $options['prefix'] ?? config('nestogy.invoice.prefix', 'INV');
            $invoice->number = $this->getNextInvoiceNumber($invoice->company_id, $invoice->prefix);
            $invoice->status = Invoice::STATUS_DRAFT;
            $invoice->date = $billingPeriod['invoice_date'];
            $invoice->due_date = $billingPeriod['due_date'];
            $invoice->currency_code = $contract->currency_code ?? 'USD';
            $invoice->contract_id = $contract->id;
            $invoice->recurring_invoice_id = $recurringInvoice->id;
            $invoice->save();

            // Add contract line items
            $this->addContractLineItems($invoice, $contract, $billingPeriod);

            // Add usage-based items if applicable
            if ($contract->pricing_structure && isset($contract->pricing_structure['usage_based'])) {
                $this->addUsageBasedItems($invoice, $contract, $billingPeriod);
            }

            // Apply proration if needed
            if ($options['prorate'] ?? false) {
                $this->applyProration($invoice, $contract, $billingPeriod);
            }

            // Calculate totals
            $this->calculateInvoiceTotals($invoice);

            // Update recurring invoice tracking
            $recurringInvoice->last_generated_at = now();
            $recurringInvoice->next_generation_date = $this->calculateNextBillingDate($contract);
            $recurringInvoice->invoices_generated++;
            $recurringInvoice->save();

            // Send invoice if auto-send is enabled
            if ($options['auto_send'] ?? $recurringInvoice->auto_send) {
                $this->sendInvoice($invoice);
            }

            DB::commit();

            Log::info('Invoice generated from contract', [
                'invoice_id' => $invoice->id,
                'contract_id' => $contract->id,
                'amount' => $invoice->amount,
            ]);

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate invoice from contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if contract is billable
     * 
     * @param Contract $contract
     * @return bool
     */
    protected function isContractBillable(Contract $contract): bool
    {
        // Check contract status
        if ($contract->status !== 'Active') {
            return false;
        }

        // Check date range
        $now = now();
        if ($contract->start_date > $now) {
            return false;
        }

        if ($contract->end_date && $contract->end_date < $now) {
            return false;
        }

        return true;
    }

    /**
     * Get or create recurring invoice configuration
     * 
     * @param Contract $contract
     * @return RecurringInvoice
     */
    protected function getOrCreateRecurringInvoice(Contract $contract): RecurringInvoice
    {
        return RecurringInvoice::firstOrCreate(
            ['contract_id' => $contract->id],
            [
                'company_id' => $contract->company_id,
                'client_id' => $contract->client_id,
                'frequency' => $this->getBillingFrequency($contract),
                'next_generation_date' => $this->calculateNextBillingDate($contract),
                'auto_send' => true,
                'is_active' => true,
                'invoices_generated' => 0,
                'total_amount_generated' => 0,
            ]
        );
    }

    /**
     * Get billing frequency from contract
     * 
     * @param Contract $contract
     * @return string
     */
    protected function getBillingFrequency(Contract $contract): string
    {
        if ($contract->pricing_structure && isset($contract->pricing_structure['billing_cycle'])) {
            return $contract->pricing_structure['billing_cycle'];
        }

        // Default based on contract term
        if ($contract->term_months <= 1) {
            return 'monthly';
        } elseif ($contract->term_months <= 3) {
            return 'quarterly';
        } else {
            return 'annual';
        }
    }

    /**
     * Calculate billing period
     * 
     * @param Contract $contract
     * @param array $options
     * @return array
     */
    protected function calculateBillingPeriod(Contract $contract, array $options = []): array
    {
        $frequency = $this->getBillingFrequency($contract);
        $cycleDays = $this->billingCycles[$frequency] ?? 30;

        // Determine period start
        if (isset($options['period_start'])) {
            $periodStart = Carbon::parse($options['period_start']);
        } else {
            $lastInvoice = Invoice::where('contract_id', $contract->id)
                ->orderBy('date', 'desc')
                ->first();

            if ($lastInvoice) {
                $periodStart = $lastInvoice->date->copy()->addDays($cycleDays);
            } else {
                $periodStart = Carbon::parse($contract->start_date);
            }
        }

        // Calculate period end
        $periodEnd = $periodStart->copy()->addDays($cycleDays - 1);

        // Ensure period doesn't exceed contract end date
        if ($contract->end_date && $periodEnd > $contract->end_date) {
            $periodEnd = Carbon::parse($contract->end_date);
        }

        // Calculate invoice and due dates
        $invoiceDate = $options['invoice_date'] ?? now();
        $paymentTerms = $contract->payment_terms ?? 'net_30';
        $dueDate = $this->calculateDueDate($invoiceDate, $paymentTerms);

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'days_in_period' => $periodStart->diffInDays($periodEnd) + 1,
            'full_cycle_days' => $cycleDays,
        ];
    }

    /**
     * Calculate due date based on payment terms
     * 
     * @param Carbon $invoiceDate
     * @param string $paymentTerms
     * @return Carbon
     */
    protected function calculateDueDate(Carbon $invoiceDate, string $paymentTerms): Carbon
    {
        $termMappings = [
            'due_on_receipt' => 0,
            'net_7' => 7,
            'net_14' => 14,
            'net_15' => 15,
            'net_30' => 30,
            'net_45' => 45,
            'net_60' => 60,
            'net_90' => 90,
        ];

        $days = $termMappings[$paymentTerms] ?? 30;
        return $invoiceDate->copy()->addDays($days);
    }

    /**
     * Add contract line items to invoice
     * 
     * @param Invoice $invoice
     * @param Contract $contract
     * @param array $billingPeriod
     * @return void
     */
    protected function addContractLineItems(Invoice $invoice, Contract $contract, array $billingPeriod): void
    {
        // Parse pricing structure
        $pricing = is_array($contract->pricing_structure) 
            ? $contract->pricing_structure 
            : json_decode($contract->pricing_structure ?? '{}', true);

        // Add fixed recurring items
        if (isset($pricing['recurring_items'])) {
            foreach ($pricing['recurring_items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'] ?? 'Recurring Service',
                    'quantity' => $item['quantity'] ?? 1,
                    'rate' => $item['rate'] ?? 0,
                    'amount' => ($item['quantity'] ?? 1) * ($item['rate'] ?? 0),
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'is_taxable' => $item['is_taxable'] ?? true,
                    'category' => $item['category'] ?? 'service',
                ]);
            }
        }

        // Add base contract amount if no specific items
        if (!isset($pricing['recurring_items']) || empty($pricing['recurring_items'])) {
            $description = sprintf(
                '%s - Service Period: %s to %s',
                $contract->title,
                $billingPeriod['period_start']->format('M d, Y'),
                $billingPeriod['period_end']->format('M d, Y')
            );

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $description,
                'quantity' => 1,
                'rate' => $contract->contract_value,
                'amount' => $contract->contract_value,
                'tax_rate' => 0,
                'is_taxable' => true,
                'category' => 'contract',
            ]);
        }
    }

    /**
     * Add usage-based billing items
     * 
     * @param Invoice $invoice
     * @param Contract $contract
     * @param array $billingPeriod
     * @return void
     */
    protected function addUsageBasedItems(Invoice $invoice, Contract $contract, array $billingPeriod): void
    {
        $pricing = is_array($contract->pricing_structure) 
            ? $contract->pricing_structure 
            : json_decode($contract->pricing_structure ?? '{}', true);

        if (!isset($pricing['usage_based']) || !is_array($pricing['usage_based'])) {
            return;
        }

        foreach ($pricing['usage_based'] as $usageItem) {
            // Get usage data for the period
            $usage = $this->getUsageData(
                $contract,
                $usageItem['metric'] ?? '',
                $billingPeriod['period_start'],
                $billingPeriod['period_end']
            );

            if ($usage > 0) {
                $rate = $usageItem['rate'] ?? 0;
                $amount = $usage * $rate;

                // Apply tiered pricing if configured
                if (isset($usageItem['tiers']) && is_array($usageItem['tiers'])) {
                    $amount = $this->calculateTieredPricing($usage, $usageItem['tiers']);
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => sprintf(
                        '%s - %s units @ %s per unit',
                        $usageItem['description'] ?? 'Usage-based service',
                        $usage,
                        $rate
                    ),
                    'quantity' => $usage,
                    'rate' => $rate,
                    'amount' => $amount,
                    'tax_rate' => $usageItem['tax_rate'] ?? 0,
                    'is_taxable' => $usageItem['is_taxable'] ?? true,
                    'category' => 'usage',
                    'metadata' => [
                        'metric' => $usageItem['metric'],
                        'period_start' => $billingPeriod['period_start']->toDateString(),
                        'period_end' => $billingPeriod['period_end']->toDateString(),
                    ],
                ]);
            }
        }
    }

    /**
     * Get usage data for a metric
     * 
     * @param Contract $contract
     * @param string $metric
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function getUsageData(Contract $contract, string $metric, Carbon $startDate, Carbon $endDate): float
    {
        // This would typically integrate with your usage tracking system
        // For now, return a placeholder implementation
        
        switch ($metric) {
            case 'support_hours':
                // Get total support hours from tickets
                return DB::table('ticket_time_entries')
                    ->join('tickets', 'ticket_time_entries.ticket_id', '=', 'tickets.id')
                    ->where('tickets.client_id', $contract->client_id)
                    ->whereBetween('ticket_time_entries.created_at', [$startDate, $endDate])
                    ->where('ticket_time_entries.billable', true)
                    ->sum('ticket_time_entries.hours_worked');

            case 'api_calls':
                // Would integrate with API usage tracking
                return 0;

            case 'storage_gb':
                // Would integrate with storage monitoring
                return 0;

            case 'bandwidth_gb':
                // Would integrate with bandwidth monitoring
                return 0;

            default:
                return 0;
        }
    }

    /**
     * Calculate tiered pricing
     * 
     * @param float $usage
     * @param array $tiers
     * @return float
     */
    protected function calculateTieredPricing(float $usage, array $tiers): float
    {
        $totalAmount = 0;
        $remainingUsage = $usage;

        foreach ($tiers as $tier) {
            $tierLimit = $tier['limit'] ?? PHP_FLOAT_MAX;
            $tierRate = $tier['rate'] ?? 0;
            
            if ($remainingUsage <= 0) {
                break;
            }

            $tierUsage = min($remainingUsage, $tierLimit);
            $totalAmount += $tierUsage * $tierRate;
            $remainingUsage -= $tierUsage;
        }

        return $totalAmount;
    }

    /**
     * Apply proration to invoice
     * 
     * @param Invoice $invoice
     * @param Contract $contract
     * @param array $billingPeriod
     * @return void
     */
    protected function applyProration(Invoice $invoice, Contract $contract, array $billingPeriod): void
    {
        $actualDays = $billingPeriod['days_in_period'];
        $fullCycleDays = $billingPeriod['full_cycle_days'];

        // If it's a partial period, apply proration
        if ($actualDays < $fullCycleDays) {
            $prorationFactor = $actualDays / $fullCycleDays;

            // Update existing line items
            $items = $invoice->items()->where('category', '!=', 'usage')->get();
            
            foreach ($items as $item) {
                $originalAmount = $item->amount;
                $proratedAmount = round($originalAmount * $prorationFactor, 2);
                
                $item->amount = $proratedAmount;
                $item->description .= sprintf(' (Prorated %d/%d days)', $actualDays, $fullCycleDays);
                $item->save();
            }

            // Add proration note to invoice
            $invoice->note = ($invoice->note ? $invoice->note . "\n" : '') . 
                sprintf('This invoice has been prorated for %d days of service.', $actualDays);
            $invoice->save();
        }
    }

    /**
     * Calculate invoice totals
     * 
     * @param Invoice $invoice
     * @return void
     */
    protected function calculateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($invoice->items as $item) {
            $subtotal += $item->amount;
            
            if ($item->is_taxable && $item->tax_rate > 0) {
                $taxTotal += $item->amount * ($item->tax_rate / 100);
            }
        }

        $invoice->subtotal = round($subtotal, 2);
        $invoice->tax_amount = round($taxTotal, 2);
        $invoice->amount = round($subtotal + $taxTotal - $invoice->discount_amount, 2);
        $invoice->save();
    }

    /**
     * Calculate next billing date
     * 
     * @param Contract $contract
     * @return Carbon
     */
    protected function calculateNextBillingDate(Contract $contract): Carbon
    {
        $frequency = $this->getBillingFrequency($contract);
        $cycleDays = $this->billingCycles[$frequency] ?? 30;

        $lastInvoice = Invoice::where('contract_id', $contract->id)
            ->orderBy('date', 'desc')
            ->first();

        if ($lastInvoice) {
            return $lastInvoice->date->copy()->addDays($cycleDays);
        }

        return now()->addDays($cycleDays);
    }

    /**
     * Get next invoice number
     * 
     * @param int $companyId
     * @param string $prefix
     * @return int
     */
    protected function getNextInvoiceNumber(int $companyId, string $prefix): int
    {
        $lastInvoice = Invoice::where('company_id', $companyId)
            ->where('prefix', $prefix)
            ->orderBy('number', 'desc')
            ->first();

        return $lastInvoice ? $lastInvoice->number + 1 : 1001;
    }

    /**
     * Send invoice to client
     * 
     * @param Invoice $invoice
     * @return void
     */
    protected function sendInvoice(Invoice $invoice): void
    {
        $invoice->status = Invoice::STATUS_SENT;
        $invoice->sent_at = now();
        $invoice->save();

        $this->notificationService->notifyInvoiceSent($invoice);
    }

    /**
     * Process failed payment with retry logic
     * 
     * @param Payment $payment
     * @param array $options
     * @return bool
     */
    public function retryFailedPayment(Payment $payment, array $options = []): bool
    {
        try {
            // Check retry count
            $retryCount = $payment->retry_count ?? 0;
            
            if ($retryCount >= count($this->retrySchedule)) {
                Log::warning('Maximum payment retries exceeded', [
                    'payment_id' => $payment->id,
                    'retry_count' => $retryCount,
                ]);
                
                // Mark invoice as overdue
                $this->markInvoiceOverdue($payment->invoice);
                
                // Notify about final failure
                $this->notificationService->notifyPaymentFinalFailure($payment);
                
                return false;
            }

            // Calculate next retry time with exponential backoff
            $nextRetryHours = $this->retrySchedule[$retryCount + 1];
            $nextRetryTime = now()->addHours($nextRetryHours);

            // Update payment record
            $payment->retry_count = $retryCount + 1;
            $payment->next_retry_at = $nextRetryTime;
            $payment->last_retry_at = now();
            $payment->save();

            // Attempt payment processing
            $result = $this->processPaymentWithGateway($payment);

            if ($result['success']) {
                // Payment successful
                $payment->status = 'completed';
                $payment->completed_at = now();
                $payment->save();

                // Update invoice status
                $invoice = $payment->invoice;
                $invoice->status = Invoice::STATUS_PAID;
                $invoice->paid_at = now();
                $invoice->save();

                // Send success notification
                $this->notificationService->notifyPaymentSuccess($payment);

                Log::info('Payment retry successful', [
                    'payment_id' => $payment->id,
                    'retry_count' => $retryCount + 1,
                ]);

                return true;

            } else {
                // Payment failed, schedule next retry
                Log::warning('Payment retry failed', [
                    'payment_id' => $payment->id,
                    'retry_count' => $retryCount + 1,
                    'next_retry_at' => $nextRetryTime,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                // Send retry notification
                $this->notificationService->notifyPaymentRetryScheduled($payment, $nextRetryTime);

                return false;
            }

        } catch (\Exception $e) {
            Log::error('Error processing payment retry', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process payment with gateway
     * 
     * @param Payment $payment
     * @return array
     */
    protected function processPaymentWithGateway(Payment $payment): array
    {
        // This would integrate with actual payment gateways (Stripe, PayPal, etc.)
        // For now, return a mock implementation
        
        try {
            // Simulate payment processing
            $success = rand(0, 100) > 30; // 70% success rate for testing
            
            if ($success) {
                return [
                    'success' => true,
                    'transaction_id' => Str::uuid()->toString(),
                    'message' => 'Payment processed successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Payment declined by processor',
                    'error_code' => 'DECLINED',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Mark invoice as overdue
     * 
     * @param Invoice $invoice
     * @return void
     */
    protected function markInvoiceOverdue(Invoice $invoice): void
    {
        $invoice->status = Invoice::STATUS_OVERDUE;
        $invoice->save();

        // Suspend services if configured
        if (config('nestogy.billing.suspend_on_overdue', false)) {
            $this->suspendClientServices($invoice->client);
        }

        $this->notificationService->notifyInvoiceOverdue($invoice);
    }

    /**
     * Suspend client services
     * 
     * @param Client $client
     * @return void
     */
    protected function suspendClientServices(Client $client): void
    {
        // This would integrate with service management system
        Log::warning('Client services suspended due to overdue invoice', [
            'client_id' => $client->id,
        ]);
    }

    /**
     * Process contract renewal
     * 
     * @param Contract $contract
     * @param array $options
     * @return Contract
     */
    public function processContractRenewal(Contract $contract, array $options = []): Contract
    {
        try {
            DB::beginTransaction();

            // Check if contract is eligible for renewal
            if (!$this->isContractRenewable($contract)) {
                throw new \Exception('Contract is not eligible for renewal');
            }

            // Create renewed contract
            $newContract = $contract->replicate();
            $newContract->contract_number = $this->generateContractNumber($contract->company_id);
            $newContract->start_date = $contract->end_date->copy()->addDay();
            $newContract->end_date = $this->calculateRenewalEndDate($newContract, $options);
            $newContract->status = $options['auto_activate'] ?? true ? 'Active' : 'Pending';
            $newContract->parent_contract_id = $contract->id;
            $newContract->renewal_count = ($contract->renewal_count ?? 0) + 1;
            $newContract->renewed_at = now();
            $newContract->save();

            // Update original contract
            $contract->renewal_contract_id = $newContract->id;
            $contract->renewed_at = now();
            $contract->save();

            // Generate first invoice if auto-billing is enabled
            if ($options['generate_invoice'] ?? $contract->auto_renewal) {
                $this->generateInvoiceFromContract($newContract, [
                    'auto_send' => $options['auto_send'] ?? true,
                ]);
            }

            // Send renewal notification
            $this->notificationService->notifyContractRenewed($newContract);

            DB::commit();

            Log::info('Contract renewed successfully', [
                'original_contract_id' => $contract->id,
                'new_contract_id' => $newContract->id,
            ]);

            return $newContract;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to renew contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if contract is renewable
     * 
     * @param Contract $contract
     * @return bool
     */
    protected function isContractRenewable(Contract $contract): bool
    {
        // Can't renew if already renewed
        if ($contract->renewal_contract_id) {
            return false;
        }

        // Check if auto-renewal is enabled or manual renewal is allowed
        if (!$contract->auto_renewal && $contract->renewal_type === 'none') {
            return false;
        }

        // Check if within renewal window
        if ($contract->end_date) {
            $renewalWindow = $contract->renewal_notice_days ?? 30;
            $renewalStartDate = $contract->end_date->copy()->subDays($renewalWindow);
            
            if (now() < $renewalStartDate) {
                return false; // Too early for renewal
            }
        }

        return true;
    }

    /**
     * Calculate renewal end date
     * 
     * @param Contract $contract
     * @param array $options
     * @return Carbon
     */
    protected function calculateRenewalEndDate(Contract $contract, array $options = []): Carbon
    {
        $termMonths = $options['term_months'] ?? $contract->term_months ?? 12;
        return $contract->start_date->copy()->addMonths($termMonths);
    }

    /**
     * Generate contract number
     * 
     * @param int $companyId
     * @return string
     */
    protected function generateContractNumber(int $companyId): string
    {
        $prefix = 'CTR';
        $lastContract = Contract::where('company_id', $companyId)
            ->where('prefix', $prefix)
            ->orderBy('number', 'desc')
            ->first();

        $number = $lastContract ? $lastContract->number + 1 : 1001;
        return sprintf('%s-%06d', $prefix, $number);
    }

    /**
     * Generate bulk invoices for scheduled run
     * 
     * @param Carbon $runDate
     * @param array $options
     * @return array
     */
    public function generateBulkInvoices(Carbon $runDate, array $options = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        try {
            // Get all recurring invoices due for generation
            $recurringInvoices = RecurringInvoice::where('is_active', true)
                ->where('next_generation_date', '<=', $runDate)
                ->with(['contract', 'client'])
                ->get();

            Log::info('Starting bulk invoice generation', [
                'recurring_invoices' => $recurringInvoices->count(),
                'run_date' => $runDate->toDateString(),
            ]);

            foreach ($recurringInvoices as $recurringInvoice) {
                try {
                    $contract = $recurringInvoice->contract;

                    // Skip if contract is not billable
                    if (!$contract || !$this->isContractBillable($contract)) {
                        $results['skipped'][] = [
                            'recurring_invoice_id' => $recurringInvoice->id,
                            'reason' => 'Contract not billable',
                        ];
                        continue;
                    }

                    // Generate invoice
                    $invoice = $this->generateInvoiceFromContract($contract, array_merge($options, [
                        'auto_send' => $recurringInvoice->auto_send,
                    ]));

                    $results['success'][] = [
                        'invoice_id' => $invoice->id,
                        'contract_id' => $contract->id,
                        'amount' => $invoice->amount,
                    ];

                    // Update recurring invoice stats
                    $recurringInvoice->total_amount_generated += $invoice->amount;
                    $recurringInvoice->save();

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'recurring_invoice_id' => $recurringInvoice->id,
                        'contract_id' => $recurringInvoice->contract_id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to generate invoice in bulk run', [
                        'recurring_invoice_id' => $recurringInvoice->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Send summary notification
            $this->notificationService->notifyBulkInvoiceGeneration($results);

            Log::info('Bulk invoice generation completed', [
                'success' => count($results['success']),
                'failed' => count($results['failed']),
                'skipped' => count($results['skipped']),
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk invoice generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Calculate usage-based billing for period
     * 
     * @param Client $client
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function calculateUsageBasedBilling(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        $usageItems = [];
        
        // Get all active contracts with usage-based pricing
        $contracts = Contract::where('client_id', $client->id)
            ->where('status', 'Active')
            ->whereJsonContains('pricing_structure->usage_based', true)
            ->get();

        foreach ($contracts as $contract) {
            $pricing = is_array($contract->pricing_structure) 
                ? $contract->pricing_structure 
                : json_decode($contract->pricing_structure ?? '{}', true);

            if (!isset($pricing['usage_based']) || !is_array($pricing['usage_based'])) {
                continue;
            }

            foreach ($pricing['usage_based'] as $usageItem) {
                $metric = $usageItem['metric'] ?? '';
                $usage = $this->getUsageData($contract, $metric, $startDate, $endDate);

                if ($usage > 0) {
                    $rate = $usageItem['rate'] ?? 0;
                    $amount = $usage * $rate;

                    // Apply tiered pricing if configured
                    if (isset($usageItem['tiers']) && is_array($usageItem['tiers'])) {
                        $amount = $this->calculateTieredPricing($usage, $usageItem['tiers']);
                    }

                    $usageItems[] = [
                        'contract_id' => $contract->id,
                        'metric' => $metric,
                        'description' => $usageItem['description'] ?? 'Usage-based service',
                        'usage' => $usage,
                        'rate' => $rate,
                        'amount' => $amount,
                        'period_start' => $startDate->toDateString(),
                        'period_end' => $endDate->toDateString(),
                    ];
                }
            }
        }

        return [
            'client_id' => $client->id,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'items' => $usageItems,
            'total_amount' => collect($usageItems)->sum('amount'),
        ];
    }

    /**
     * Get upcoming renewals
     * 
     * @param int $companyId
     * @param int $days
     * @return Collection
     */
    public function getUpcomingRenewals(int $companyId, int $days = 30): Collection
    {
        return Contract::where('company_id', $companyId)
            ->where('status', 'Active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)])
            ->whereNull('renewal_contract_id')
            ->with(['client'])
            ->orderBy('end_date')
            ->get();
    }

    /**
     * Get payment retry queue
     * 
     * @param int $companyId
     * @return Collection
     */
    public function getPaymentRetryQueue(int $companyId): Collection
    {
        return Payment::where('company_id', $companyId)
            ->where('status', 'failed')
            ->where('retry_count', '<', count($this->retrySchedule))
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->with(['invoice', 'invoice.client'])
            ->orderBy('next_retry_at')
            ->get();
    }

    /**
     * Generate billing forecast
     * 
     * @param int $companyId
     * @param int $months
     * @return array
     */
    public function generateBillingForecast(int $companyId, int $months = 3): array
    {
        $forecast = [];
        $currentDate = now()->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $currentDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();

            // Get recurring revenue for the month
            $recurringRevenue = RecurringInvoice::where('company_id', $companyId)
                ->where('is_active', true)
                ->whereHas('contract', function ($q) use ($monthEnd) {
                    $q->where('status', 'Active')
                        ->where(function ($q2) use ($monthEnd) {
                            $q2->whereNull('end_date')
                                ->orWhere('end_date', '>=', $monthEnd);
                        });
                })
                ->get()
                ->sum(function ($recurring) {
                    return $recurring->contract->contract_value ?? 0;
                });

            // Estimate usage-based revenue (based on historical average)
            $usageRevenue = $this->estimateUsageRevenue($companyId, $monthStart, $monthEnd);

            $forecast[] = [
                'month' => $monthStart->format('Y-m'),
                'recurring_revenue' => $recurringRevenue,
                'usage_revenue' => $usageRevenue,
                'total_revenue' => $recurringRevenue + $usageRevenue,
            ];
        }

        return $forecast;
    }

    /**
     * Estimate usage-based revenue
     * 
     * @param int $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function estimateUsageRevenue(int $companyId, Carbon $startDate, Carbon $endDate): float
    {
        // Calculate based on 3-month historical average
        $historicalStart = now()->subMonths(3);
        $historicalEnd = now();

        $historicalRevenue = InvoiceItem::whereHas('invoice', function ($q) use ($companyId, $historicalStart, $historicalEnd) {
                $q->where('company_id', $companyId)
                    ->whereBetween('date', [$historicalStart, $historicalEnd]);
            })
            ->where('category', 'usage')
            ->sum('amount');

        // Calculate monthly average
        $monthlyAverage = $historicalRevenue / 3;

        return round($monthlyAverage, 2);
    }
}