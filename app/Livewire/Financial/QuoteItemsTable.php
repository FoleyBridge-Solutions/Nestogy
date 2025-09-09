<?php

namespace App\Livewire\Financial;

use Livewire\Component;
use App\Models\Product;
use App\Models\Service;
use App\Traits\QuotePricingCalculations;

class QuoteItemsTable extends Component
{
    use QuotePricingCalculations;

    public $items = [];
    public $currency_code = 'USD';
    public $editingIndex = null;
    public $showProductSelector = false;
    public $searchProducts = '';
    public $availableProducts = [];

    // New item form
    public $newItem = [
        'name' => '',
        'description' => '',
        'quantity' => 1,
        'unit_price' => 0,
        'discount' => 0,
        'tax_rate' => 0,
        'type' => 'product',
        'billing_cycle' => 'one_time'
    ];

    protected $listeners = [
        'itemsUpdated' => 'updateItems'
    ];

    public function mount($items = [], $currency_code = 'USD')
    {
        $this->items = $items;
        $this->currency_code = $currency_code;
        $this->loadAvailableProducts();
    }

    protected function loadAvailableProducts()
    {
        $this->availableProducts = Product::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'description', 'price', 'category'])
            ->toArray();
    }

    public function updateItems($newItems)
    {
        $this->items = $newItems;
    }

    public function addItem()
    {
        // Validate the new item
        $this->validateNewItem();

        $item = [
            'id' => 'temp_' . time() . '_' . rand(1000, 9999),
            'name' => $this->newItem['name'],
            'description' => $this->newItem['description'],
            'quantity' => (float)$this->newItem['quantity'],
            'unit_price' => (float)$this->newItem['unit_price'],
            'discount' => (float)$this->newItem['discount'],
            'tax_rate' => (float)$this->newItem['tax_rate'],
            'type' => $this->newItem['type'],
            'billing_cycle' => $this->newItem['billing_cycle'],
            'order' => count($this->items) + 1
        ];

        $item['subtotal'] = $this->calculateItemSubtotal($item);
        $this->items[] = $item;

        // Reset the new item form
        $this->resetNewItem();

        // Notify parent component
        $this->dispatch('itemsUpdated', $this->items);
    }

    public function addProductToQuote($productId)
    {
        $product = collect($this->availableProducts)->firstWhere('id', $productId);
        
        if ($product) {
            $item = [
                'id' => 'temp_' . time() . '_' . rand(1000, 9999),
                'product_id' => $product['id'],
                'name' => $product['name'],
                'description' => $product['description'] ?? '',
                'quantity' => 1,
                'unit_price' => (float)($product['price'] ?? 0),
                'discount' => 0,
                'tax_rate' => 0,
                'type' => 'product',
                'billing_cycle' => 'one_time',
                'order' => count($this->items) + 1
            ];

            $item['subtotal'] = $this->calculateItemSubtotal($item);
            $this->items[] = $item;

            $this->showProductSelector = false;
            $this->dispatch('itemsUpdated', $this->items);
        }
    }

    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            array_splice($this->items, $index, 1);
            $this->reorderItems();
            $this->dispatch('itemsUpdated', $this->items);
        }
    }

    public function startEditing($index)
    {
        $this->editingIndex = $index;
    }

    public function stopEditing()
    {
        $this->editingIndex = null;
        $this->dispatch('itemsUpdated', $this->items);
    }

    public function updateItem($index, $field, $value)
    {
        if (isset($this->items[$index])) {
            $this->items[$index][$field] = $value;
            
            // Recalculate subtotal for pricing fields
            if (in_array($field, ['quantity', 'unit_price', 'discount'])) {
                $this->items[$index]['subtotal'] = $this->calculateItemSubtotal($this->items[$index]);
            }
            
            $this->dispatch('itemsUpdated', $this->items);
        }
    }

    public function moveItem($fromIndex, $toIndex)
    {
        if (isset($this->items[$fromIndex]) && $toIndex >= 0 && $toIndex < count($this->items)) {
            $item = array_splice($this->items, $fromIndex, 1)[0];
            array_splice($this->items, $toIndex, 0, [$item]);
            $this->reorderItems();
            $this->dispatch('itemsUpdated', $this->items);
        }
    }

    public function duplicateItem($index)
    {
        if (isset($this->items[$index])) {
            $item = $this->items[$index];
            $item['id'] = 'temp_' . time() . '_' . rand(1000, 9999);
            $item['name'] = $item['name'] . ' (Copy)';
            $item['order'] = count($this->items) + 1;
            
            $this->items[] = $item;
            $this->dispatch('itemsUpdated', $this->items);
        }
    }

    protected function reorderItems()
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['order'] = $index + 1;
        }
    }

    protected function validateNewItem()
    {
        $this->validate([
            'newItem.name' => 'required|string|max:255',
            'newItem.quantity' => 'required|numeric|min:0.01',
            'newItem.unit_price' => 'required|numeric|min:0',
            'newItem.discount' => 'nullable|numeric|min:0',
            'newItem.tax_rate' => 'nullable|numeric|min:0|max:100',
        ], [
            'newItem.name.required' => 'Item name is required',
            'newItem.quantity.required' => 'Quantity is required',
            'newItem.quantity.min' => 'Quantity must be greater than 0',
            'newItem.unit_price.required' => 'Unit price is required',
            'newItem.unit_price.min' => 'Unit price cannot be negative',
            'newItem.discount.min' => 'Discount cannot be negative',
            'newItem.tax_rate.max' => 'Tax rate cannot exceed 100%',
        ]);
    }

    protected function resetNewItem()
    {
        $this->newItem = [
            'name' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'tax_rate' => 0,
            'type' => 'product',
            'billing_cycle' => 'one_time'
        ];
    }

    public function toggleProductSelector()
    {
        $this->showProductSelector = !$this->showProductSelector;
        if ($this->showProductSelector) {
            $this->searchProducts = '';
        }
    }

    public function updatedSearchProducts()
    {
        if (strlen($this->searchProducts) >= 2) {
            $this->availableProducts = Product::where('company_id', auth()->user()->company_id)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->searchProducts . '%')
                          ->orWhere('description', 'like', '%' . $this->searchProducts . '%');
                })
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'description', 'price', 'category'])
                ->toArray();
        } else {
            $this->loadAvailableProducts();
        }
    }

    // === COMPUTED PROPERTIES ===
    public function getItemsTotalProperty()
    {
        return array_sum(array_column($this->items, 'subtotal'));
    }

    public function getFormattedItemsTotalProperty()
    {
        return $this->formatCurrency($this->itemsTotal);
    }

    public function getItemsCountProperty()
    {
        return count($this->items);
    }

    public function getHasItemsProperty()
    {
        return count($this->items) > 0;
    }

    protected function formatCurrency($amount)
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$'
        ];

        $symbol = $symbols[$this->currency_code] ?? $this->currency_code;
        return $symbol . number_format($amount, 2);
    }

    public function render()
    {
        return view('livewire.financial.quote-items-table');
    }
}