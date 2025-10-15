<?php

namespace App\Livewire\Financial;

use App\Domains\Core\Services\NavigationService;
use App\Domains\Financial\Services\PaymentApplicationService;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PaymentCreate extends Component
{
    public $client_id = '';
    public $invoice_id = '';
    public $amount = '';
    public $currency = 'USD';
    public $payment_method = '';
    public $gateway = 'manual';
    public $payment_date;
    public $payment_reference = '';
    public $gateway_transaction_id = '';
    public $gateway_fee = '';
    public $notes = '';
    public $auto_apply = false;

    public $invoices = [];
    
    public $allocations = [];
    public $selectedInvoices = [];
    public $allocationMode = 'manual';
    public $totalAllocated = 0;
    public $remainingAmount = 0;

    protected function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|string',
            'gateway' => 'nullable|string',
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:255',
            'gateway_transaction_id' => 'nullable|string|max:255',
            'gateway_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'auto_apply' => 'nullable|boolean',
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|numeric|min:0',
        ];
    }

    public function validateAllocations()
    {
        $hasAllocations = false;
        
        foreach ($this->allocations as $invoiceId => $amount) {
            $allocatedAmount = floatval($amount);
            
            if ($allocatedAmount <= 0) {
                continue;
            }
            
            $hasAllocations = true;
            $invoice = $this->invoices->firstWhere('id', $invoiceId);
            
            if (!$invoice) {
                return "Invalid invoice selected.";
            }
            
            $balance = $invoice->getBalance();
            
            if ($allocatedAmount > $balance) {
                return "Amount allocated to invoice {$invoice->getFullNumber()} exceeds its balance of \$" . number_format($balance, 2);
            }
        }
        
        if ($this->totalAllocated > $this->amount) {
            return "Total allocation (\$" . number_format($this->totalAllocated, 2) . ") exceeds payment amount (\$" . number_format($this->amount, 2) . ")";
        }
        
        return null;
    }

    protected $messages = [
        'client_id.required' => 'Please select a client.',
        'amount.required' => 'Please enter a payment amount.',
        'amount.min' => 'Amount must be greater than zero.',
        'payment_method.required' => 'Please select how this payment was made.',
        'payment_date.required' => 'Please select a payment date.',
    ];

    public function mount($clientId = null, $invoiceId = null)
    {
        $this->payment_date = now()->format('Y-m-d');

        $selectedClient = app(NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            $this->client_id = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
        } elseif ($clientId) {
            $this->client_id = $clientId;
        }

        if ($invoiceId) {
            $this->invoice_id = $invoiceId;
            $this->loadInvoiceDetails($invoiceId);
        }

        $this->loadInvoicesForClient();
    }

    public function updatedClientId($value)
    {
        $this->invoice_id = '';
        $this->amount = '';
        $this->allocations = [];
        $this->loadInvoicesForClient();
        $this->calculateTotals();
    }

    public function updatedInvoiceId($value)
    {
        if ($value) {
            $this->loadInvoiceDetails($value);
        }
    }

    public function updatedAmount($value)
    {
        $this->calculateTotals();
    }

    public function updatedAllocations()
    {
        $this->calculateTotals();
    }

    public function updatedSelectedInvoices($value, $invoiceId)
    {
        if ($value) {
            // Checkbox was checked - set allocation to full balance
            $invoice = $this->invoices->firstWhere('id', $invoiceId);
            if ($invoice) {
                $this->allocations[$invoiceId] = $invoice->getBalance();
            }
        } else {
            // Checkbox was unchecked - clear allocation
            $this->allocations[$invoiceId] = 0;
        }
        $this->calculateTotals();
    }

    protected function loadInvoicesForClient()
    {
        if ($this->client_id) {
            $this->invoices = Invoice::where('company_id', Auth::user()->company_id)
                ->where('client_id', $this->client_id)
                ->whereIn('status', ['Sent', 'Partial', 'sent', 'partial'])
                ->get()
                ->filter(function($invoice) {
                    return $invoice->getBalance() > 0;
                })
                ->sortBy('due_date')
                ->values();
        } else {
            $this->invoices = collect([]);
        }
    }

    public function payAllInvoices()
    {
        if (!$this->amount || $this->amount <= 0) {
            Flux::toast('Please enter a payment amount first.', variant: 'warning');
            return;
        }

        $this->allocationMode = 'auto_all';
        $this->distributePaymentAcrossInvoices($this->invoices);
        Flux::toast('Payment distributed across all invoices.');
    }

    public function payOldestFirst()
    {
        if (!$this->amount || $this->amount <= 0) {
            Flux::toast('Please enter a payment amount first.', variant: 'warning');
            return;
        }

        $this->allocationMode = 'auto_oldest';
        $sortedInvoices = $this->invoices->sortBy('due_date');
        $this->distributePaymentAcrossInvoices($sortedInvoices);
        Flux::toast('Payment allocated to oldest invoices first.');
    }

    public function clearAllocation()
    {
        $this->allocations = [];
        $this->allocationMode = 'manual';
        $this->calculateTotals();
        Flux::toast('Allocation cleared.');
    }

    protected function distributePaymentAcrossInvoices($invoices)
    {
        $remaining = (float) $this->amount;
        $this->allocations = [];
        $this->selectedInvoices = [];

        foreach ($invoices as $invoice) {
            if ($remaining <= 0) {
                break;
            }

            $balance = $invoice->getBalance();
            if ($balance <= 0) {
                continue;
            }

            $allocate = min($balance, $remaining);
            $this->allocations[$invoice->id] = number_format($allocate, 2, '.', '');
            $this->selectedInvoices[$invoice->id] = true;
            $remaining -= $allocate;
        }

        $this->calculateTotals();
    }

    protected function calculateTotals()
    {
        $this->totalAllocated = array_sum(array_map('floatval', $this->allocations));
        $this->remainingAmount = (float) $this->amount - $this->totalAllocated;
    }

    public function getAllocationStatus($invoiceId, $balance)
    {
        $allocated = floatval($this->allocations[$invoiceId] ?? 0);
        
        if ($allocated <= 0) {
            return ['color' => 'zinc', 'label' => 'Not Applied'];
        }
        
        if ($allocated > $balance) {
            return ['color' => 'red', 'label' => 'Over-allocated'];
        }
        
        if ($allocated >= $balance) {
            return ['color' => 'green', 'label' => 'Full Payment'];
        }
        
        return ['color' => 'amber', 'label' => 'Partial Payment'];
    }

    protected function loadInvoiceDetails($invoiceId)
    {
        $invoice = Invoice::where('company_id', Auth::user()->company_id)
            ->find($invoiceId);

        if ($invoice) {
            $this->client_id = $invoice->client_id;
            $remainingBalance = $invoice->getBalance();
            $this->amount = $remainingBalance > 0 ? number_format($remainingBalance, 2, '.', '') : '';
        }
    }

    #[Computed]
    public function clients()
    {
        return Client::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function paymentMethods()
    {
        return [
            'credit_card' => 'Credit Card',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'cash' => 'Cash',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'other' => 'Other',
        ];
    }

    #[Computed]
    public function gateways()
    {
        return [
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'square' => 'Square',
            'authorize_net' => 'Authorize.Net',
            'manual' => 'Manual Entry',
        ];
    }

    #[Computed]
    public function currencies()
    {
        return [
            'USD' => 'USD - US Dollar',
            'EUR' => 'EUR - Euro',
            'GBP' => 'GBP - British Pound',
            'CAD' => 'CAD - Canadian Dollar',
        ];
    }

    public function save()
    {
        $this->validate();

        // Validate allocations
        $validationError = $this->validateAllocations();
        if ($validationError) {
            Flux::toast($validationError, variant: 'danger');
            return;
        }

        // Create the payment first
        $payment = Payment::create([
            'company_id' => Auth::user()->company_id,
            'client_id' => $this->client_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'gateway' => $this->gateway ?: 'manual',
            'payment_date' => $this->payment_date,
            'payment_reference' => $this->payment_reference ?: $this->generatePaymentReference(),
            'gateway_transaction_id' => $this->gateway_transaction_id ?: null,
            'gateway_fee' => $this->gateway_fee ?: null,
            'notes' => $this->notes ?: null,
            'status' => 'completed',
            'processed_by' => Auth::id(),
        ]);

        // Apply allocations if any
        if (count($this->allocations) > 0) {
            $paymentApplicationService = app(PaymentApplicationService::class);
            
            foreach ($this->allocations as $invoiceId => $amount) {
                $allocatedAmount = floatval($amount);
                if ($allocatedAmount > 0) {
                    $invoice = $this->invoices->firstWhere('id', $invoiceId);
                    if ($invoice) {
                        $paymentApplicationService->applyPaymentToInvoice($payment, $invoice, $allocatedAmount);
                    }
                }
            }

            // Create credit from remaining amount if any
            if ($this->remainingAmount > 0) {
                $creditService = app(\App\Domains\Financial\Services\ClientCreditService::class);
                $creditService->createCreditFromOverpayment($payment, $this->remainingAmount);
            }
        } elseif ($this->auto_apply) {
            // Use auto-apply if no manual allocations
            $paymentService = app(\App\Domains\Financial\Services\PaymentService::class);
            $paymentService->autoApplyPayment($payment);
        }

        Flux::toast('Payment recorded successfully.');
        return redirect()->route('financial.payments.show', $payment);
    }

    protected function generatePaymentReference()
    {
        $lastPayment = Payment::where('company_id', Auth::user()->company_id)
            ->whereNotNull('payment_reference')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPayment && preg_match('/PAY-(\d+)/', $lastPayment->payment_reference, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return 'PAY-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('livewire.financial.payment-create');
    }
}
