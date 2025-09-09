@extends('layouts.app')

@section('title', 'Create Payment')

@section('content')
<div class="container mx-auto mx-auto px-4 mx-auto px-4 mx-auto px-6 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Payment</h1>
                <p class="text-gray-600 mt-1">Record a new payment transaction</p>
            </div>
            <a href="{{ route('financial.payments.index') }}" 
               class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Payments
            </a>
        </div>

        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('financial.payments.store') }}" class="space-y-6 p-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Client Selection -->
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                        <select name="client_id" id="client_id" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('client_id') border-red-300 @enderror">
                            <option value="">Select a client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $selectedClientId) == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }} {{ $client->company_name ? '(' . $client->company_name . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Invoice Selection -->
                    <div>
                        <label for="invoice_id" class="block text-sm font-medium text-gray-700">Invoice (Optional)</label>
                        <select name="invoice_id" id="invoice_id"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('invoice_id') border-red-300 @enderror">
                            <option value="">No specific invoice</option>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice->id }}" {{ old('invoice_id', $selectedInvoiceId) == $invoice->id ? 'selected' : '' }}>
                                    {{ $invoice->invoice_number }} - {{ $invoice->client->name }} (${{ number_format($invoice->total, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('invoice_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount *</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01" max="999999.99" required
                                   value="{{ old('amount') }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md @error('amount') border-red-300 @enderror"
                                   placeholder="0.00">
                        </div>
                        @error('amount')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Currency -->
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700">Currency *</label>
                        <select name="currency" id="currency" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('currency') border-red-300 @enderror">
                            <option value="USD" {{ old('currency', 'USD') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                            <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                            <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                            <option value="CAD" {{ old('currency') == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                        </select>
                        @error('currency')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method *</label>
                        <select name="payment_method" id="payment_method" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('payment_method') border-red-300 @enderror">
                            <option value="">Select payment method</option>
                            @foreach($paymentMethods as $key => $label)
                                <option value="{{ $key }}" {{ old('payment_method') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Gateway -->
                    <div>
                        <label for="gateway" class="block text-sm font-medium text-gray-700">Gateway *</label>
                        <select name="gateway" id="gateway" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('gateway') border-red-300 @enderror">
                            <option value="">Select gateway</option>
                            @foreach($gateways as $key => $label)
                                <option value="{{ $key }}" {{ old('gateway', 'manual') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('gateway')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <label for="payment_date" class="block text-sm font-medium text-gray-700">Payment Date *</label>
                        <input type="datetime-local" name="payment_date" id="payment_date" required
                               value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('payment_date') border-red-300 @enderror">
                        @error('payment_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Reference -->
                    <div>
                        <label for="payment_reference" class="block text-sm font-medium text-gray-700">Payment Reference</label>
                        <input type="text" name="payment_reference" id="payment_reference" maxlength="255"
                               value="{{ old('payment_reference') }}"
                               placeholder="Leave blank to auto-generate"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('payment_reference') border-red-300 @enderror">
                        @error('payment_reference')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Gateway Transaction ID -->
                    <div>
                        <label for="gateway_transaction_id" class="block text-sm font-medium text-gray-700">Gateway Transaction ID</label>
                        <input type="text" name="gateway_transaction_id" id="gateway_transaction_id" maxlength="255"
                               value="{{ old('gateway_transaction_id') }}"
                               placeholder="Transaction ID from payment gateway"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('gateway_transaction_id') border-red-300 @enderror">
                        @error('gateway_transaction_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Gateway Fee -->
                    <div>
                        <label for="gateway_fee" class="block text-sm font-medium text-gray-700">Gateway Fee</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="gateway_fee" id="gateway_fee" step="0.01" min="0" max="9999.99"
                                   value="{{ old('gateway_fee') }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md @error('gateway_fee') border-red-300 @enderror"
                                   placeholder="0.00">
                        </div>
                        @error('gateway_fee')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" id="notes" rows="3" maxlength="1000"
                              class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('notes') border-red-300 @enderror"
                              placeholder="Additional notes about this payment...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('financial.payments.index') }}" 
                       class="inline-flex justify-center px-6 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex justify-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Create Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter invoices by selected client
    const clientSelect = document.getElementById('client_id');
    const invoiceSelect = document.getElementById('invoice_id');
    const originalInvoices = [...invoiceSelect.options];

    clientSelect.addEventListener('change', function() {
        const selectedClientId = this.value;
        
        // Clear current options except the first one
        invoiceSelect.innerHTML = '<option value="">No specific invoice</option>';
        
        if (selectedClientId) {
            // Filter invoices by selected client
            originalInvoices.slice(1).forEach(option => {
                if (option.text.includes('- {{ $invoice->client->name }}') || 
                    option.value === '{{ $selectedInvoiceId }}') {
                    invoiceSelect.appendChild(option.cloneNode(true));
                }
            });
        }
    });

    // Trigger initial filtering if client is pre-selected
    if (clientSelect.value) {
        clientSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection
