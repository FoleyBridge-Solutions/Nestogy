@extends('layouts.app')

@section('title', 'Quote #' . $quote->getFullNumber())

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <x-page-header 
        :title="'Quote #' . $quote->getFullNumber()"
        :subtitle="$quote->client->name . ($quote->template_name ? ' • Template: ' . $quote->template_name : '')"
        :back-route="route('financial.quotes.index')"
        back-label="Back to Quotes"
    >
        <x-slot name="actions">
            <div class="flex gap-3">
                @can('generatePdf', $quote)
                <a href="{{ route('financial.quotes.pdf', $quote) }}" 
                   class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download PDF
                </a>
                @endcan

                @can('view', $quote)
                <a href="{{ route('client.quotes.show', $quote) }}" 
                   target="_blank"
                   class="inline-flex items-center px-6 py-2 border border-purple-300 rounded-md shadow-sm text-sm font-medium text-purple-700 bg-white hover:bg-purple-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview Client View
                </a>
                @endcan

                @can('create', App\Models\Quote::class)
                <a href="{{ route('financial.quotes.copy', $quote) }}" 
                   class="inline-flex items-center px-6 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white hover:bg-blue-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Copy Quote
                </a>
                @endcan

                @can('send', $quote)
                @if($quote->isFullyApproved())
                <button onclick="sendQuote({{ $quote->id }})"
                        class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Send Quote
                </button>
                @endif
                @endcan

                @can('update', $quote)
                @if($quote->isDraft())
                <a href="{{ route('financial.quotes.edit', $quote) }}" 
                   class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Edit Quote
                </a>
                @endif
                @endcan

                @can('convert', $quote)
                @if($quote->isAccepted())
                <button onclick="convertQuote({{ $quote->id }})"
                        class="inline-flex items-center px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    Convert to Invoice
                </button>
                @endif
                @endcan

                @can('cancel', $quote)
                @if(in_array($quote->status, ['Sent', 'Viewed', 'Accepted', 'Declined']))
                <button onclick="cancelQuote({{ $quote->id }}, '{{ $quote->getFullNumber() }}')"
                        class="inline-flex items-center px-6 py-2 border border-orange-300 rounded-md shadow-sm text-sm font-medium text-orange-700 bg-white hover:bg-orange-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                    </svg>
                    Cancel Quote
                </button>
                @endif
                @endcan

                @can('delete', $quote)
                <button onclick="deleteQuote({{ $quote->id }}, '{{ $quote->getFullNumber() }}')"
                        class="inline-flex items-center px-6 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Quote
                </button>
                @endcan
            </div>
        </x-slot>
    </x-page-header>

    <!-- Main Content Grid -->
    <div class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column (Main Content - 2/3 width) -->
            <div class="lg:flex-1 px-6-span-2 space-y-6">
                
                <!-- Quote Items -->
                <x-content-card>
                    <div class="px-6 py-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Quote Items</h3>
                    </div>
                    @if($quote->items->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                    <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($quote->items->sortBy('order') as $item)
                                <tr>
                                    <td class="px-6 py-6 whitespace-nowrap">
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
                                        @if($item->discount > 0)
                                        -${{ number_format($item->discount, 2) }}
                                        @else
                                        -
                                        @endif
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
                        <p class="text-gray-500">No items added to this quote yet.</p>
                    </div>
                    @endif
                </x-content-card>

                <!-- Approval Workflow -->
                @can('viewApprovals', $quote)
                @if($quote->approvals->count() > 0)
                <x-content-card>
            <div class="px-6 py-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Approval Workflow</h3>
                    @can('approve', $quote)
                    @if($quote->needsApproval())
                    <a href="{{ route('financial.quotes.approve', $quote) }}" 
                       class="inline-flex items-center px-6 py-1 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200">
                        Process Approval
                    </a>
                    @endif
                    @endcan
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="flow-root">
                    <ul class="-mb-8">
                        @foreach($quote->approvals->sortBy('created_at') as $approval)
                        <li>
                            <div class="relative pb-8">
                                @unless($loop->last)
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                @endunless
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                            @if($approval->isApproved()) bg-green-500
                                            @elseif($approval->isRejected()) bg-red-500
                                            @else bg-gray-400
                                            @endif">
                                            @if($approval->isApproved())
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            @elseif($approval->isRejected())
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                            @else
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                            </svg>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                {{ $approval->getLevelLabel() }} approval
                                                @if($approval->user)
                                                by <span class="font-medium text-gray-900">{{ $approval->user->name }}</span>
                                                @endif
                                            </p>
                                            @if($approval->comments)
                                            <p class="mt-1 text-sm text-gray-700">{{ $approval->comments }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                            @if($approval->approved_at)
                                            <time>{{ $approval->approved_at->format('M d, g:i A') }}</time>
                                            @elseif($approval->rejected_at)
                                            <time>{{ $approval->rejected_at->format('M d, g:i A') }}</time>
                                            @else
                                            <span class="text-yellow-600">Pending</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
                </x-content-card>
                @endif
                @endcan

            </div>

            <!-- Right Column (Sidebar - 1/3 width) -->
            <div class="lg:flex-1 px-6-span-1 space-y-6">
                
                <!-- Quote Status -->
                <x-content-card>
                    <div class="px-6 py-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Status</h3>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($quote->status === 'Draft') bg-gray-100 text-gray-800
                                    @elseif($quote->status === 'Sent') bg-blue-100 text-blue-800
                                    @elseif($quote->status === 'Viewed') bg-purple-100 text-purple-800
                                    @elseif($quote->status === 'Accepted') bg-green-100 text-green-800
                                    @elseif($quote->status === 'Declined') bg-red-100 text-red-800
                                    @elseif($quote->status === 'Expired') bg-yellow-100 text-yellow-800
                                    @elseif($quote->status === 'Converted') bg-indigo-100 text-indigo-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $quote->status }}
                                </span>
                                @if($quote->isExpired())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Expired
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-6">
                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Quote Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $quote->date->format('M d, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Expires</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($quote->expire || $quote->valid_until)
                                        {{ ($quote->valid_until ?? $quote->expire)->format('M d, Y') }}
                                        @if($quote->isExpired())
                                            <span class="text-red-600 ml-1">(Expired)</span>
                                        @endif
                                    @else
                                        No expiration
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Version</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    v{{ $quote->versions->count() > 0 ? $quote->versions->max('version_number') : 1 }}
                                    @if($quote->versions->count() > 1)
                                        <button onclick="showVersionHistory()" class="text-indigo-600 hover:text-indigo-900 ml-1 text-xs">
                                            (View History)
                                        </button>
                                    @endif
                                </dd>
                            </div>
                            @if($quote->sent_at || $quote->viewed_at || $quote->accepted_at || $quote->declined_at)
                            <div class="pt-4 border-t border-gray-200">
                                @if($quote->sent_at)
                                <div class="mb-2">
                                    <dt class="text-sm font-medium text-gray-500">Sent</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $quote->sent_at->format('M d, Y g:i A') }}</dd>
                                </div>
                                @endif
                                @if($quote->viewed_at)
                                <div class="mb-2">
                                    <dt class="text-sm font-medium text-gray-500">Viewed</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $quote->viewed_at->format('M d, Y g:i A') }}</dd>
                                </div>
                                @endif
                                @if($quote->accepted_at)
                                <div class="mb-2">
                                    <dt class="text-sm font-medium text-gray-500">Accepted</dt>
                                    <dd class="mt-1 text-sm text-green-600">{{ $quote->accepted_at->format('M d, Y g:i A') }}</dd>
                                </div>
                                @endif
                                @if($quote->declined_at)
                                <div class="mb-2">
                                    <dt class="text-sm font-medium text-gray-500">Declined</dt>
                                    <dd class="mt-1 text-sm text-red-600">{{ $quote->declined_at->format('M d, Y g:i A') }}</dd>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </x-content-card>

                <!-- Client Information -->
                <x-content-card>
                    <div class="px-6 py-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Client Information</h3>
                    </div>
                    <div class="px-6 py-6">
                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Client Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $quote->client->name }}</dd>
                                @if($quote->client->company_name)
                                <dd class="text-sm text-gray-500">{{ $quote->client->company_name }}</dd>
                                @endif
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Category</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $quote->category->name ?? 'N/A' }}</dd>
                            </div>
                        </div>
                    </div>
                </x-content-card>

                <!-- Quote Totals -->
                <x-content-card>
                    <div class="px-6 py-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Quote Summary</h3>
                    </div>
                    <div class="px-6 py-6">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Subtotal:</span>
                                <span class="text-sm text-gray-900">${{ number_format($totals['subtotal'], 2) }}</span>
                            </div>
                            @if($totals['discount_amount'] > 0)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Discount:</span>
                                <span class="text-sm text-gray-900">-${{ number_format($totals['discount_amount'], 2) }}</span>
                            </div>
                            @endif
                            @php
                                $calculatedTax = ($totals['total'] - $totals['subtotal'] + $totals['discount_amount']);
                                $hasTaxCalculation = $quote->latestTaxCalculation();
                                $realTimeTaxBreakdown = $quote->getFormattedTaxBreakdown();
                            @endphp
                            @if($totals['tax_amount'] > 0 || $calculatedTax > 0 || $hasTaxCalculation || $realTimeTaxBreakdown['has_breakdown'] ?? false)
                            <x-tax-jurisdiction-breakdown 
                                :tax-calculation="$quote->latestTaxCalculation()" 
                                :collapsible="true" 
                                :fallback-tax-amount="$calculatedTax"
                                :real-time-breakdown="$realTimeTaxBreakdown"
                            />
                            @endif
                            <div class="flex justify-between pt-3 border-t border-gray-200 font-medium">
                                <span class="text-sm text-gray-900">Total:</span>
                                <span class="text-lg text-gray-900">${{ number_format($totals['total'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </x-content-card>

                <!-- Auto-Renewal Settings -->
                @if($quote->auto_renew)
                <x-content-card>
                    <div class="px-6 py-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Auto-Renewal Enabled</h3>
                                <div class="mt-1 text-sm text-blue-700">
                                    <p>Renews every {{ $quote->auto_renew_days }} days</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-content-card>
                @endif

            </div>
        </div>
    </div>
</div>
    <!-- Version History Modal -->
    <div id="versionHistoryModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-8 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Version History</h3>
                    <button onclick="hideVersionHistory()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="versionHistoryContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Quote Actions - Preserved for show view functionality
function sendQuote(quoteId) {
    if (confirm('Are you sure you want to send this quote to the client?')) {
        fetch(`/financial/quotes/${quoteId}/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Quote sent successfully!');
                location.reload();
            } else {
                alert('Error sending quote: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending quote. Please try again.');
        });
    }
}

function convertQuote(quoteId) {
    if (confirm('Are you sure you want to convert this quote to an invoice? This action cannot be undone.')) {
        fetch(`/financial/quotes/${quoteId}/convert`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Quote converted to invoice successfully!');
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    location.reload();
                }
            } else {
                alert('Error converting quote: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error converting quote. Please try again.');
        });
    }
}

function showVersionHistory() {
    fetch(`/financial/quotes/{{ $quote->id }}/versions`)
        .then(response => response.json())
        .then(data => {
            let content = '<div class="space-y-4">';
            data.versions.forEach(version => {
                content += `
                    <div class="border border-gray-200 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-900">Version ${version.version}</h4>
                            <span class="text-xs text-gray-500">${version.created_at}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">Total: $${parseFloat(version.total_amount).toFixed(2)}</p>
                        ${version.change_summary ? `<p class="text-sm text-gray-700">${version.change_summary}</p>` : ''}
                        ${version.user_name ? `<p class="text-xs text-gray-500 mt-2">Created by: ${version.user_name}</p>` : ''}
                    </div>
                `;
            });
            content += '</div>';
            
            document.getElementById('versionHistoryContent').innerHTML = content;
            document.getElementById('versionHistoryModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error loading version history:', error);
            alert('Error loading version history');
        });
}

function hideVersionHistory() {
    document.getElementById('versionHistoryModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('versionHistoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideVersionHistory();
    }
});

function deleteQuote(quoteId, quoteNumber) {
    if (confirm(`Are you sure you want to delete quote ${quoteNumber}? This action cannot be undone.`)) {
        fetch(`/financial/quotes/${quoteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Network response was not ok');
        })
        .then(data => {
            if (data.success) {
                window.location.href = '/financial/quotes';
            } else {
                alert('Error: ' + (data.message || 'Failed to delete quote'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Fallback form submission
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/financial/quotes/${quoteId}`;
            form.style.display = 'none';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfInput);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            form.submit();
        });
    }
}

function cancelQuote(quoteId, quoteNumber) {
    if (confirm(`Are you sure you want to cancel quote ${quoteNumber}? This will mark it as cancelled and it cannot be sent or converted.`)) {
        fetch(`/financial/quotes/${quoteId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to cancel quote'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error cancelling quote');
        });
    }
}
</script>
@endpush
