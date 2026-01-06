<?php

namespace App\Domains\Financial\Services;

use App\Domains\Client\Models\Client;
use App\Domains\Contract\Models\ContractContactAssignment;
use App\Domains\Financial\Models\Category;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\InvoiceItem;
use App\Domains\Financial\Models\BillingAuditLog;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ticket Billing Service
 * 
 * Handles automatic billing for support tickets based on:
 * - Time entries (hourly billing)
 * - Per-ticket rates (fixed fee per ticket)
 * - Mixed billing (combination of both)
 */
class TicketBillingService
{
    protected TimeEntryInvoiceService $timeEntryService;
    protected InvoiceService $invoiceService;

    public function __construct(
        TimeEntryInvoiceService $timeEntryService,
        InvoiceService $invoiceService
    ) {
        $this->timeEntryService = $timeEntryService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Process billing for a ticket
     * 
     * @param Ticket $ticket
     * @param array $options Override options
     * @return Invoice|null
     * @throws \Exception
     */
    public function billTicket(Ticket $ticket, array $options = []): ?Invoice
    {
        if (!config('billing.ticket.enabled', true)) {
            $this->log('Ticket billing is disabled globally', ['ticket_id' => $ticket->id]);
            return null;
        }

        // Check if ticket is already invoiced
        if ($ticket->invoice_id && !($options['force'] ?? false)) {
            $this->log('Ticket already invoiced', [
                'ticket_id' => $ticket->id,
                'invoice_id' => $ticket->invoice_id,
            ]);
            return null;
        }

        // Check if ticket is billable
        if (!$ticket->billable && !($options['force'] ?? false)) {
            $this->log('Ticket is not billable', ['ticket_id' => $ticket->id]);
            return null;
        }

        // Must have a client
        if (!$ticket->client_id) {
            $this->log('Ticket has no client', ['ticket_id' => $ticket->id], 'warning');
            return null;
        }

        // Check if should bill based on contract limits (unless forced)
        if (!($options['force'] ?? false)) {
            $shouldBill = $this->shouldBillTicket($ticket);
            
            if (!$shouldBill['should_bill']) {
                $this->log('Ticket covered by contract limits', [
                    'ticket_id' => $ticket->id,
                    'reason' => $shouldBill['reason'],
                    'covered_by' => $shouldBill['covered_by'],
                ]);
                
                // Deduct from contract limits but don't create invoice
                $this->deductFromContractLimits($ticket);
                
                // Mark ticket as processed (not invoiced, but accounted for)
                $ticket->update(['billing_status' => 'covered']);
                
                return null;
            }
        }

        return DB::transaction(function () use ($ticket, $options) {
            $client = $ticket->client;
            $strategy = $this->determineBillingStrategy($ticket, $options);

            $this->log("Processing ticket billing with strategy: {$strategy}", [
                'ticket_id' => $ticket->id,
                'client_id' => $client->id,
                'strategy' => $strategy,
            ]);

            $invoice = match ($strategy) {
                'time_based' => $this->billByTimeEntries($ticket, $client, $options),
                'per_ticket' => $this->billByTicketRate($ticket, $client, $options),
                'mixed' => $this->billMixed($ticket, $client, $options),
                default => throw new \Exception("Unknown billing strategy: {$strategy}"),
            };

            if ($invoice) {
                // Link ticket to invoice
                $ticket->update(['invoice_id' => $invoice->id]);

                // Log audit trail
                BillingAuditLog::create([
                    'company_id' => $ticket->company_id,
                    'user_id' => auth()->id(),
                    'action' => BillingAuditLog::ACTION_INVOICE_GENERATED,
                    'entity_type' => 'Ticket',
                    'entity_id' => $ticket->id,
                    'ticket_id' => $ticket->id,
                    'invoice_id' => $invoice->id,
                    'description' => "Invoice #{$invoice->number} generated from ticket #{$ticket->number}",
                    'metadata' => [
                        'strategy' => $strategy,
                        'amount' => $invoice->total ?? 0,
                        'client_id' => $ticket->client_id,
                        'billable_hours' => $invoice->items->sum('quantity'),
                    ],
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'user_agent' => request()->userAgent() ?? 'CLI',
                ]);

                $this->log('Ticket billing completed', [
                    'ticket_id' => $ticket->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->amount,
                    'strategy' => $strategy,
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Determine which billing strategy to use for a ticket
     */
    protected function determineBillingStrategy(Ticket $ticket, array $options = []): string
    {
        // Allow strategy override
        if (isset($options['strategy'])) {
            return $options['strategy'];
        }

        $hasTimeEntries = $ticket->timeEntries()->where('billable', true)->exists();
        $contractAssignment = $this->getContractAssignment($ticket);
        $hasPerTicketRate = $contractAssignment && $contractAssignment->per_ticket_rate > 0;

        // If we have both time entries and per-ticket rate, use mixed
        if ($hasTimeEntries && $hasPerTicketRate) {
            return 'mixed';
        }

        // If we have time entries, use time-based
        if ($hasTimeEntries) {
            return 'time_based';
        }

        // If we have per-ticket rate, use that
        if ($hasPerTicketRate) {
            return 'per_ticket';
        }

        // Fall back to default from config
        return config('billing.ticket.default_strategy', 'time_based');
    }

    /**
     * Bill ticket based on time entries
     */
    protected function billByTimeEntries(Ticket $ticket, Client $client, array $options = []): ?Invoice
    {
        $timeEntries = $ticket->timeEntries()
            ->where('billable', true)
            ->where('is_billed', false)
            ->get();

        if ($timeEntries->isEmpty()) {
            $this->log('No billable time entries found', ['ticket_id' => $ticket->id]);
            
            if (config('billing.ticket.skip_zero_invoices', true)) {
                return null;
            }
        }

        $totalHours = $timeEntries->sum('hours_worked');
        $minHours = config('billing.ticket.min_billable_hours', 0.25);
        $roundTo = config('billing.ticket.round_hours_to', 0.25);

        // Apply minimum hours
        $billableHours = max($totalHours, $minHours);

        // Round hours
        if ($roundTo > 0) {
            $billableHours = ceil($billableHours / $roundTo) * $roundTo;
        }

        // Get hourly rate
        $hourlyRate = $this->getHourlyRate($ticket, $client);
        $amount = $billableHours * $hourlyRate;

        // Create invoice
        $invoice = $this->createInvoice($client, [
            'ticket_id' => $ticket->id,
            'note' => $options['note'] ?? "Support ticket #{$ticket->number}: {$ticket->subject}",
        ]);

        // Create invoice item
        $description = $this->buildTimeEntryDescription($ticket, $timeEntries, $billableHours);
        
        $taxRate = $options['tax_rate'] ?? 0;
        $tax = $amount * ($taxRate / 100);
        
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'name' => "Support - Ticket #{$ticket->number}",
            'description' => $description,
            'quantity' => $billableHours,
            'price' => $hourlyRate,
            'subtotal' => $amount,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'total' => $amount + $tax,
        ]);

        // Mark time entries as billed
        $timeEntries->each(function ($entry) use ($invoice) {
            $entry->update(['is_billed' => true]);
        });

        $this->updateInvoiceTotals($invoice);

        return $invoice;
    }

    /**
     * Bill ticket using per-ticket rate from contract
     */
    protected function billByTicketRate(Ticket $ticket, Client $client, array $options = []): ?Invoice
    {
        $contractAssignment = $this->getContractAssignment($ticket);

        if (!$contractAssignment || $contractAssignment->per_ticket_rate <= 0) {
            $this->log('No per-ticket rate found for ticket', [
                'ticket_id' => $ticket->id,
                'client_id' => $client->id,
            ], 'warning');

            if (config('billing.ticket.skip_zero_invoices', true)) {
                return null;
            }
        }

        $perTicketRate = $contractAssignment->per_ticket_rate ?? 0;

        // Create invoice
        $invoice = $this->createInvoice($client, [
            'ticket_id' => $ticket->id,
            'note' => $options['note'] ?? "Support ticket #{$ticket->number}: {$ticket->subject}",
        ]);

        // Create invoice item
        $description = "Support Ticket #{$ticket->number}: {$ticket->subject}\n";
        $description .= "Priority: {$ticket->priority}\n";
        $description .= "Per-ticket flat rate";

        $taxRate = $options['tax_rate'] ?? 0;
        $tax = $perTicketRate * ($taxRate / 100);
        
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'name' => "Support - Ticket #{$ticket->number}",
            'description' => $description,
            'quantity' => 1,
            'price' => $perTicketRate,
            'subtotal' => $perTicketRate,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'total' => $perTicketRate + $tax,
        ]);

        $this->updateInvoiceTotals($invoice);

        return $invoice;
    }

    /**
     * Bill ticket using mixed approach (time entries + per-ticket rate)
     */
    protected function billMixed(Ticket $ticket, Client $client, array $options = []): ?Invoice
    {
        $timeEntries = $ticket->timeEntries()
            ->where('billable', true)
            ->where('is_billed', false)
            ->get();

        $contractAssignment = $this->getContractAssignment($ticket);
        $perTicketRate = $contractAssignment->per_ticket_rate ?? 0;

        $totalHours = $timeEntries->sum('hours_worked');
        $minHours = config('billing.ticket.min_billable_hours', 0.25);
        $roundTo = config('billing.ticket.round_hours_to', 0.25);

        // Apply minimum hours and rounding
        $billableHours = max($totalHours, $minHours);
        if ($roundTo > 0) {
            $billableHours = ceil($billableHours / $roundTo) * $roundTo;
        }

        $hourlyRate = $this->getHourlyRate($ticket, $client);
        $timeAmount = $billableHours * $hourlyRate;
        $totalAmount = $timeAmount + $perTicketRate;

        if ($totalAmount <= 0 && config('billing.ticket.skip_zero_invoices', true)) {
            return null;
        }

        // Create invoice
        $invoice = $this->createInvoice($client, [
            'ticket_id' => $ticket->id,
            'note' => $options['note'] ?? "Support ticket #{$ticket->number}: {$ticket->subject}",
        ]);

        $taxRate = $options['tax_rate'] ?? 0;
        
        // Add time entry item
        if ($timeAmount > 0) {
            $timeDescription = $this->buildTimeEntryDescription($ticket, $timeEntries, $billableHours);
            $timeTax = $timeAmount * ($taxRate / 100);
            
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'name' => "Support Time - Ticket #{$ticket->number}",
                'description' => $timeDescription,
                'quantity' => $billableHours,
                'price' => $hourlyRate,
                'subtotal' => $timeAmount,
                'tax_rate' => $taxRate,
                'tax' => $timeTax,
                'total' => $timeAmount + $timeTax,
            ]);

            // Mark time entries as billed
            $timeEntries->each(function ($entry) use ($invoice) {
                $entry->update(['is_billed' => true]);
            });
        }

        // Add per-ticket rate item
        if ($perTicketRate > 0) {
            $perTicketTax = $perTicketRate * ($taxRate / 100);
            
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'name' => "Support Fee - Ticket #{$ticket->number}",
                'description' => "Support Ticket #{$ticket->number} - Flat Rate Fee",
                'quantity' => 1,
                'price' => $perTicketRate,
                'subtotal' => $perTicketRate,
                'tax_rate' => $taxRate,
                'tax' => $perTicketTax,
                'total' => $perTicketRate + $perTicketTax,
            ]);
        }

