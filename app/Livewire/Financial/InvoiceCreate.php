<?php

namespace App\Livewire\Financial;

use App\Domains\Financial\Services\InvoiceService;
use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Domains\Core\Services\NavigationService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InvoiceCreate extends Component
{
    // Core Invoice Fields
    public $client_id = '';
    public $category_id = '';
    public $invoice_date;
    public $due_date;
    public $currency_code = 'USD';
    public $payment_terms = 30;
    
    // Invoice Number
    public $prefix = 'INV-';
    public $number = '';
    
    // Items
    public $items = [];
    
    // Pricing
    public $discount_type = 'fixed';
    public $discount_amount = 0;
    public $tax_rate = 0;
    
    // Additional Fields
    public $description = '';
    public $notes = '';
    public $terms_conditions = '';
    
    // UI State
    public $showItemForm = false;
    public $editingItemIndex = null;
    public $itemForm = [
        'name' => '',
        'description' => '',
        'quantity' => 1,
        'unit_price' => 0,
        'tax_rate' => 0,
    ];

    protected function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'category_id' => 'required|exists:categories,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }

    protected $messages = [
        'client_id.required' => 'Please select a client.',
        'category_id.required' => 'Please select a category.',
        'items.required' => 'Please add at least one item.',
        'items.min' => 'Invoice must have at least one item.',
    ];

    public function mount($clientId = null)
    {
        // Set default dates
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays($this->payment_terms)->format('Y-m-d');
        
        // Generate invoice number
        $this->generateInvoiceNumber();
        
        // Set default category
        $defaultCategory = Category::where('company_id', Auth::user()->company_id)
            ->where('type', 'invoice')
            ->where('archived_at', null)
            ->first();
        if ($defaultCategory) {
            $this->category_id = $defaultCategory->id;
        }
        
        // Pre-select client from parameter or session
        if ($clientId) {
            $this->client_id = $clientId;
        } else {
            $selectedClient = app(NavigationService::class)->getSelectedClient();
            if ($selectedClient) {
                $this->client_id = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
            }
        }
    }

    public function generateInvoiceNumber()
    {
        $lastInvoice = Invoice::where('company_id', Auth::user()->company_id)
            ->orderBy('number', 'desc')
            ->first();

        if ($lastInvoice && is_numeric($lastInvoice->number)) {
            $this->number = str_pad((int)$lastInvoice->number + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $this->number = '000001';
        }
    }

    public function updatedPaymentTerms()
    {
        $this->due_date = \Carbon\Carbon::parse($this->invoice_date)
            ->addDays($this->payment_terms)
            ->format('Y-m-d');
    }

    public function updatedInvoiceDate()
    {
        $this->updatedPaymentTerms();
    }

    #[Computed]
    public function clients()
    {
        return Client::where('company_id', Auth::user()->company_id)
            ->where('deleted_at', null)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::where('company_id', Auth::user()->company_id)
            ->where('type', 'invoice')
            ->where('archived_at', null)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        return Product::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedClient()
    {
        return $this->client_id ? Client::find($this->client_id) : null;
    }

    #[Computed]
    public function subtotal()
    {
        return collect($this->items)->sum(function ($item) {
            return ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
        });
    }

    #[Computed]
    public function discountAmount()
    {
        if ($this->discount_type === 'percentage') {
            return ($this->subtotal * $this->discount_amount) / 100;
        }
        return $this->discount_amount;
    }

    #[Computed]
    public function taxAmount()
    {
        $taxableAmount = $this->subtotal - $this->discountAmount;
        return ($taxableAmount * $this->tax_rate) / 100;
    }

    #[Computed]
    public function total()
    {
        return $this->subtotal - $this->discountAmount + $this->taxAmount;
    }

    // Item Management
    public function showAddItemForm()
    {
        $this->resetItemForm();
        $this->showItemForm = true;
    }

    public function editItem($index)
    {
        if (isset($this->items[$index])) {
            $this->editingItemIndex = $index;
            $this->itemForm = $this->items[$index];
            $this->showItemForm = true;
        }
    }

    public function saveItem()
    {
        $validatedItem = $this->validate([
            'itemForm.name' => 'required|string|max:255',
            'itemForm.description' => 'nullable|string',
            'itemForm.quantity' => 'required|numeric|min:0.01',
            'itemForm.unit_price' => 'required|numeric|min:0',
            'itemForm.tax_rate' => 'nullable|numeric|min:0|max:100',
        ])['itemForm'];

        if ($this->editingItemIndex !== null) {
            $this->items[$this->editingItemIndex] = $validatedItem;
            Flux::toast('Item updated');
        } else {
            $this->items[] = $validatedItem;
            Flux::toast('Item added');
        }

        $this->cancelItemForm();
    }

    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            array_splice($this->items, $index, 1);
            $this->items = array_values($this->items);
            Flux::toast('Item removed');
        }
    }

    public function cancelItemForm()
    {
        $this->showItemForm = false;
        $this->editingItemIndex = null;
        $this->resetItemForm();
    }

    private function resetItemForm()
    {
        $this->itemForm = [
            'name' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'tax_rate' => 0,
        ];
    }

    public function addProductAsItem($productId)
    {
        if (!$productId) {
            return;
        }
        
        $product = Product::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->find($productId);
            
        if ($product) {
            $this->items[] = [
                'name' => $product->name,
                'description' => $product->description,
                'quantity' => 1,
                'unit_price' => $product->price ?? $product->base_price ?? 0,
                'tax_rate' => $product->tax_rate ?? 0,
            ];
            Flux::toast('Product added');
        }
    }

    public function saveAsDraft()
    {
        $this->save('Draft');
    }

    public function createInvoice()
    {
        $this->save('Sent');
    }

    protected function save($status = 'Draft')
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast('Please complete all required fields', variant: 'danger');
            return;
        }

        DB::beginTransaction();
        try {
            $invoiceService = app(InvoiceService::class);

            $invoiceData = [
                'prefix' => $this->prefix,
                'number' => $this->number,
                'category_id' => $this->category_id,
                'status' => $status,
                'date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'currency_code' => $this->currency_code,
                'scope' => $this->description,
                'note' => $this->notes,
                'terms_conditions' => $this->terms_conditions,
                'discount_type' => $this->discount_type,
                'discount_amount' => $this->discount_amount,
                'tax_rate' => $this->tax_rate,
                'subtotal' => $this->subtotal,
                'discount' => $this->discountAmount,
                'tax' => $this->taxAmount,
                'amount' => $this->total,
                'items' => $this->items,
            ];

            $client = Client::findOrFail($this->client_id);
            $invoice = $invoiceService->createInvoice($client, $invoiceData);

            DB::commit();

            $message = $status === 'Draft' 
                ? "Invoice saved as draft" 
                : "Invoice #{$invoice->prefix}{$invoice->number} created successfully";
            
            Flux::toast($message, variant: 'success');

            return redirect()->route('financial.invoices.show', $invoice->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast('Failed to create invoice: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.financial.invoice-create');
    }
}