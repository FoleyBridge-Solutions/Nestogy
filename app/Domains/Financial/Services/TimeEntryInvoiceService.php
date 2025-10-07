<?php

namespace App\Domains\Financial\Services;

use App\Domains\Financial\Models\RateCard;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimeEntryInvoiceService
{
    public function generateInvoiceFromTimeEntries(array $timeEntryIds, int $clientId, array $options = []): Invoice
    {
        return DB::transaction(function () use ($timeEntryIds, $clientId, $options) {
            $client = Client::findOrFail($clientId);
            $timeEntries = TicketTimeEntry::whereIn('id', $timeEntryIds)
                ->where('company_id', $client->company_id)
                ->whereNull('invoice_id')
                ->with(['ticket', 'user'])
                ->get();

            if ($timeEntries->isEmpty()) {
                throw new \Exception('No uninvoiced time entries found for the specified IDs.');
            }

            if ($timeEntries->pluck('ticket.client_id')->unique()->count() > 1) {
                throw new \Exception('All time entries must belong to the same client.');
            }

            $invoice = $this->createInvoice($client, $options);
            
            $groupedEntries = $this->groupTimeEntries($timeEntries, $options['groupBy'] ?? 'ticket');
            
            foreach ($groupedEntries as $group) {
                $this->createInvoiceItem($invoice, $group, $client);
            }

            $this->linkTimeEntriesToInvoice($timeEntries, $invoice);
            
            $this->updateInvoiceTotals($invoice);

            Log::info('Invoice generated from time entries', [
                'invoice_id' => $invoice->id,
                'client_id' => $clientId,
                'time_entry_count' => $timeEntries->count(),
                'amount' => $invoice->amount,
            ]);

            return $invoice->fresh(['items', 'client']);
        });
    }

    protected function createInvoice(Client $client, array $options): Invoice
    {
        $lastInvoice = Invoice::where('company_id', $client->company_id)
            ->orderBy('number', 'desc')
            ->first();

        $invoice = Invoice::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'prefix' => $options['prefix'] ?? 'INV',
            'number' => $lastInvoice ? $lastInvoice->number + 1 : 1001,
            'date' => $options['date'] ?? now(),
            'due' => $options['due_date'] ?? now()->addDays($client->net_terms ?? 30),
            'due_date' => $options['due_date'] ?? now()->addDays($client->net_terms ?? 30),
            'status' => Invoice::STATUS_DRAFT,
            'currency_code' => $client->currency_code ?? 'USD',
            'note' => $options['note'] ?? null,
            'url_key' => \Illuminate\Support\Str::random(32),
        ]);

        return $invoice;
    }

    protected function groupTimeEntries(Collection $timeEntries, string $groupBy): Collection
    {
        $result = match ($groupBy) {
            'ticket' => $timeEntries->groupBy('ticket_id')->map(function ($entries, $ticketId) {
                return [
                    'type' => 'ticket',
                    'ticket_id' => $ticketId,
                    'ticket' => $entries->first()->ticket,
                    'entries' => $entries,
                ];
            })->values(),
            'date' => $timeEntries->groupBy('work_date')->map(function ($entries, $date) {
                return [
                    'type' => 'date',
                    'date' => $date,
                    'entries' => $entries,
                ];
            })->values(),
            'user' => $timeEntries->groupBy('user_id')->map(function ($entries, $userId) {
                return [
                    'type' => 'user',
                    'user_id' => $userId,
                    'user' => $entries->first()->user,
                    'entries' => $entries,
                ];
            })->values(),
            default => collect([[
                'type' => 'combined',
                'entries' => $timeEntries,
            ]]),
        };

        return $result;
    }

    protected function createInvoiceItem(Invoice $invoice, array $group, Client $client): InvoiceItem
    {
        $entries = $group['entries'];
        $totalHours = 0;
        $totalAmount = 0;

        foreach ($entries as $entry) {
            $rateCard = $this->getApplicableRateCard($client, $entry);
            
            if ($rateCard) {
                $billableHours = $rateCard->calculateBillableHours($entry->hours_worked);
                $amount = $rateCard->calculateAmount($entry->hours_worked);
            } else {
                $billableHours = $entry->hours_worked;
                $rate = $entry->hourly_rate ?? $client->hourly_rate ?? 100;
                $amount = $billableHours * $rate;
            }

            $totalHours += $billableHours;
            $totalAmount += $amount;
        }

        $description = $this->generateItemDescription($group);

        return InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'description' => $description,
            'quantity' => round($totalHours, 2),
            'price' => $totalHours > 0 ? round($totalAmount / $totalHours, 2) : 0,
            'amount' => round($totalAmount, 2),
            'unit' => 'hours',
        ]);
    }

    protected function getApplicableRateCard(Client $client, TicketTimeEntry $entry): ?RateCard
    {
        $serviceType = $entry->work_type ?? RateCard::SERVICE_TYPE_STANDARD;
        $date = $entry->work_date ?? now();

        return RateCard::findApplicableRate($client->id, $serviceType, $date);
    }

    protected function generateItemDescription(array $group): string
    {
        switch ($group['type']) {
            case 'ticket':
                $ticket = $group['ticket'];
                $entries = $group['entries'];
                $description = "Ticket #{$ticket->number}: {$ticket->subject}";
                
                if ($entries->count() > 1) {
                    $description .= "\n" . $entries->count() . ' time entries';
                }
                
                return $description;

            case 'date':
                $date = Carbon::parse($group['date'])->format('M d, Y');
                $entries = $group['entries'];
                return "Services provided on {$date} ({$entries->count()} entries)";

            case 'user':
                $user = $group['user'];
                $entries = $group['entries'];
                return "Services by {$user->name} ({$entries->count()} entries)";

            case 'combined':
            default:
                $entries = $group['entries'];
                $ticketCount = $entries->pluck('ticket_id')->unique()->count();
                return "Professional Services ({$entries->count()} time entries across {$ticketCount} tickets)";
        }
    }

    protected function linkTimeEntriesToInvoice(Collection $timeEntries, Invoice $invoice): void
    {
        $timeEntries->each(function ($entry) use ($invoice) {
            $entry->update([
                'invoice_id' => $invoice->id,
                'invoiced_at' => now(),
            ]);
        });
    }

    protected function updateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = $invoice->items()->sum('amount');
        $discountAmount = $invoice->discount_amount ?? 0;
        $total = $subtotal - $discountAmount;

        $invoice->update([
            'amount' => $total,
        ]);
    }

    public function previewInvoice(array $timeEntryIds, int $clientId, array $options = []): array
    {
        $client = Client::findOrFail($clientId);
        $timeEntries = TicketTimeEntry::whereIn('id', $timeEntryIds)
            ->where('company_id', $client->company_id)
            ->whereNull('invoice_id')
            ->with(['ticket', 'user'])
            ->get();

        if ($timeEntries->isEmpty()) {
            throw new \Exception('No uninvoiced time entries found for the specified IDs.');
        }

        $groupedEntries = $this->groupTimeEntries($timeEntries, $options['groupBy'] ?? 'ticket');
        
        $items = [];
        $subtotal = 0;

        foreach ($groupedEntries as $group) {
            $entries = $group['entries'];
            $totalHours = 0;
            $totalAmount = 0;

            foreach ($entries as $entry) {
                $rateCard = $this->getApplicableRateCard($client, $entry);
                
                if ($rateCard) {
                    $billableHours = $rateCard->calculateBillableHours($entry->hours_worked);
                    $amount = $rateCard->calculateAmount($entry->hours_worked);
                } else {
                    $billableHours = $entry->hours_worked;
                    $rate = $entry->hourly_rate ?? $client->hourly_rate ?? 100;
                    $amount = $billableHours * $rate;
                }

                $totalHours += $billableHours;
                $totalAmount += $amount;
            }

            $items[] = [
                'description' => $this->generateItemDescription($group),
                'quantity' => round($totalHours, 2),
                'price' => $totalHours > 0 ? round($totalAmount / $totalHours, 2) : 0,
                'amount' => round($totalAmount, 2),
            ];

            $subtotal += $totalAmount;
        }

        return [
            'client' => $client,
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'discount' => $options['discount_amount'] ?? 0,
            'total' => round($subtotal - ($options['discount_amount'] ?? 0), 2),
            'time_entry_count' => $timeEntries->count(),
            'total_hours' => round($timeEntries->sum('hours_worked'), 2),
        ];
    }

    public function getUninvoicedTimeEntries(int $clientId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = TicketTimeEntry::whereHas('ticket', function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })
            ->whereNull('invoice_id')
            ->where('billable', true)
            ->with(['ticket', 'user']);

        if ($startDate) {
            $query->where('work_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('work_date', '<=', $endDate);
        }

        return $query->orderBy('work_date')->get();
    }

    public function bulkGenerateInvoices(array $clientIds, ?Carbon $startDate = null, ?Carbon $endDate = null, array $options = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        foreach ($clientIds as $clientId) {
            try {
                $timeEntries = $this->getUninvoicedTimeEntries($clientId, $startDate, $endDate);

                if ($timeEntries->isEmpty()) {
                    $results['skipped'][] = [
                        'client_id' => $clientId,
                        'reason' => 'No uninvoiced time entries',
                    ];
                    continue;
                }

                $invoice = $this->generateInvoiceFromTimeEntries(
                    $timeEntries->pluck('id')->toArray(),
                    $clientId,
                    $options
                );

                $results['success'][] = [
                    'client_id' => $clientId,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->amount,
                ];

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'client_id' => $clientId,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to generate invoice for client', [
                    'client_id' => $clientId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