        $this->updateInvoiceTotals($invoice);

        return $invoice;
    }

    /**
     * Get or create invoice for billing
     * 
     * This consolidates tickets for the same client into a single draft invoice
     */
    protected function createInvoice(Client $client, array $data = []): Invoice
    {
        // Check if we should consolidate into existing draft invoice
        $consolidate = $data['consolidate'] ?? config('billing.ticket.consolidate_invoices', true);
        
        if ($consolidate) {
            // Look for an existing draft invoice for this client created today (with lock to prevent race conditions)
            $existingInvoice = Invoice::where('company_id', $client->company_id)
                ->where('client_id', $client->id)
                ->where('status', Invoice::STATUS_DRAFT)
                ->whereDate('date', now()->toDateString())
                ->lockForUpdate()
                ->first();
            
            if ($existingInvoice) {
                return $existingInvoice;
            }
        }

        // Use a lock to prevent duplicate invoice numbers (include soft-deleted)
        $lastInvoice = Invoice::withTrashed()
            ->where('company_id', $client->company_id)
            ->lockForUpdate()
            ->orderBy('number', 'desc')
            ->first();

        $dueDate = now()->addDays(
            (int) ($data['due_days'] ?? config('billing.ticket.invoice_due_days', 30))
        );

        $status = config('billing.ticket.require_approval', true) 
            ? Invoice::STATUS_DRAFT 
            : Invoice::STATUS_SENT;

        // Get or create a default category for ticket billing
        $categoryId = $data['category_id'] ?? Category::where('company_id', $client->company_id)
            ->where('name', 'like', '%Support%')
            ->orWhere('name', 'like', '%Service%')
            ->value('id');
        
        // Fallback to first category if none found
        if (!$categoryId) {
            $categoryId = Category::where('company_id', $client->company_id)->value('id')
                ?? Category::first()?->id;
        }

        $invoice = Invoice::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'category_id' => $categoryId,
            'prefix' => $data['prefix'] ?? 'INV',
            'number' => $lastInvoice ? $lastInvoice->number + 1 : 1001,
            'date' => $data['date'] ?? now(),
            'due_date' => $dueDate,
            'status' => $status,
            'currency_code' => $client->currency_code ?? 'USD',
            'note' => $data['note'] ?? 'Support Services',
            'url_key' => \Illuminate\Support\Str::random(32),
        ]);

        return $invoice;
    }

    /**
     * Build description for time entry invoice item
     */
    protected function buildTimeEntryDescription(Ticket $ticket, $timeEntries, float $billableHours): string
    {
        $description = "Support Ticket #{$ticket->number}: {$ticket->subject}\n";
        $description .= "Priority: {$ticket->priority} | Status: {$ticket->status}\n\n";
        
        if ($timeEntries->isNotEmpty()) {
            $description .= "Time Entries:\n";
            foreach ($timeEntries as $entry) {
                $date = $entry->work_date->format('M d, Y');
                $hours = number_format($entry->hours_worked, 2);
                $user = $entry->user ? $entry->user->name : 'Unknown';
                $notes = $entry->description ? " - {$entry->description}" : '';
                $description .= "  {$date}: {$hours}h by {$user}{$notes}\n";
            }
        }

        $totalActual = $timeEntries->sum('hours_worked');
        $description .= "\nActual Hours: " . number_format($totalActual, 2);
        $description .= "\nBillable Hours: " . number_format($billableHours, 2);

        return $description;
    }

    /**
     * Get hourly rate for ticket billing
     */
    protected function getHourlyRate(Ticket $ticket, Client $client): float
    {
        // Try to get rate from contract assignment
        $contractAssignment = $this->getContractAssignment($ticket);
        if ($contractAssignment && $contractAssignment->billing_rate > 0) {
            return $contractAssignment->billing_rate;
        }

        // Fall back to client's default hourly rate
        if ($client->hourly_rate > 0) {
            return $client->hourly_rate;
        }

        // Last resort: use config default
        return config('billing.ticket.default_hourly_rate', 100);
    }

    /**
     * Get contract assignment for ticket's contact
     */
    protected function getContractAssignment(Ticket $ticket): ?ContractContactAssignment
    {
        if (!$ticket->contact_id) {
            return null;
        }

        return ContractContactAssignment::where('contact_id', $ticket->contact_id)
            ->where('status', 'active')
            ->whereHas('contract', function ($query) {
                $query->whereIn('status', ['active', 'signed'])
                    ->whereDate('start_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', now());
                    });
            })
            ->first();
    }

    /**
     * Update invoice totals
     */
    protected function updateInvoiceTotals(Invoice $invoice): void
    {
        // Use the Invoice model's built-in method to recalculate totals
        // This properly calculates subtotal from items, adds tax, subtracts discount,
        // and updates the 'amount' column
        $invoice->recalculateTotals();
    }

    /**
     * Preview billing calculation without creating invoice
     */
    public function previewBilling(Ticket $ticket): array
    {
        $strategy = $this->determineBillingStrategy($ticket, []);
        $timeEntries = $ticket->timeEntries()->where('billable', true)->where('is_billed', false)->get();
        $contractAssignment = $this->getContractAssignment($ticket);
        
        $preview = [
            'strategy' => $strategy,
            'client' => $ticket->client->name,
            'ticket_number' => $ticket->number,
            'ticket_subject' => $ticket->subject,
            'line_items' => [],
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'warnings' => [],
        ];

        // Validate contract
        $validation = $this->validateContract($ticket);
        if (!$validation['valid']) {
            $preview['warnings'] = $validation['warnings'];
        }

        // Calculate based on strategy
        if ($strategy === 'time_based' || $strategy === 'mixed') {
            $totalHours = $timeEntries->sum('hours_worked');
            $minHours = config('billing.ticket.min_billable_hours', 0.25);
            $roundTo = config('billing.ticket.round_hours_to', 0.25);
            
            $billableHours = max($totalHours, $minHours);
            if ($roundTo > 0) {
                $billableHours = ceil($billableHours / $roundTo) * $roundTo;
            }
            
            $hourlyRate = $this->getHourlyRate($ticket, $ticket->client);
            $amount = $billableHours * $hourlyRate;
            
            $preview['line_items'][] = [
                'description' => 'Time-based billing',
                'quantity' => $billableHours,
                'unit' => 'hours',
                'rate' => $hourlyRate,
                'amount' => $amount,
                'details' => [
                    'actual_hours' => $totalHours,
                    'minimum_hours' => $minHours,
                    'rounded_hours' => $billableHours,
                    'time_entries_count' => $timeEntries->count(),
                ],
            ];
            
            $preview['subtotal'] += $amount;
        }

        if ($strategy === 'per_ticket' || $strategy === 'mixed') {
            $perTicketRate = $contractAssignment->per_ticket_rate ?? 0;
            
            $preview['line_items'][] = [
                'description' => 'Per-ticket flat rate',
                'quantity' => 1,
                'unit' => 'ticket',
                'rate' => $perTicketRate,
                'amount' => $perTicketRate,
                'details' => [
                    'contract_id' => $contractAssignment->contract_id ?? null,
                ],
            ];
            
            $preview['subtotal'] += $perTicketRate;
        }

        // Tax calculation (simplified - you may have complex tax logic)
        $taxRate = 0; // Get from client or config
        $preview['tax'] = $preview['subtotal'] * ($taxRate / 100);
        $preview['total'] = $preview['subtotal'] + $preview['tax'];

        return $preview;
    }

    /**
     * Validate contract before billing
     */
    protected function validateContract(Ticket $ticket): array
    {
        $warnings = [];
        $valid = true;

        // Check if client exists
        if (!$ticket->client) {
            $warnings[] = 'No client associated with this ticket';
            $valid = false;
        }

        // Check for active contract
        $contractAssignment = $this->getContractAssignment($ticket);
        if (!$contractAssignment) {
            $warnings[] = 'No active contract found for this contact';
            // This is a warning, not a hard failure - may still bill by time
        }

        // Check if contract is active
        if ($contractAssignment && $contractAssignment->status !== 'active') {
            $warnings[] = 'Contract status is: ' . $contractAssignment->status;
            $valid = false;
        }

        // Check prepaid hours balance
        if ($contractAssignment && $contractAssignment->max_support_hours_per_month > 0) {
            $hoursUsed = $contractAssignment->current_month_support_hours ?? 0;
            $hoursAllowed = $contractAssignment->max_support_hours_per_month;
            $hoursRemaining = $hoursAllowed - $hoursUsed;
            
            if ($hoursRemaining > 0) {
                $warnings[] = "Client has {$hoursRemaining} prepaid hours remaining this month ({$hoursUsed}/{$hoursAllowed} used)";
            }
        }

        // Check included tickets limit
        if ($contractAssignment && $contractAssignment->max_tickets_per_month > 0) {
            $ticketsUsed = $contractAssignment->current_month_tickets ?? 0;
            $ticketsAllowed = $contractAssignment->max_tickets_per_month;
            
            if ($ticketsUsed < $ticketsAllowed) {
                $warnings[] = "This ticket may be covered under included tickets ({$ticketsUsed}/{$ticketsAllowed} used this month)";
            }
        }

        return [
            'valid' => $valid,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check if ticket can be billed
     */
    public function canBillTicket(Ticket $ticket): bool
    {
        if (!config('billing.ticket.enabled', true)) {
            return false;
        }

        if ($ticket->invoice_id) {
            return false;
        }

        if (!$ticket->billable) {
            return false;
        }

        if (!$ticket->client_id) {
            return false;
        }

        // Check if there's something to bill
        $hasTimeEntries = $ticket->timeEntries()->where('billable', true)->where('is_billed', false)->exists();
        $contractAssignment = $this->getContractAssignment($ticket);
        $hasPerTicketRate = $contractAssignment && $contractAssignment->per_ticket_rate > 0;

        return $hasTimeEntries || $hasPerTicketRate;
    }

    /**
     * Check if ticket should be billed based on contract limits
     * Returns: ['should_bill' => bool, 'reason' => string, 'covered_by' => string|null]
     */
    public function shouldBillTicket(Ticket $ticket): array
    {
        // First check if CAN bill at all
        if (!$this->canBillTicket($ticket)) {
            return [
                'should_bill' => false,
                'reason' => 'Ticket cannot be billed',
                'covered_by' => null,
            ];
        }

        $contractAssignment = $this->getContractAssignment($ticket);
        
        // No contract? Bill normally
        if (!$contractAssignment) {
            return [
                'should_bill' => true,
                'reason' => 'No contract limits apply',
                'covered_by' => null,
            ];
        }

        // ABUSE DETECTION - Check first before allowing "unlimited" coverage
        $abuseCheck = $this->checkForAbuse($contractAssignment);
        if ($abuseCheck['is_abuse']) {
            $this->log('Abuse detected - forcing billing despite unlimited contract', [
                'ticket_id' => $ticket->id,
                'contract_id' => $contractAssignment->contract_id,
                'reason' => $abuseCheck['reason'],
            ], 'warning');

            return [
                'should_bill' => true,
                'reason' => 'Billing due to usage limits: ' . $abuseCheck['reason'],
                'covered_by' => null,
                'abuse_detected' => true,
            ];
        }

        // Check included tickets (per-ticket billing)
        if ($contractAssignment->max_tickets_per_month > 0) {
            $ticketsUsed = $contractAssignment->current_month_tickets ?? 0;
            $ticketsAllowed = $contractAssignment->max_tickets_per_month;
            
            // If within included tickets, DON'T bill
            if ($ticketsUsed < $ticketsAllowed) {
                return [
                    'should_bill' => false,
                    'reason' => "Covered by included tickets ({$ticketsUsed}/{$ticketsAllowed} used)",
                    'covered_by' => 'included_tickets',
                ];
            }
        } elseif ($contractAssignment->max_tickets_per_month === -1) {
            // Unlimited tickets - still don't bill, but track
            return [
                'should_bill' => false,
                'reason' => "Covered by unlimited ticket plan",
                'covered_by' => 'unlimited_tickets',
            ];
        }

        // Check prepaid hours (time-based billing)
        if ($contractAssignment->max_support_hours_per_month > 0) {
            $timeEntries = $ticket->timeEntries()->where('billable', true)->where('is_billed', false)->get();
            $ticketHours = $timeEntries->sum('hours_worked');
            
            $hoursUsed = $contractAssignment->current_month_support_hours ?? 0;
            $hoursAllowed = $contractAssignment->max_support_hours_per_month;
            $hoursRemaining = $hoursAllowed - $hoursUsed;
            
            // If ticket hours are covered by remaining prepaid, DON'T bill
            if ($ticketHours <= $hoursRemaining) {
                return [
                    'should_bill' => false,
                    'reason' => "Covered by prepaid hours ({$ticketHours}h used, {$hoursRemaining}h remaining)",
                    'covered_by' => 'prepaid_hours',
                ];
            }
            
            // Partial coverage - only bill excess hours
            if ($hoursRemaining > 0) {
                return [
                    'should_bill' => true,
                    'reason' => "Partial billing: {$hoursRemaining}h covered, " . ($ticketHours - $hoursRemaining) . "h billable",
                    'covered_by' => 'partial',
                ];
            }
        } elseif ($contractAssignment->max_support_hours_per_month === -1) {
            // Unlimited hours - still don't bill, but track
            return [
                'should_bill' => false,
                'reason' => "Covered by unlimited support hours plan",
                'covered_by' => 'unlimited_hours',
            ];
        }

        // No limits apply or limits exceeded - bill normally
        return [
            'should_bill' => true,
            'reason' => 'Contract limits exceeded or not applicable',
            'covered_by' => null,
        ];
    }

    /**
     * Check for potential abuse of unlimited contracts
     * 
     * @return array ['is_abuse' => bool, 'reason' => string]
     */
    protected function checkForAbuse(ContractContactAssignment $contractAssignment): array
    {
        $issues = [];

        // Check hard limits on "unlimited" plans
        if ($contractAssignment->max_tickets_per_month === -1) {
            if ($contractAssignment->isAbusingTicketLimit()) {
                $issues[] = "Excessive ticket usage ({$contractAssignment->current_month_tickets} tickets this month)";
            }
        }

        if ($contractAssignment->max_support_hours_per_month === -1) {
            if ($contractAssignment->isAbusingHoursLimit()) {
                $issues[] = "Excessive hours usage ({$contractAssignment->current_month_support_hours} hours this month)";
            }
        }

        // Check for anomalous patterns
        $anomaly = $contractAssignment->detectAnomalousUsage();
        if ($anomaly['is_anomalous'] && $anomaly['severity'] === 'critical') {
            $issues[] = "Critical usage anomaly detected: " . implode(', ', $anomaly['alerts']);
        }

        return [
            'is_abuse' => !empty($issues),
            'reason' => implode('; ', $issues),
            'severity' => $anomaly['severity'] ?? 'normal',
        ];
    }

    /**
     * Deduct ticket usage from contract limits
     */
    protected function deductFromContractLimits(Ticket $ticket): void
    {
        $contractAssignment = $this->getContractAssignment($ticket);
        
        if (!$contractAssignment) {
            return;
        }

        // Deduct from ticket count
        if ($contractAssignment->max_tickets_per_month > 0) {
            $contractAssignment->recordTicketCreation(); // This increments current_month_tickets
            
            $this->log('Deducted from included tickets', [
                'ticket_id' => $ticket->id,
                'contract_id' => $contractAssignment->contract_id,
                'tickets_used' => $contractAssignment->current_month_tickets,
                'tickets_allowed' => $contractAssignment->max_tickets_per_month,
            ]);
        }

        // Deduct from support hours
        if ($contractAssignment->max_support_hours_per_month > 0) {
            $timeEntries = $ticket->timeEntries()->where('billable', true)->get();
            $ticketHours = $timeEntries->sum('hours_worked');
            
            if ($ticketHours > 0) {
                $contractAssignment->recordSupportHours($ticketHours);
                
                $this->log('Deducted from prepaid hours', [
                    'ticket_id' => $ticket->id,
                    'contract_id' => $contractAssignment->contract_id,
                    'hours_used' => $ticketHours,
                    'total_hours_used' => $contractAssignment->current_month_support_hours,
                    'hours_allowed' => $contractAssignment->max_support_hours_per_month,
                ]);
            }
        }

        // Log audit trail
        BillingAuditLog::logBillingAction(
            action: 'contract_limit_deducted',
            ticketId: $ticket->id,
            description: "Ticket covered by contract limits - no invoice generated",
            metadata: [
                'contract_id' => $contractAssignment->contract_id,
                'tickets_used' => $contractAssignment->current_month_tickets,
                'hours_used' => $contractAssignment->current_month_support_hours,
            ]
        );
    }

    /**
     * Log billing activity
     */
    protected function log(string $message, array $context = [], string $level = 'info'): void
    {
        if (!config('billing.logging.enabled', true)) {
            return;
        }

        $channel = config('billing.logging.channel', 'stack');
        
        Log::channel($channel)->$level('[TicketBilling] ' . $message, $context);
    }
}
