<?php

namespace App\Livewire\Financial;

use App\Models\Category;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Traits\QuotePricingCalculations;
use Livewire\Component;

class QuoteWizard extends Component
{
    use QuotePricingCalculations;

    public $currentStep = 1;

    public $totalSteps = 3;

    // Step names for display
    public $stepNames = [
        1 => 'Quote Details',
        2 => 'Items & Pricing',
        3 => 'Review & Submit',
    ];

    // Quote document properties
    public $client_id = '';

    public $category_id = '';

    public $date;

    public $expire_date = '';

    public $currency_code = 'USD';

    public $scope = '';

    public $note = '';

    public $terms_conditions = '';

    public $discount_type = 'fixed';

    public $discount_amount = 0;

    public $status = 'draft';

    // Items and pricing
    public $selectedItems = [];

    public $pricing = [
        'subtotal' => 0,
        'discount' => 0,
        'tax' => 0,
        'total' => 0,
        'savings' => 0,
        'recurring' => ['monthly' => 0, 'annual' => 0],
    ];

    // Billing configuration
    public $billingConfig = [
        'model' => 'one_time',
        'cycle' => 'monthly',
        'paymentTerms' => 30,
        'startDate' => '',
        'endDate' => '',
        'autoRenew' => false,
    ];

    // UI state
    public $loading = false;

    public $saving = false;

    public $errors = [];

    public $validationErrors = [];

    public $showAdvanced = false;

    public $quickMode = false;

    // Templates
    public $availableTemplates = [];

    public $suggestedTemplates = [];

    public $selectedTemplate = null;

    // Auto-save
    public $autoSaveEnabled = true;

    public $lastSaved = null;

    // Available data
    public $clients = [];

    public $categories = [];

    public $currencies = [];

    protected $listeners = [
        'itemsUpdated' => 'handleItemsUpdate',
        'pricingUpdated' => 'handlePricingUpdate',
        'templateSelected' => 'handleTemplateSelection',
        'clientChanged' => 'handleClientChange',
    ];

    public function mount($quote = null)
    {
        $this->initializeData();

        if ($quote) {
            $this->loadExistingQuote($quote);
        } else {
            $this->setDefaults();
        }
    }

