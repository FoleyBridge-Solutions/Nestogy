<?php

namespace App\Livewire\Financial;

use App\Models\Invoice;
use App\Models\Payment;
use App\Domains\Financial\Services\InvoiceService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoiceShow extends Component
{
    public Invoice $invoice;
    public $totals = [];
    public $showPaymentModal = false;
    public $showEmailModal = false;

    // Payment form fields
    public $paymentAmount;
    public $paymentMethod = 'credit_card';
    public $paymentDate;
    public $paymentReference;
    public $paymentNotes;

    // Email form fields
    public $emailTo;
    public $emailSubject;
    public $emailMessage;
    public $attachPdf = true;
    
    protected $invoiceService;
    
    public function mount(Invoice $invoice)
    {
        logger('InvoiceShow mount called with invoice ID: ' . $invoice->id);
        
        $this->authorize('view', $invoice);
        
        $this->invoice = $invoice;
        $this->loadInvoiceData();
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentAmount = round($this->totals['balance'] ?? 0, 2);

        // Initialize email form fields
        $companyName = config('app.name');
        if (Auth::check() && Auth::user()->company) {
            $companyName = Auth::user()->company->name;
        }
        
        $this->emailTo = $this->invoice->client->email ?? '';
        $this->emailSubject = "Invoice #" . ($this->invoice->invoice_number ?? $this->invoice->number) . " from " . $companyName;
        $this->emailMessage = "Please find attached invoice #" . ($this->invoice->invoice_number ?? $this->invoice->number) . " for $" . number_format($this->invoice->amount, 2) . ".\n\nThe invoice is due on " . ($this->invoice->due_date?->format('F j, Y') ?? 'receipt') . ".\n\nThank you for your business!";
        
        logger('InvoiceShow mount completed');
    }
    
    public function loadInvoiceData()
    {
        try {
            $this->invoice->load([
                'client',
                'category',
                'items' => function ($query) {
                    $query->orderBy('order');
                },
                'payments' => function ($query) {
                    $query->orderBy('payment_date', 'desc');
                },
            ]);
            
            $this->invoiceService = app(InvoiceService::class);
            $this->totals = $this->invoiceService->calculateInvoiceTotals($this->invoice);
        } catch (\Exception $e) {
            logger('InvoiceShow loadInvoiceData error: ' . $e->getMessage());
            // Fallback totals calculation with proper rounding
            $this->totals = [
                'subtotal' => round($this->invoice->items->sum('amount'), 2),
                'total' => round($this->invoice->amount, 2),
                'paid' => round($this->invoice->payments->sum('amount'), 2),
                'balance' => round($this->invoice->amount - $this->invoice->payments->sum('amount'), 2),
            ];
        }
    }
    
    public function downloadPdf()
    {
        return redirect()->route('financial.invoices.pdf', $this->invoice);
    }
    
    public function printInvoice()
    {
        // Open PDF in a new tab/window for printing
        $this->dispatch('print-invoice', [
            'url' => route('financial.invoices.pdf', $this->invoice)
        ]);
    }
    
    public function sendEmail()
    {
        $this->showEmailModal = true;
    }

    public function sendInvoiceEmail()
    {
        // Force log to file directly to ensure it works
        file_put_contents(storage_path('logs/debug.log'), 
            '[' . now() . '] sendInvoiceEmail called - emailTo: ' . $this->emailTo . PHP_EOL, 
            FILE_APPEND
        );

        try {
            $this->validate([
                'emailTo' => 'required|email',
                'emailSubject' => 'required|string|max:255',
                'emailMessage' => 'required|string|max:2000',
                'attachPdf' => 'boolean'
            ]);
            
            file_put_contents(storage_path('logs/debug.log'), 
                '[' . now() . '] Validation passed, proceeding with email send' . PHP_EOL, 
                FILE_APPEND
            );
        } catch (\Exception $e) {
            file_put_contents(storage_path('logs/debug.log'), 
                '[' . now() . '] Validation failed: ' . $e->getMessage() . PHP_EOL, 
                FILE_APPEND
            );
            return;
        }

        try {
            file_put_contents(storage_path('logs/debug.log'), 
                '[' . now() . '] About to authorize and send email' . PHP_EOL, 
                FILE_APPEND
            );
            
            $this->authorize('view', $this->invoice);

            // Use the EmailService to send the invoice
            $emailService = app(\App\Services\EmailService::class);
            
            file_put_contents(storage_path('logs/debug.log'), 
                '[' . now() . '] Calling EmailService->sendInvoiceEmail' . PHP_EOL, 
                FILE_APPEND
            );
            
            $result = $emailService->sendInvoiceEmail($this->invoice, [
                'to' => $this->emailTo,
                'subject' => $this->emailSubject,
                'message' => $this->emailMessage,
                'attach_pdf' => $this->attachPdf
            ]);

            file_put_contents(storage_path('logs/debug.log'), 
                '[' . now() . '] EmailService returned: ' . ($result ? 'true' : 'false') . PHP_EOL, 
                FILE_APPEND
            );

            if ($result) {
                // Update status to sent if it was draft
                if ($this->invoice->status === 'Draft') {
                    $this->invoiceService->updateInvoiceStatus($this->invoice, 'Sent');
                    $this->loadInvoiceData(); // Refresh data
                }

                $this->showEmailModal = false;

                file_put_contents(storage_path('logs/debug.log'), 
                    '[' . now() . '] About to dispatch success notification' . PHP_EOL, 
                    FILE_APPEND
                );

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Invoice email has been queued for delivery to ' . $this->emailTo . '. The email will be processed in the background.'
                ]);

                Log::info('Invoice emailed via modal', [
                    'invoice_id' => $this->invoice->id,
                    'user_id' => Auth::id(),
                    'recipient' => $this->emailTo
                ]);
            } else {
                throw new \Exception('Email service returned false');
            }

        } catch (\Exception $e) {
            file_put_contents(storage_path('logs/debug.log'), 
                '[' . now() . '] Exception caught: ' . $e->getMessage() . PHP_EOL, 
                FILE_APPEND
            );
            
            Log::error('Invoice email failed via modal', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to send invoice: ' . $e->getMessage()
            ]);
        }
    }
    
    public function duplicateInvoice()
    {
        $this->authorize('create', Invoice::class);
        
        try {
            // Create a duplicate invoice
            $newInvoice = $this->invoice->replicate();
            $newInvoice->status = 'Draft';
            $newInvoice->created_at = now();
            $newInvoice->updated_at = now();
            $newInvoice->save();
            
            // Duplicate invoice items
            foreach ($this->invoice->items as $item) {
                $newItem = $item->replicate();
                $newItem->invoice_id = $newInvoice->id;
                $newItem->save();
            }
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Invoice duplicated successfully'
            ]);
            
            return redirect()->route('financial.invoices.edit', $newInvoice);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to duplicate invoice'
            ]);
        }
    }
    
    public function recordPayment()
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01|max:' . ($this->totals['balance'] ?: 0),
            'paymentMethod' => 'required|in:credit_card,bank_transfer,check,cash,other',
            'paymentDate' => 'required|date',
            'paymentNotes' => 'nullable|string|max:500'
        ]);
        
        try {
            $payment = Payment::create([
                'invoice_id' => $this->invoice->id,
                'client_id' => $this->invoice->client_id,
                'company_id' => $this->invoice->company_id,
                'amount' => $this->paymentAmount,
                'payment_method' => $this->paymentMethod,
                'payment_date' => $this->paymentDate,
                'reference_number' => $this->paymentReference,
                'notes' => $this->paymentNotes,
                'status' => 'completed',
                'created_by' => Auth::id()
            ]);
            
            $this->showPaymentModal = false;
            $this->loadInvoiceData();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Payment recorded successfully'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to record payment'
            ]);
        }
    }
    
    public function markAsSent()
    {
        $this->authorize('update', $this->invoice);
        
        $this->invoice->update(['status' => 'Sent']);
        $this->loadInvoiceData();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Invoice marked as sent'
        ]);
    }
    
    public function sendPhysicalMail()
    {
        $this->authorize('update', $this->invoice);
        
        $this->dispatch('sendPhysicalMail', [
            'invoice_id' => $this->invoice->id
        ]);
    }
    
    #[Computed]
    public function statusColor()
    {
        return match($this->invoice->status) {
            'Draft' => 'zinc',
            'Sent' => 'blue',
            'Paid' => 'green',
            'Overdue' => 'red',
            'Cancelled' => 'gray',
            default => 'zinc'
        };
    }
    
    #[Computed]
    public function daysOverdue()
    {
        if ($this->invoice->status !== 'Overdue' || !$this->invoice->due_date) {
            return 0;
        }
        
        return (int) now()->startOfDay()->diffInDays($this->invoice->due_date);
    }
    
    public function render()
    {
        return view('livewire.financial.invoice-show')
            ->extends('layouts.app')
            ->section('content');
    }
}