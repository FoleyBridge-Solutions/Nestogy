@extends('layouts.app')

@section('title', 'Quote #' . $quote->getFullNumber())

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Quote #{{ $quote->getFullNumber() }}</h1>
                <p class="text-gray-600 mt-1">{{ $quote->client->name }}</p>
                @if($quote->template_name)
                <p class="text-sm text-indigo-600 mt-1">Template: {{ $quote->template_name }}</p>
                @endif
            </div>
            <div class="flex gap-3">
                <a href="{{ route('financial.quotes.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Quotes
                </a>

                @can('generatePdf', $quote)
                <a href="{{ route('financial.quotes.pdf', $quote) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download PDF
                </a>
                @endcan

                @can('send', $quote)
                @if($quote->isFullyApproved() || $quote->approval_status === 'not_required')
                <button onclick="sendQuote({{ $quote->id }})"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Send Quote
                </button>
                @endif
                @endcan

                @can('update', $quote)
                @if($quote->isDraft() || $quote->approval_status === 'rejected')
                <a href="{{ route('financial.quotes.edit', $quote) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Edit Quote
                </a>
                @endif
                @endcan

                @can('convert', $quote)
                @if($quote->isAccepted())
                <button onclick="convertQuote({{ $quote->id }})"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    Convert to Invoice
                </button>
                @endif
                @endcan
            </div>
        </div>

        <!-- Quote Status and Key Information -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Quote Status</h3>
                    <div class="flex items-center space-x-3">
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
                        
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($quote->approval_status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($quote->approval_status === 'manager_approved') bg-blue-100 text-blue-800
                            @elseif($quote->approval_status === 'executive_approved') bg-green-100 text-green-800
                            @elseif($quote->approval_status === 'rejected') bg-red-100 text-red-800
                            @elseif($quote->approval_status === 'not_required') bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $quote->approval_status)) }}
                        </span>

                        @if($quote->isExpired())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Expired
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Quote Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quote->date->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Expires</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($quote->expire_date || $quote->valid_until)
                                {{ ($quote->valid_until ?? $quote->expire_date)->format('M d, Y') }}
                                @if($quote->isExpired())
                                    <span class="text-red-600 ml-1">(Expired)</span>
                                @endif
                            @else
                                No expiration
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quote->getFormattedAmount() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Version</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            v{{ $quote->version }}
                            @if($quote->versions->count() > 1)
                                <button onclick="showVersionHistory()" class="text-indigo-600 hover:text-indigo-900 ml-1 text-xs">
                                    (View History)
                                </button>
                            @endif
                        </dd>
                    </div>
                </div>

                @if($quote->sent_at || $quote->viewed_at || $quote->accepted_at || $quote->declined_at)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        @if($quote->sent_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sent</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $quote->sent_at->format('M d, Y g:i A') }}</dd>
                        </div>
                        @endif
                        @if($quote->viewed_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Viewed</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $quote->viewed_at->format('M d, Y g:i A') }}</dd>
                        </div>
                        @endif
                        @if($quote->accepted_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Accepted</dt>
                            <dd class="mt-1 text-sm text-green-600">{{ $quote->accepted_at->format('M d, Y g:i A') }}</dd>
                        </div>
                        @endif
                        @if($quote->declined_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Declined</dt>
                            <dd class="mt-1 text-sm text-red-600">{{ $quote->declined_at->format('M d, Y g:i A') }}</dd>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Approval Workflow -->
        @can('viewApprovals', $quote)
        @if($quote->approvals->count() > 0)
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Approval Workflow</h3>
                    @can('approve', $quote)
                    @if($quote->needsApproval())
                    <a href="{{ route('financial.quotes.approve', $quote) }}" 
                       class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200">
                        Process Approval
                    </a>
                    @endif
                    @endcan
                </div>
            </div>
            <div class="px-6 py-4">
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
        </div>
        @endif
        @endcan

        <!-- Client Information -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Client Information</h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
        </div>

        <!-- VoIP Configuration -->
        @if($quote->voip_config)
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">VoIP Configuration</h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Extensions</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quote->voip_config['extensions'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Concurrent Calls</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quote->voip_config['concurrent_calls'] ?? 'N/A' }}</dd>
                    </div>
                </div>

                @if(isset($quote->voip_config['features']))
                <div class="mt-6">
                    <dt class="text-sm font-medium text-gray-500 mb-3">Features</dt>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($quote->voip_config['features'] as $feature => $enabled)
                        <div class="flex items-center">
                            @if($enabled)
                            <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            @else
                            <svg class="h-4 w-4 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            @endif
                            <span class="text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $feature)) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(isset($quote->voip_config['equipment']))
                <div class="mt-6">
                    <dt class="text-sm font-medium text-gray-500 mb-3">Equipment</dt>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach($quote->voip_config['equipment'] as $equipment => $quantity)
                        @if($quantity > 0)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ ucwords(str_replace('_', ' ', $equipment)) }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $quantity }}</dd>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Quote Items -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Quote Items</h3>
            </div>
            @if($quote->items->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($quote->items->sortBy('order') as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                    @if($item->description)
                                    <div class="text-sm text-gray-500">{{ $item->description }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($item->price, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($item->discount > 0)
                                -${{ number_format($item->discount, 2) }}
                                @else
                                -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
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
        </div>

        <!-- Quote Totals -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Quote Summary</h3>
            </div>
            <div class="px-6 py-4">
                <div class="max-w-md ml-auto">
                    <div class="flex justify-between py-2">
                        <span class="text-sm text-gray-600">Subtotal:</span>
                        <span class="text-sm text-gray-900">${{ number_format($totals['subtotal'], 2) }}</span>
                    </div>
                    @if($totals['discount_amount'] > 0)
                    <div class="flex justify-between py-2">
                        <span class="text-sm text-gray-600">
                            Discount @if($quote->discount_type === 'percentage')({{ $quote->discount_amount }}%)@endif:
                        </span>
                        <span class="text-sm text-gray-900">-${{ number_format($totals['discount_amount'], 2) }}</span>
                    </div>
                    @endif
                    @if($totals['tax_amount'] > 0)
                    <div class="flex justify-between py-2">
                        <span class="text-sm text-gray-600">Tax:</span>
                        <span class="text-sm text-gray-900">${{ number_format($totals['tax_amount'], 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-2 border-t border-gray-200 font-medium">
                        <span class="text-sm text-gray-900">Total:</span>
                        <span class="text-sm text-gray-900">${{ number_format($totals['total'], 2) }}</span>
                    </div>
                    @if(isset($totals['voip_breakdown']))
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">VoIP Pricing Breakdown</h4>
                        @if($totals['voip_breakdown']['setup_fees'] > 0)
                        <div class="flex justify-between py-1">
                            <span class="text-sm text-gray-600">Setup Fee:</span>
                            <span class="text-sm text-gray-900">${{ number_format($totals['voip_breakdown']['setup_fees'], 2) }}</span>
                        </div>
                        @endif
                        @if($totals['voip_breakdown']['monthly_recurring'] > 0)
                        <div class="flex justify-between py-1">
                            <span class="text-sm text-gray-600">Monthly Recurring:</span>
                            <span class="text-sm text-gray-900">${{ number_format($totals['voip_breakdown']['monthly_recurring'], 2) }}</span>
                        </div>
                        @endif
                        @if($totals['voip_breakdown']['equipment_costs'] > 0)
                        <div class="flex justify-between py-1">
                            <span class="text-sm text-gray-600">Equipment:</span>
                            <span class="text-sm text-gray-900">${{ number_format($totals['voip_breakdown']['equipment_costs'], 2) }}</span>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Auto-Renewal Settings -->
        @if($quote->auto_renew)
        <div class="bg-blue-50 border border-blue-200 rounded-lg mb-6">
            <div class="px-6 py-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Auto-Renewal Enabled</h3>
                        <div class="mt-1 text-sm text-blue-700">
                            <p>This quote will automatically renew every {{ $quote->auto_renew_days }} days after expiration.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Notes -->
        @if($quote->note)
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Notes</h3>
            </div>
            <div class="px-6 py-4">
                <p class="text-sm text-gray-700">{{ $quote->note }}</p>
            </div>
        </div>
        @endif

        <!-- Terms and Conditions -->
        @if($quote->terms)
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Terms and Conditions</h3>
            </div>
            <div class="px-6 py-4">
                <p class="text-sm text-gray-700">{{ $quote->terms }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Version History Modal -->
<div id="versionHistoryModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
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

<script>
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
                    <div class="border border-gray-200 rounded-lg p-4">
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
</script>