    protected function initializeData()
    {
        $companyId = auth()->user()->company_id;

        $this->clients = Client::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'currency_code']);

        $this->categories = Category::where('company_id', $companyId)
            ->where('type', 'quote')
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->currencies = [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'CAD' => 'Canadian Dollar',
            'AUD' => 'Australian Dollar',
        ];

        $this->availableTemplates = QuoteTemplate::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    protected function setDefaults()
    {
        $this->date = now()->format('Y-m-d');
        $this->billingConfig['startDate'] = now()->format('Y-m-d');

        // Set default expiration (30 days)
        $this->expire_date = now()->addDays(30)->format('Y-m-d');

        $this->scheduleAutoSave();
    }

    protected function loadExistingQuote($quote)
    {
        $this->client_id = $quote->client_id;
        $this->category_id = $quote->category_id;
        $this->date = $quote->date->format('Y-m-d');
        $this->expire_date = $quote->expire_date ? $quote->expire_date->format('Y-m-d') : '';
        $this->currency_code = $quote->currency_code;
        $this->scope = $quote->scope ?? '';
        $this->note = $quote->note ?? '';
        $this->terms_conditions = $quote->terms_conditions ?? '';
        $this->discount_amount = $quote->discount_amount ?? 0;
        $this->status = $quote->status;

        // Load items
        $this->selectedItems = $quote->items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description ?? '',
                'quantity' => $item->quantity,
                'unit_price' => $item->price,
                'discount' => $item->discount ?? 0,
                'tax_rate' => $item->tax_rate ?? 0,
                'subtotal' => $item->subtotal,
                'type' => $item->type ?? 'product',
                'billing_cycle' => $item->billing_cycle ?? 'one_time',
                'order' => $item->order ?? 1,
            ];
        })->toArray();

        $this->updatePricing();
    }

    // === NAVIGATION ===
    public function nextStep()
    {
        if ($this->validateCurrentStep() && $this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            $this->dispatch('stepChanged', ['step' => $this->currentStep]);
        }
    }

    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->dispatch('stepChanged', ['step' => $this->currentStep]);
        }
    }

    public function goToStep($step)
    {
        if ($this->canGoToStep($step)) {
            $this->currentStep = $step;
            $this->dispatch('stepChanged', ['step' => $this->currentStep]);
        }
    }

    public function canGoToStep($step)
    {
        // Can always go backwards
        if ($step <= $this->currentStep) {
            return true;
        }

        // Step-specific validation for forward navigation
        if ($step > 1 && ! $this->validateStep(1)) {
            return false;
        }

        if ($step > 2 && ! $this->validateStep(2)) {
            return false;
        }

        return $step <= $this->totalSteps;
    }

    // === VALIDATION ===
    public function validateCurrentStep()
    {
        return $this->validateStep($this->currentStep);
    }

    public function validateStep($step)
    {
        $this->validationErrors = [];

        if ($step >= 1) {
            if (empty($this->client_id)) {
                $this->validationErrors['client_id'] = 'Please select a client';
            }
            if (empty($this->category_id)) {
                $this->validationErrors['category_id'] = 'Please select a category';
            }
            if (empty($this->date)) {
                $this->validationErrors['date'] = 'Please select a quote date';
            }
        }

        if ($step >= 2) {
            if (empty($this->selectedItems)) {
                $this->validationErrors['items'] = 'Please add at least one item';
            }

            foreach ($this->selectedItems as $index => $item) {
                if (empty($item['name'])) {
                    $this->validationErrors["item_{$index}_name"] = 'Item name is required';
                }
                if ($item['quantity'] <= 0) {
                    $this->validationErrors["item_{$index}_quantity"] = 'Quantity must be greater than 0';
                }
                if ($item['unit_price'] < 0) {
                    $this->validationErrors["item_{$index}_unit_price"] = 'Unit price cannot be negative';
                }
            }
        }

        return empty($this->validationErrors);
    }

    public function validateField($field)
    {
        switch ($field) {
            case 'client_id':
                if (empty($this->client_id)) {
                    $this->validationErrors['client_id'] = 'Please select a client';
                } else {
                    unset($this->validationErrors['client_id']);
                    $this->handleClientChange();
                }
                break;
            case 'expire_date':
                if ($this->expire_date && $this->expire_date <= $this->date) {
                    $this->validationErrors['expire_date'] = 'Expiration date must be after quote date';
                } else {
                    unset($this->validationErrors['expire_date']);
                }
                break;
        }
    }

    // === CLIENT MANAGEMENT ===
    public function handleClientChange()
    {
        if ($this->client_id) {
            $client = $this->clients->find($this->client_id);
            if ($client && $client->currency_code) {
                $this->currency_code = $client->currency_code;
            }

            // Load client suggestions
            $this->loadClientSuggestions();
        }
    }

    protected function loadClientSuggestions()
    {
        if ($this->client_id) {
            $this->suggestedTemplates = QuoteTemplate::where('company_id', auth()->user()->company_id)
                ->whereHas('quotes', function ($query) {
                    $query->where('client_id', $this->client_id);
                })
                ->limit(5)
                ->get();
        }
    }

    // === ITEM MANAGEMENT ===
    public function addItem($item = null)
    {
        $newItem = [
            'id' => 'temp_'.time().'_'.rand(1000, 9999),
            'name' => $item['name'] ?? '',
            'description' => $item['description'] ?? '',
            'quantity' => $item['quantity'] ?? 1,
            'unit_price' => $item['unit_price'] ?? $item['price'] ?? 0,
            'discount' => $item['discount'] ?? 0,
            'tax_rate' => $item['tax_rate'] ?? 0,
            'subtotal' => 0,
            'type' => $item['type'] ?? 'product',
            'billing_cycle' => $item['billing_cycle'] ?? 'one_time',
            'order' => count($this->selectedItems) + 1,
        ];

        $newItem['subtotal'] = $this->calculateItemSubtotal($newItem);
        $this->selectedItems[] = $newItem;
        $this->updatePricing();
        $this->scheduleAutoSave();
    }

    public function removeItem($itemId)
    {
        $this->selectedItems = array_values(array_filter($this->selectedItems, function ($item) use ($itemId) {
            return $item['id'] !== $itemId;
        }));

        $this->reorderItems();
        $this->updatePricing();
        $this->scheduleAutoSave();
    }

    public function updateItem($itemId, $field, $value)
    {
        foreach ($this->selectedItems as $index => $item) {
            if ($item['id'] === $itemId) {
                $this->selectedItems[$index][$field] = $value;

                if (in_array($field, ['quantity', 'unit_price', 'discount'])) {
                    $this->selectedItems[$index]['subtotal'] = $this->calculateItemSubtotal($this->selectedItems[$index]);
                    $this->updatePricing();
                }

                $this->scheduleAutoSave();
                break;
            }
        }
    }

    protected function reorderItems()
    {
        foreach ($this->selectedItems as $index => $item) {
            $this->selectedItems[$index]['order'] = $index + 1;
        }
    }

    // === EVENT HANDLERS ===
    public function handleItemsUpdate($items)
    {
        $this->selectedItems = $items;
        $this->updatePricing();
        $this->scheduleAutoSave();
    }

    public function handlePricingUpdate($pricing)
    {
        $this->pricing = array_merge($this->pricing, $pricing);
    }

    public function handleTemplateSelection($template)
    {
        $this->selectedTemplate = $template;
        $this->loadTemplate($template);
    }

    protected function loadTemplate($template)
    {
        if (! $template) {
            return;
        }

        $this->scope = $template['scope'] ?? '';
        $this->note = $template['note'] ?? '';
        $this->terms_conditions = $template['terms_conditions'] ?? '';
        $this->discount_amount = $template['discount_amount'] ?? 0;

        if (isset($template['items']) && is_array($template['items'])) {
            $this->selectedItems = array_map(function ($item, $index) {
                $item['id'] = 'template_'.time().'_'.$index;
                $item['subtotal'] = $this->calculateItemSubtotal($item);

                return $item;
            }, $template['items'], array_keys($template['items']));
        }

        $this->updatePricing();
        $this->scheduleAutoSave();
    }

    // === AUTO-SAVE ===
    protected function scheduleAutoSave()
    {
        if ($this->autoSaveEnabled && ! $this->saving) {
            $this->dispatch('schedule-auto-save');
        }
    }

    public function performAutoSave()
    {
        if ($this->saving || ! $this->validateStep(1)) {
            return;
        }

        $this->saving = true;

        try {
            $data = [
                'client_id' => $this->client_id,
                'category_id' => $this->category_id,
                'date' => $this->date,
                'expire_date' => $this->expire_date,
                'currency_code' => $this->currency_code,
                'scope' => $this->scope,
                'note' => $this->note,
                'terms_conditions' => $this->terms_conditions,
                'discount_amount' => $this->discount_amount,
                'status' => 'draft',
                'amount' => $this->pricing['total'],
                'company_id' => auth()->user()->company_id,
                'items' => $this->selectedItems,
                'draft' => true,
            ];

            // Store in session for now (could be database)
            session(['quote_draft_'.auth()->id() => $data]);

            $this->lastSaved = now()->format('H:i:s');
            $this->dispatch('auto-save-success');

        } catch (\Exception $e) {
            $this->dispatch('auto-save-error', ['message' => 'Auto-save failed']);
        } finally {
            $this->saving = false;
        }
    }

    // === SAVE & SUBMIT ===
    public function saveQuote($submitType = 'draft')
    {
        if (! $this->validateCurrentStep()) {
            return;
        }

        $this->saving = true;
        $this->errors = [];

        try {
            $quoteData = [
                'company_id' => auth()->user()->company_id,
                'client_id' => $this->client_id,
                'category_id' => $this->category_id,
                'date' => $this->date,
                'expire_date' => $this->expire_date ?: null,
                'currency_code' => $this->currency_code,
                'scope' => $this->scope,
                'note' => $this->note,
                'terms_conditions' => $this->terms_conditions,
                'discount_amount' => $this->discount_amount,
                'amount' => $this->pricing['total'],
                'status' => $submitType === 'send' ? Quote::STATUS_SENT : Quote::STATUS_DRAFT,
                'created_by' => auth()->id(),
            ];

            $quote = Quote::create($quoteData);

            // Create quote items
            foreach ($this->selectedItems as $item) {
                $quote->items()->create([
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'subtotal' => $item['subtotal'],
                    'type' => $item['type'] ?? 'product',
                    'billing_cycle' => $item['billing_cycle'] ?? 'one_time',
                    'order' => $item['order'] ?? 1,
                ]);
            }

            $quote->calculateTotals();

            $this->dispatch('quote-saved', [
                'quote_id' => $quote->id,
                'message' => $submitType === 'send' ? 'Quote sent successfully!' : 'Quote saved successfully!',
            ]);

            return redirect()->route('financial.quotes.show', $quote);

        } catch (\Exception $e) {
            $this->errors['general'] = 'Failed to save quote: '.$e->getMessage();
        } finally {
            $this->saving = false;
        }
    }

    // === COMPUTED PROPERTIES ===
    public function getStepProgressProperty()
    {
        return round(($this->currentStep / $this->totalSteps) * 100);
    }

    public function getIsValidStepProperty()
    {
        return $this->validateCurrentStep();
    }

    public function getCanProceedProperty()
    {
        return $this->validateCurrentStep() && ! $this->loading;
    }

    public function getFormattedTotalProperty()
    {
        return $this->formatCurrency($this->pricing['total']);
    }

    protected function formatCurrency($amount)
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];

        $symbol = $symbols[$this->currency_code] ?? $this->currency_code;

        return $symbol.number_format($amount, 2);
    }

    public function render()
    {
        return view('livewire.financial.quote-wizard')
            ->extends('layouts.app')
            ->section('content');
    }
}
