<?php

namespace App\Domains\Financial\Services;

use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Create a new invoice
     */
    public function createInvoice(Client $client, array $data): Invoice
    {
        return DB::transaction(function () use ($client, $data) {
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'company_id' => Auth::user()->company_id,
                'category_id' => $data['category_id'] ?? null,
                'prefix' => $data['prefix'] ?? null,
                'number' => $this->generateInvoiceNumber(),
                'scope' => $data['scope'] ?? null,
                'date' => $data['date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays(30)->toDateString(),
                'status' => $data['status'] ?? 'Draft',
                'discount_amount' => $data['discount_amount'] ?? 0,
                'amount' => 0, // Will be calculated when items are added
                'currency_code' => $data['currency_code'] ?? 'USD',
                'note' => $data['note'] ?? null,
            ]);

            Log::info('Invoice created', [
                'invoice_id' => $invoice->id,
                'client_id' => $client->id,
                'user_id' => Auth::id(),
            ]);

            return $invoice;
        });
    }

    /**
     * Update an existing invoice
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice->update($data);

            // Update items if provided
            if (isset($data['items'])) {
                $invoice->items()->delete();

                foreach ($data['items'] as $item) {
                    $invoice->items()->create([
                        'description' => $item['description'],
                        'quantity' => $item['quantity'] ?? 1,
                        'rate' => $item['rate'] ?? 0,
                        'amount' => ($item['quantity'] ?? 1) * ($item['rate'] ?? 0),
                        'tax_rate' => $item['tax_rate'] ?? 0,
                    ]);
                }
            }

            Log::info('Invoice updated', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            return $invoice->load('items');
        });
    }

    /**
     * Send invoice to client
     */
    public function sendInvoice(Invoice $invoice): bool
    {
        try {
            // Update status to sent
            $invoice->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            // Here you would typically send email notification
            // Mail::to($invoice->client->email)->send(new InvoiceMail($invoice));

            Log::info('Invoice sent', [
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'user_id' => Auth::id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return false;
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice, array $paymentData = []): bool
    {
        try {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => $paymentData['paid_at'] ?? now(),
            ]);

            Log::info('Invoice marked as paid', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark invoice as paid', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return false;
        }
    }

    /**
     * Generate invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $company_id = Auth::user()->company_id;
        $year = now()->year;

        $lastInvoice = Invoice::where('company_id', $company_id)
            ->whereYear('created_at', $year)
            ->orderBy('number', 'desc')
            ->first();

        if ($lastInvoice && preg_match('/INV-'.$year.'-(\d+)/', $lastInvoice->number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return 'INV-'.$year.'-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get invoice statistics for client
     */
    public function getInvoiceStats(Client $client): array
    {
        $invoices = Invoice::where('client_id', $client->id);

        return [
            'total_count' => $invoices->count(),
            'draft_count' => $invoices->where('status', 'draft')->count(),
            'sent_count' => $invoices->where('status', 'sent')->count(),
            'paid_count' => $invoices->where('status', 'paid')->count(),
            'overdue_count' => $invoices->where('status', 'sent')
                ->where('due_date', '<', now()->toDateString())->count(),
            'total_amount' => $invoices->sum('amount'),
            'paid_amount' => $invoices->where('status', 'paid')->sum('amount'),
            'outstanding_amount' => $invoices->whereIn('status', ['sent', 'overdue'])->sum('amount'),
        ];
    }

    /**
     * Archive invoice
     */
    public function archiveInvoice(Invoice $invoice): bool
    {
        try {
            $invoice->update(['archived_at' => now()]);

            Log::info('Invoice archived', [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to archive invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return false;
        }
    }

    /**
     * Calculate invoice totals
     */
    public function calculateInvoiceTotals(Invoice $invoice): array
    {
        return [
            'subtotal' => round($invoice->getSubtotal(), 2),
            'discount' => round($invoice->discount_amount, 2),
            'tax' => round($invoice->getTotalTax(), 2),
            'total' => round($invoice->amount, 2),
            'paid' => round($invoice->getTotalPaid(), 2),
            'balance' => round($invoice->getBalance(), 2),
        ];
    }

    /**
     * Duplicate an invoice
     */
    public function duplicateInvoice(Invoice $originalInvoice, array $overrides = []): Invoice
    {
        return DB::transaction(function () use ($originalInvoice, $overrides) {
            try {
                $invoiceData = $originalInvoice->toArray();

                unset($invoiceData['id'], $invoiceData['number'], $invoiceData['created_at'],
                    $invoiceData['updated_at'], $invoiceData['archived_at'], $invoiceData['sent_at'],
                    $invoiceData['paid_at'], $invoiceData['viewed_at'], $invoiceData['due'], $invoiceData['status']);

                $invoiceData = array_merge($invoiceData, $overrides);

                $invoiceData['date'] = $overrides['date'] ?? now()->toDateString();
                $invoiceData['due_date'] = $overrides['due_date'] ?? now()->addDays(30)->toDateString();
                $invoiceData['number'] = $this->generateInvoiceNumber();
                $invoiceData['status'] = 'Draft';

                $newInvoice = Invoice::create($invoiceData);

                $originalItems = $originalInvoice->items()->get();
                foreach ($originalItems as $item) {
                    $itemData = $item->toArray();
                    unset($itemData['id'], $itemData['invoice_id'], $itemData['created_at'], $itemData['updated_at']);
                    $newInvoice->items()->create($itemData);
                }

                $newInvoice->calculateTotals();

                Log::info('Invoice duplicated successfully', [
                    'original_invoice_id' => $originalInvoice->id,
                    'new_invoice_id' => $newInvoice->id,
                    'user_id' => Auth::id(),
                ]);

                return $newInvoice->fresh(['client', 'category', 'items']);

            } catch (\Exception $e) {
                Log::error('Invoice duplication failed', [
                    'original_invoice_id' => $originalInvoice->id,
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id(),
                ]);
                throw new \Exception('Failed to duplicate invoice: '.$e->getMessage());
            }
        });
    }
}
