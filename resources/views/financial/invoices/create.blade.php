@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Invoice</h1>
                <p class="text-gray-600 mt-1">Create a new invoice for billing</p>
            </div>
            <a href="{{ route('financial.invoices.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Invoices
            </a>
        </div>

        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('financial.invoices.store') }}" class="space-y-6 p-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Client -->
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                        <select name="client_id" id="client_id" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('client_id') border-red-300 @enderror">
                            <option value="">Select client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ (old('client_id', $selectedClient?->id) == $client->id) ? 'selected' : '' }}>
                                    {{ $client->name }}{{ $client->company_name ? ' (' . $client->company_name . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
                        <select name="category_id" id="category_id" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('category_id') border-red-300 @enderror">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Currency -->
                    <div>
                        <label for="currency_code" class="block text-sm font-medium text-gray-700">Currency *</label>
                        <select name="currency_code" id="currency_code" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('currency_code') border-red-300 @enderror">
                            <option value="USD" {{ old('currency_code', 'USD') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                            <option value="EUR" {{ old('currency_code') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                            <option value="GBP" {{ old('currency_code') == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                            <option value="CAD" {{ old('currency_code') == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                            <option value="AUD" {{ old('currency_code') == 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                        </select>
                        @error('currency_code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Invoice Date -->
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700">Invoice Date *</label>
                        <input type="date" name="date" id="date" required
                               value="{{ old('date', now()->format('Y-m-d')) }}"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('date') border-red-300 @enderror">
                        @error('date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date *</label>
                        <input type="date" name="due_date" id="due_date" required
                               value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('due_date') border-red-300 @enderror">
                        @error('due_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Scope/Description -->
                    <div class="md:col-span-2">
                        <label for="scope" class="block text-sm font-medium text-gray-700">Invoice Scope</label>
                        <input type="text" name="scope" id="scope" maxlength="255"
                               value="{{ old('scope') }}"
                               placeholder="Brief description of services or products"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('scope') border-red-300 @enderror">
                        @error('scope')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Discount Amount -->
                    <div>
                        <label for="discount_amount" class="block text-sm font-medium text-gray-700">Discount Amount</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="discount_amount" id="discount_amount" step="0.01" min="0"
                                   value="{{ old('discount_amount', '0.00') }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md @error('discount_amount') border-red-300 @enderror"
                                   placeholder="0.00">
                        </div>
                        @error('discount_amount')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status (hidden, will be set to Draft) -->
                    <input type="hidden" name="status" value="Draft">
                </div>

                <!-- Notes -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">Invoice Notes</label>
                    <textarea name="note" id="note" rows="3"
                              class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('note') border-red-300 @enderror"
                              placeholder="Additional notes or terms for this invoice...">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if(isset($ticket))
                    <!-- Associated Ticket -->
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Associated with Ticket</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>Ticket #{{ $ticket->id }}: {{ $ticket->subject }}</p>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                    </div>
                @endif

                <!-- Submit Buttons -->
                <div class="flex flex-col sm:flex-row justify-end items-center pt-6 border-t border-gray-200 gap-3">
                    <a href="{{ route('financial.invoices.index') }}"
                       class="w-full sm:w-auto inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Create Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set due date automatically when invoice date changes
    const invoiceDate = document.getElementById('date');
    const dueDate = document.getElementById('due_date');
    
    invoiceDate.addEventListener('change', function() {
        if (this.value) {
            const date = new Date(this.value);
            date.setDate(date.getDate() + 30); // Default 30 days payment terms
            dueDate.value = date.toISOString().split('T')[0];
        }
    });
    
    // Validate due date is not before invoice date
    dueDate.addEventListener('change', function() {
        const invDate = new Date(invoiceDate.value);
        const dueDateVal = new Date(this.value);
        
        if (dueDateVal < invDate) {
            alert('Due date cannot be before invoice date');
            this.value = invoiceDate.value;
        }
    });
});
</script>
@endpush
@endsection