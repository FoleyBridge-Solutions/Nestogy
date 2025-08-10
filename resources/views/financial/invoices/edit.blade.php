@extends('layouts.app')

@section('title', 'Edit Invoice #' . $invoice->getFullNumber())

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Invoice #{{ $invoice->getFullNumber() }}</h1>
                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-600">
                    <span>{{ $invoice->client->name }}</span>
                    <span>•</span>
                    <span>{{ $invoice->date->format('M d, Y') }}</span>
                    <span>•</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($invoice->status === 'Draft') bg-gray-100 text-gray-800
                        @elseif($invoice->status === 'Sent') bg-blue-100 text-blue-800
                        @elseif($invoice->status === 'Paid') bg-green-100 text-green-800
                        @elseif($invoice->status === 'Overdue') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ $invoice->status }}
                    </span>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="button" id="edit-details-btn" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Details
                </button>
                <a href="{{ route('financial.invoices.show', $invoice) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Invoice
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">There were some errors</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Invoice Items Management -->
        <form method="POST" action="{{ route('financial.invoices.update', $invoice) }}" id="invoice-form">
            @csrf
            @method('PUT')

            <!-- Invoice Items Section -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Invoice Items</h3>
                        <button type="button" id="add-item" 
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Item
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div id="invoice-items" class="space-y-4">
                        @foreach($invoice->items as $index => $item)
                        <div class="invoice-item border border-gray-200 rounded-lg p-4 bg-gray-50" data-index="{{ $index }}">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea name="items[{{ $index }}][description]" rows="2" required 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">{{ old('items.'.$index.'.description', $item->description) }}</textarea>
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                    <input type="number" name="items[{{ $index }}][quantity]" value="{{ old('items.'.$index.'.quantity', $item->quantity) }}" 
                                           min="1" step="1" required 
                                           class="item-quantity w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Rate ($)</label>
                                    <input type="number" name="items[{{ $index }}][rate]" value="{{ old('items.'.$index.'.rate', number_format($item->rate, 2, '.', '')) }}" 
                                           min="0" step="0.01" required 
                                           class="item-rate w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Total</label>
                                    <div class="item-total w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-right font-semibold">
                                        ${{ number_format($item->amount, 2) }}
                                    </div>
                                </div>
                                <div class="md:col-span-1 flex items-end justify-center">
                                    <button type="button" class="remove-item p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        
                        @if($invoice->items->count() === 0)
                        <div id="no-items-message" class="text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2">No items added yet.</p>
                            <p class="text-sm">Click "Add Item" to get started.</p>
                        </div>
                        @endif
                    </div>

                    <!-- Invoice Totals -->
                    <div class="mt-8 flex justify-end">
                        <div class="w-full max-w-sm space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span id="subtotal" class="font-medium">${{ number_format($invoice->getSubtotal(), 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Discount:</span>
                                <span id="discount" class="font-medium">-${{ number_format($invoice->discount_amount, 2) }}</span>
                            </div>
                            <div class="border-t border-gray-200 pt-2">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-900">Total:</span>
                                    <span id="total" class="text-lg font-semibold text-gray-900">${{ number_format($invoice->amount, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('financial.invoices.show', $invoice) }}" 
                   class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Save Changes
                </button>
            </div>

            <!-- Hidden form fields for invoice details -->
            <input type="hidden" name="client_id" value="{{ $invoice->client_id }}">
            <input type="hidden" name="category_id" value="{{ $invoice->category_id }}">
            <input type="hidden" name="currency_code" value="{{ $invoice->currency_code }}">
            <input type="hidden" name="date" value="{{ $invoice->date->format('Y-m-d') }}">
            <input type="hidden" name="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}">
            <input type="hidden" name="scope" value="{{ $invoice->scope }}">
            <input type="hidden" name="discount_amount" id="discount_amount" value="{{ $invoice->discount_amount }}">
            <input type="hidden" name="status" value="{{ $invoice->status }}">
            <input type="hidden" name="note" value="{{ $invoice->note }}">
        </form>
    </div>
</div>

<!-- Invoice Details Modal -->
<div id="details-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Invoice Details</h3>
            
            <form id="details-form" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Client -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Client *</label>
                        <select id="modal-client_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ $invoice->client_id == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}{{ $client->company_name ? ' (' . $client->company_name . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select id="modal-category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $invoice->category_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Currency -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Currency *</label>
                        <select id="modal-currency_code" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="USD" {{ $invoice->currency_code == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                            <option value="EUR" {{ $invoice->currency_code == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                            <option value="GBP" {{ $invoice->currency_code == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                            <option value="CAD" {{ $invoice->currency_code == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                            <option value="AUD" {{ $invoice->currency_code == 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="modal-status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="Draft" {{ $invoice->status == 'Draft' ? 'selected' : '' }}>Draft</option>
                            <option value="Sent" {{ $invoice->status == 'Sent' ? 'selected' : '' }}>Sent</option>
                            <option value="Paid" {{ $invoice->status == 'Paid' ? 'selected' : '' }}>Paid</option>
                            <option value="Cancelled" {{ $invoice->status == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <!-- Invoice Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Date *</label>
                        <input type="date" id="modal-date" value="{{ $invoice->date->format('Y-m-d') }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Due Date *</label>
                        <input type="date" id="modal-due_date" value="{{ $invoice->due_date->format('Y-m-d') }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <!-- Scope -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Scope</label>
                    <input type="text" id="modal-scope" value="{{ $invoice->scope }}" 
                           placeholder="Brief description of services or products"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Discount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Discount Amount ($)</label>
                    <input type="number" id="modal-discount_amount" value="{{ number_format($invoice->discount_amount, 2, '.', '') }}" 
                           min="0" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Notes</label>
                    <textarea id="modal-note" rows="3" 
                              placeholder="Additional notes or terms for this invoice..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">{{ $invoice->note }}</textarea>
                </div>

                <!-- Modal Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" id="cancel-details" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" id="save-details" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = {{ $invoice->items->count() }};
    
    // Modal functionality
    const modal = document.getElementById('details-modal');
    const editBtn = document.getElementById('edit-details-btn');
    const cancelBtn = document.getElementById('cancel-details');
    const saveBtn = document.getElementById('save-details');
    
    editBtn.addEventListener('click', function() {
        modal.classList.remove('hidden');
    });
    
    cancelBtn.addEventListener('click', function() {
        modal.classList.add('hidden');
    });
    
    // Close modal on outside click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
    
    saveBtn.addEventListener('click', function() {
        // Update hidden form fields
        document.querySelector('input[name="client_id"]').value = document.getElementById('modal-client_id').value;
        document.querySelector('input[name="category_id"]').value = document.getElementById('modal-category_id').value;
        document.querySelector('input[name="currency_code"]').value = document.getElementById('modal-currency_code').value;
        document.querySelector('input[name="date"]').value = document.getElementById('modal-date').value;
        document.querySelector('input[name="due_date"]').value = document.getElementById('modal-due_date').value;
        document.querySelector('input[name="scope"]').value = document.getElementById('modal-scope').value;
        document.querySelector('input[name="discount_amount"]').value = document.getElementById('modal-discount_amount').value;
        document.querySelector('input[name="status"]').value = document.getElementById('modal-status').value;
        document.querySelector('input[name="note"]').value = document.getElementById('modal-note').value;
        
        modal.classList.add('hidden');
        updateTotals();
        
        // Show a success message
        const alert = document.createElement('div');
        alert.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50';
        alert.innerHTML = 'Invoice details updated. Don\'t forget to save your changes.';
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    });
    
    // Add new item
    document.getElementById('add-item').addEventListener('click', function() {
        const itemsContainer = document.getElementById('invoice-items');
        const noItemsMsg = document.getElementById('no-items-message');
        
        if (noItemsMsg) {
            noItemsMsg.remove();
        }
        
        const itemHtml = `
            <div class="invoice-item border border-gray-200 rounded-lg p-4 bg-gray-50" data-index="${itemIndex}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="items[${itemIndex}][description]" rows="2" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" step="1" required 
                               class="item-quantity w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rate ($)</label>
                        <input type="number" name="items[${itemIndex}][rate]" value="0.00" min="0" step="0.01" required 
                               class="item-rate w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Total</label>
                        <div class="item-total w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-right font-semibold">
                            $0.00
                        </div>
                    </div>
                    <div class="md:col-span-1 flex items-end justify-center">
                        <button type="button" class="remove-item p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        itemsContainer.insertAdjacentHTML('beforeend', itemHtml);
        itemIndex++;
        updateTotals();
    });
    
    // Remove item
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('.invoice-item').remove();
            
            // Show no items message if all items removed
            if (document.querySelectorAll('.invoice-item').length === 0) {
                const itemsContainer = document.getElementById('invoice-items');
                itemsContainer.innerHTML = `
                    <div id="no-items-message" class="text-center py-12 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2">No items added yet.</p>
                        <p class="text-sm">Click "Add Item" to get started.</p>
                    </div>
                `;
            }
            
            updateTotals();
        }
    });
    
    // Update totals when quantities or rates change
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-quantity') || e.target.classList.contains('item-rate')) {
            const item = e.target.closest('.invoice-item');
            if (item) {
                updateItemTotal(item);
            }
            updateTotals();
        }
        
        if (e.target.id === 'modal-discount_amount') {
            updateTotals();
        }
    });
    
    function updateItemTotal(item) {
        const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
        const rate = parseFloat(item.querySelector('.item-rate').value) || 0;
        const total = quantity * rate;
        
        item.querySelector('.item-total').textContent = '$' + total.toFixed(2);
    }
    
    function updateTotals() {
        let subtotal = 0;
        
        document.querySelectorAll('.invoice-item').forEach(function(item) {
            const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
            const rate = parseFloat(item.querySelector('.item-rate').value) || 0;
            subtotal += quantity * rate;
        });
        
        const discountInput = document.getElementById('modal-discount_amount');
        const discount = discountInput ? parseFloat(discountInput.value) || 0 : parseFloat(document.getElementById('discount_amount').value) || 0;
        const total = Math.max(0,
const itemHtml = `
            <div class="invoice-item border border-gray-200 rounded-lg p-4 bg-gray-50" data-index="${itemIndex}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="items[${itemIndex}][description]" rows="2" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" step="1" required 
                               class="item-quantity w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rate ($)</label>
                        <input type="number" name="items[${itemIndex}][rate]" value="0.00" min="0" step="0.01" required 
                               class="item-rate w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Total</label>
                        <div class="item-total w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-right font-semibold">
                            $0.00
                        </div>
                    </div>
                    <div class="md:col-span-1 flex items-end justify-center">
                        <button type="button" class="remove-item p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        itemsContainer.insertAdjacentHTML('beforeend', itemHtml);
        itemIndex++;
        updateTotals();
    });
    
    // Remove item
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('.invoice-item').remove();
            
            // Show no items message if all items removed
            if (document.querySelectorAll('.invoice-item').length === 0) {
                const itemsContainer = document.getElementById('invoice-items');
                itemsContainer.innerHTML = `
                    <div id="no-items-message" class="text-center py-12 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2">No items added yet.</p>
                        <p class="text-sm">Click "Add Item" to get started.</p>
                    </div>
                `;
            }
            
            updateTotals();
        }
    });
    
    // Update totals when quantities or rates change
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-quantity') || e.target.classList.contains('item-rate')) {
            const item = e.target.closest('.invoice-item');
            if (item) {
                updateItemTotal(item);
            }
            updateTotals();
        }
        
        if (e.target.id === 'modal-discount_amount') {
            updateTotals();
        }
    });
    
    function updateItemTotal(item) {
        const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
        const rate = parseFloat(item.querySelector('.item-rate').value) || 0;
        const total = quantity * rate;
        
        item.querySelector('.item-total').textContent = '$' + total.toFixed(2);
    }
    
    function updateTotals() {
        let subtotal = 0;
        
        document.querySelectorAll('.invoice-item').forEach(function(item) {
            const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
            const rate = parseFloat(item.querySelector('.item-rate').value) || 0;
            subtotal += quantity * rate;
        });
        
        const discountInput = document.getElementById('modal-discount_amount');
        const discount = discountInput ? parseFloat(discountInput.value) || 0 : parseFloat(document.getElementById('discount_amount').value) || 0;
        const total = Math.max(0, subtotal - discount);
        
        document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('discount').textContent = '-$' + discount.toFixed(2);
        document.getElementById('total').textContent = '$' + total.toFixed(2);
    }
    
    // Initialize totals on page load
    document.querySelectorAll('.invoice-item').forEach(updateItemTotal);
    updateTotals();
});
</script>
@endpush
@endsection