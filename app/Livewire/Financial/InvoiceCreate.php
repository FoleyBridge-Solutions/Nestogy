<?php

namespace App\Livewire\Financial;

use App\Domains\Core\Services\NavigationService;
use App\Domains\Financial\Services\InvoiceService;
use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class InvoiceCreate extends Component
{
    public $client_id = '';

    public $category_id = '';

    public $invoice_date;

    public $due_date;

    public $currency_code = 'USD';

    public $payment_terms = 30;

    public $prefix = 'INV';

    public $number = '';

    public $items = [];

    public $discount_type = 'fixed';

    public $discount_amount = 0;

    public $tax_rate = 0;

    public $scope = '';

    public $note = '';

    public $terms_conditions = '';

    public $showItemModal = false;

    public $editingItemIndex = null;

    public $itemForm = [
        'name' => '',
        'description' => '',
        'quantity' => 1,
        'price' => 0,
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
            'items.*.price' => 'required|numeric|min:0',
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
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays($this->payment_terms)->format('Y-m-d');

        $this->generateInvoiceNumber();

        $defaultCategory = Category::where('company_id', Auth::user()->company_id)
            ->whereJsonContains('type', 'invoice')
            ->where('archived_at', null)
            ->first();
        if ($defaultCategory) {
            $this->category_id = $defaultCategory->id;
        }

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
            $this->number = str_pad((int) $lastInvoice->number + 1, 6, '0', STR_PAD_LEFT);
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
            ->whereJsonContains('type', 'invoice')
            ->where('archived_at', null)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        return Product::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->whereJsonContains('type', 'invoice');
            })
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
            return ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
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

    public function openItemModal()
    {
        $this->resetItemForm();
        $this->editingItemIndex = null;
        $this->showItemModal = true;
    }

    public function editItem($index)
    {
        if (isset($this->items[$index])) {
            $this->editingItemIndex = $index;
            $this->itemForm = $this->items[$index];
            $this->showItemModal = true;
        }
    }

    public function saveItem()
    {
        $validated = $this->validate([
            'itemForm.name' => 'required|string|max:255',
            'itemForm.description' => 'nullable|string',
            'itemForm.quantity' => 'required|numeric|min:0.01',
            'itemForm.price' => 'required|numeric|min:0',
        ]);

        if ($this->editingItemIndex !== null) {
            $this->items[$this->editingItemIndex] = $validated['itemForm'];
            Flux::toast('Item updated successfully', variant: 'success');
        } else {
            $this->items[] = $validated['itemForm'];
            Flux::toast('Item added successfully', variant: 'success');
        }

        $this->closeItemModal();
    }

    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            array_splice($this->items, $index, 1);
            $this->items = array_values($this->items);
            Flux::toast('Item removed', variant: 'success');
        }
    }

    public function closeItemModal()
    {
        $this->showItemModal = false;
        $this->editingItemIndex = null;
        $this->resetItemForm();
        $this->resetValidation();
    }

    private function resetItemForm()
    {
        $this->itemForm = [
            'name' => '',
            'description' => '',
            'quantity' => 1,
            'price' => 0,
        ];
    }

    public function addProductAsItem($productId)
    {
        if (! $productId) {
            return;
        }

        $product = Product::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->with('category')
            ->find($productId);

        if (! $product) {
            Flux::toast('Product not found', variant: 'danger');
            return;
        }

        // Validate product category has invoice type
        if (! $product->category || ! $product->category->hasType(\App\Models\Category::TYPE_INVOICE)) {
            Flux::toast('This product cannot be added to invoices. Its category does not support invoicing.', variant: 'danger');
            return;
        }

        $this->items[] = [
            'name' => $product->name,
            'description' => $product->description ?? '',
            'quantity' => 1,
            'price' => $product->price ?? $product->base_price ?? 0,
            'category_id' => $product->category_id,
            'product_id' => $product->id,
        ];

        Flux::toast('Product added successfully', variant: 'success');
    }

    public function saveAsDraft()
    {
        $this->save('Draft');
    }

    public function createAndSend()
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
            $invoice = Invoice::create([
                'company_id' => Auth::user()->company_id,
                'client_id' => $this->client_id,
                'category_id' => $this->category_id,
                'prefix' => $this->prefix,
                'number' => $this->number,
                'status' => $status,
                'date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'currency_code' => $this->currency_code,
                'scope' => $this->scope,
                'note' => $this->note,
                'discount_amount' => $this->discountAmount,
                'amount' => 0,
                'is_recurring' => false,
            ]);

            // Load the client relationship for tax calculations
            $invoice->load('client');

            foreach ($this->items as $index => $item) {
                $invoice->items()->create([
                    'company_id' => Auth::user()->company_id,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount' => 0,
                    'order' => $index,
                ]);
            }

            $invoice->calculateTotals();

            DB::commit();

            $message = $status === 'Draft'
                ? 'Invoice saved as draft successfully'
                : 'Invoice created and marked as sent successfully';

            Flux::toast($message, variant: 'success');

            return redirect()->route('financial.invoices.show', $invoice->id);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create invoice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Flux::toast('Failed to create invoice: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.financial.invoice-create');
    }
}
