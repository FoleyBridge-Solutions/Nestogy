@extends('client-portal.layouts.app')

@section('title', 'Quote #' . $quote->getFullNumber())

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-gray-200 pb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Quote #{{ $quote->getFullNumber() }}</h1>
                <p class="text-gray-600">{{ $quote->client->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('client.quotes.pdf', $quote) }}" 
                   class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download PDF
                </a>
                <a href="{{ route('client.quotes') }}" 
                   class="inline-flex items-center px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    Back to Quotes
                </a>
            </div>
        </div>
    </div>

    <!-- Quote Status Banner -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-6 py-1 rounded-full text-sm font-medium
                    @if($quote->status === 'Draft') bg-gray-100 text-gray-800
                    @elseif($quote->status === 'Sent') bg-blue-100 text-blue-800
                    @elseif($quote->status === 'Viewed') bg-purple-100 text-purple-800
                    @elseif($quote->status === 'Accepted') bg-green-100 text-green-800
                    @elseif($quote->status === 'Declined') bg-red-100 text-red-800
                    @elseif($quote->status === 'Expired') bg-yellow-100 text-yellow-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    {{ $quote->status }}
                </span>
                @if($quote->isExpired())
                    <span class="inline-flex items-center px-6 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        Expired
                    </span>
                @endif
            </div>
            <div class="text-sm text-gray-600">
                Valid until: {{ ($quote->valid_until ?? $quote->expire)->format('M d, Y') }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:flex-1 px-6-span-2 space-y-6">
            <!-- Quote Details -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Quote Details</h3>
                </div>
                <div class="px-6 py-6 grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Quote Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quote->date->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Valid Until</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ ($quote->valid_until ?? $quote->expire)->format('M d, Y') }}
                            @if($quote->isExpired())
                                <span class="text-red-600 ml-1">(Expired)</span>
                            @endif
                        </dd>
                    </div>
                    @if($quote->scope)
                    <div class="flex-1 px-6-span-2">
                        <dt class="text-sm font-medium text-gray-500">Scope of Work</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quote->scope }}</dd>
                    </div>
                    @endif
                    @if($quote->note)
                    <div class="flex-1 px-6-span-2">
                        <dt class="text-sm font-medium text-gray-500">Notes</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quote->note }}</dd>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quote Items -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Items & Services</h3>
                </div>
                @if($quote->items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($quote->items->sortBy('order') as $item)
                            <tr>
                                <td class="px-6 py-6">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                        @if($item->description)
                                        <div class="text-sm text-gray-500">{{ $item->description }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($item->quantity, 2) }}
                                </td>
                                <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($item->price, 2) }}
                                </td>
                                <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($item->total, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-500">No items in this quote.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:flex-1 px-6-span-1 space-y-6">
            <!-- Quote Summary -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Quote Summary</h3>
                </div>
                <div class="px-6 py-6">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Subtotal:</span>
                            <span class="text-sm text-gray-900">${{ number_format($quote->getSubtotal(), 2) }}</span>
                        </div>
                        @if($quote->getDiscountAmount() > 0)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Discount:</span>
                            <span class="text-sm text-gray-900">-${{ number_format($quote->getDiscountAmount(), 2) }}</span>
                        </div>
                        @endif
                        @if($quote->getTotalTax() > 0)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Tax:</span>
                            <span class="text-sm text-gray-900">${{ number_format($quote->getTotalTax(), 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between pt-3 border-t border-gray-200 font-medium">
                            <span class="text-base text-gray-900">Total:</span>
                            <span class="text-xl text-gray-900">${{ number_format($quote->amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            @if($quote->status === 'Sent' || $quote->status === 'Viewed')
            <div class="bg-blue-50 border border-blue-200 rounded-lg">
                <div class="px-6 py-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Next Steps</h3>
                            <div class="mt-1 text-sm text-blue-700">
                                <p>Please review this quote and contact us if you have any questions or would like to proceed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($quote->status === 'Accepted')
            <div class="bg-green-50 border border-green-200 rounded-lg">
                <div class="px-6 py-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Quote Accepted</h3>
                            <div class="mt-1 text-sm text-green-700">
                                <p>Thank you for accepting this quote! We will be in touch soon to begin the work.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Company Information -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Questions?</h3>
                </div>
                <div class="px-6 py-6">
                    <p class="text-sm text-gray-600 mb-6">
                        If you have any questions about this quote, please don't hesitate to contact us.
                    </p>
                    <div class="space-y-2">
                        @if($quote->client->primaryContact && $quote->client->primaryContact->email)
                        <div class="text-sm">
                            <span class="font-medium text-gray-700">Email:</span>
                            <a href="mailto:{{ $quote->client->primaryContact->email }}" class="text-blue-600 hover:text-blue-800">
                                {{ $quote->client->primaryContact->email }}
                            </a>
                        </div>
                        @endif
                        @if($quote->client->primaryContact && $quote->client->primaryContact->phone)
                        <div class="text-sm">
                            <span class="font-medium text-gray-700">Phone:</span>
                            <a href="tel:{{ $quote->client->primaryContact->phone }}" class="text-blue-600 hover:text-blue-800">
                                {{ $quote->client->primaryContact->phone }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
