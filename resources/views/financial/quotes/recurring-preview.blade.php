@extends('layouts.app')

@section('title', 'Preview Recurring Conversion - Quote #' . $quote->getFullNumber())

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <x-page-header 
        :title="'Preview Recurring Conversion'"
        :subtitle="'Quote #' . $quote->getFullNumber() . ' - ' . $quote->client->name"
        :back-route="route('financial.quotes.show', $quote)"
        back-label="Back to Quote"
    />

    <!-- Preview Content -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                Recurring Billing Preview
            </h3>

            <div class="space-y-6">
                <!-- Quote Details -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Quote Details</h4>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Client</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $quote->client->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Quote Amount</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $quote->formatted_amount ?? '$' . number_format($quote->amount, 2) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Billing Frequency Options -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Projected Annual Revenue by Frequency</h4>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($billing_frequencies as $frequency => $amount)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500 capitalize">{{ str_replace('-', ' ', $frequency) }}</div>
                            <div class="mt-1 text-xl font-semibold text-gray-900">${{ number_format($amount, 2) }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Available Services -->
                @if(!empty($available_voip_services))
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Available VoIP Services</h4>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($available_voip_services as $key => $label)
                        <li class="text-sm text-gray-600">{{ $label }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Items Preview -->
                @if($quote->items && $quote->items->count() > 0)
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Quote Items</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($quote->items as $item)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $item->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $item->quantity }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900">${{ number_format($item->price, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900">${{ number_format($item->quantity * $item->price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-gray-50 px-4 py-4 sm:px-6 flex justify-end space-x-3">
            <a href="{{ route('financial.quotes.show', $quote) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <form method="POST" action="{{ route('financial.quotes.convert-to-recurring', $quote) }}" class="inline">
                @csrf
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Convert to Recurring
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
