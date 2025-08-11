@extends('layouts.app')

@section('title', $client->name . ' - Client Details')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        @if($client->avatar)
                            <img class="h-16 w-16 rounded-full object-cover" src="{{ Storage::url($client->avatar) }}" alt="{{ $client->name }}">
                        @else
                            <div class="h-16 w-16 rounded-full bg-gray-300 flex items-center justify-center">
                                <span class="text-xl font-medium text-gray-700">{{ substr($client->name, 0, 2) }}</span>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $client->name }}</h1>
                        <div class="flex items-center space-x-4 mt-1">
                            @if($client->company)
                                <p class="text-sm text-gray-500">{{ $client->company }}</p>
                            @endif
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $client->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $client->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ ucfirst($client->type ?? 'individual') }}
                            </span>
                            @if($client->lead)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Lead
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Customer
                                </span>
                            @endif
                        </div>
                        @if($client->tags->count() > 0)
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($client->tags as $tag)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex space-x-3">
                    @if($client->lead)
                        <form action="{{ route('clients.convert', $client) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Convert to Customer
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit {{ $client->lead ? 'Lead' : 'Client' }}
                    </a>
                    <a href="{{ $client->lead ? route('clients.leads') : route('clients.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to {{ $client->lead ? 'Leads' : 'Clients' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Open Tickets</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $client->tickets()->where('status', 'open')->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Invoices</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $client->invoices()->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                            <dd class="text-lg font-medium text-gray-900">${{ number_format($client->invoices()->sum('amount') ?? 0, 2) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Assets</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $client->assets()->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">IT Docs</dt>
                            <dd class="text-lg font-medium text-gray-900">
                                @php
                                    $itDocCount = \App\Domains\Client\Models\ClientITDocumentation::where('client_id', $client->id)
                                        ->where('tenant_id', auth()->user()->tenant_id)
                                        ->count();
                                @endphp
                                {{ $itDocCount }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="bg-white shadow rounded-lg" x-data="{ activeTab: 'overview' }">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'overview'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'overview', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'overview' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Overview
                </button>
                <button @click="activeTab = 'tickets'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'tickets', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'tickets' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Tickets
                </button>
                <button @click="activeTab = 'invoices'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'invoices', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'invoices' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Invoices
                </button>
                <button @click="activeTab = 'assets'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'assets', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'assets' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Assets
                </button>
                <button @click="activeTab = 'contacts'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'contacts', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'contacts' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Contacts
                </button>
                <button @click="activeTab = 'locations'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'locations', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'locations' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Locations
                </button>
                <button @click="activeTab = 'it-documentation'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'it-documentation', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'it-documentation' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    IT Documentation
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Contact Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="text-sm text-gray-900">
                                    <a href="mailto:{{ $client->email }}" class="text-blue-600 hover:text-blue-800">{{ $client->email }}</a>
                                </dd>
                            </div>
                            @if($client->phone)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                <dd class="text-sm text-gray-900">
                                    <a href="tel:{{ $client->phone }}" class="text-blue-600 hover:text-blue-800">{{ $client->phone }}</a>
                                </dd>
                            </div>
                            @endif
                            @if($client->website)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Website</dt>
                                <dd class="text-sm text-gray-900">
                                    <a href="{{ $client->website }}" target="_blank" class="text-blue-600 hover:text-blue-800">{{ $client->website }}</a>
                                </dd>
                            </div>
                            @endif
                            @if($client->tax_id_number)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tax ID</dt>
                                <dd class="text-sm text-gray-900">{{ $client->tax_id_number }}</dd>
                            </div>
                            @endif
                            @if($client->referral)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Referral Source</dt>
                                <dd class="text-sm text-gray-900">{{ $client->referral }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Address Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Address</h3>
                        @if($client->address || $client->city || $client->state || $client->zip_code || $client->country)
                        <div class="text-sm text-gray-900">
                            @if($client->address)
                                <div>{{ $client->address }}</div>
                            @endif
                            @if($client->city || $client->state || $client->zip_code)
                                <div>
                                    {{ $client->city }}{{ $client->city && ($client->state || $client->zip_code) ? ', ' : '' }}
                                    {{ $client->state }} {{ $client->zip_code }}
                                </div>
                            @endif
                            @if($client->country)
                                <div>{{ $client->country }}</div>
                            @endif
                        </div>
                        @else
                        <p class="text-sm text-gray-500">No address information available</p>
                        @endif
                    </div>

                    <!-- Billing Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Billing Information</h3>
                        <dl class="space-y-3">
                            @if($client->rate)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Default Rate</dt>
                                <dd class="text-sm text-gray-900">${{ number_format($client->rate, 2) }}/hour</dd>
                            </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Currency</dt>
                                <dd class="text-sm text-gray-900">{{ $client->currency_code ?? 'USD' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Payment Terms</dt>
                                <dd class="text-sm text-gray-900">Net {{ $client->net_terms ?? 30 }} days</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Additional Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Information</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Client Since</dt>
                                <dd class="text-sm text-gray-900">{{ $client->created_at->format('F j, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="text-sm text-gray-900">{{ $client->updated_at->format('F j, Y g:i A') }}</dd>
                            </div>
                            @if($client->accessed_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Accessed</dt>
                                <dd class="text-sm text-gray-900">{{ $client->accessed_at->format('F j, Y g:i A') }}</dd>
                            </div>
                            @endif
                            @if($client->created_by)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created By</dt>
                                <dd class="text-sm text-gray-900">{{ $client->creator->name ?? 'Unknown' }}</dd>
                            </div>
                            @endif
                        </dl>
                        @if($client->notes)
                        <div class="mt-4">
                            <dt class="text-sm font-medium text-gray-500 mb-2">Notes</dt>
                            <dd class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">{{ $client->notes }}</dd>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tickets Tab -->
            <div x-show="activeTab === 'tickets'">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Recent Tickets</h3>
                    <a href="#" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        New Ticket
                    </a>
                </div>
                @if($client->tickets()->count() > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($client->tickets()->latest()->take(10)->get() as $ticket)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <a href="#" class="hover:text-blue-600">#{{ $ticket->id }} - {{ Str::limit($ticket->subject, 50) }}</a>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->status_color ?? 'gray' }}-100 text-{{ $ticket->status_color ?? 'gray' }}-800">
                                        {{ ucfirst($ticket->status ?? 'open') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->priority_color ?? 'gray' }}-100 text-{{ $ticket->priority_color ?? 'gray' }}-800">
                                        {{ ucfirst($ticket->priority ?? 'medium') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $ticket->created_at->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="#" class="text-blue-600 hover:text-blue-900">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No tickets</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new ticket for this client.</p>
                </div>
                @endif
            </div>

            <!-- Invoices Tab -->
            <div x-show="activeTab === 'invoices'">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Recent Invoices</h3>
                    <a href="#" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        New Invoice
                    </a>
                </div>
                @if($client->invoices()->count() > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($client->invoices()->latest()->take(10)->get() as $invoice)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="#" class="hover:text-blue-600">#{{ $invoice->number ?? $invoice->id }}</a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $invoice->status_color ?? 'gray' }}-100 text-{{ $invoice->status_color ?? 'gray' }}-800">
                                        {{ ucfirst($invoice->status ?? 'draft') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($invoice->amount ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $invoice->created_at->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="#" class="text-blue-600 hover:text-blue-900">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No invoices</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new invoice for this client.</p>
                </div>
                @endif
            </div>

            <!-- Assets Tab -->
            <div x-show="activeTab === 'assets'">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Client Assets</h3>
                    <a href="#" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Asset
                    </a>
                </div>
                @if($client->assets()->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($client->assets()->latest()->take(12)->get() as $asset)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">
                                    <a href="#" class="hover:text-blue-600">{{ $asset->name }}</a>
                                </h4>
                                <p class="text-xs text-gray-500">{{ $asset->type ?? 'Unknown Type' }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $asset->status_color ?? 'gray' }}-100 text-{{ $asset->status_color ?? 'gray' }}-800">
                                {{ ucfirst($asset->status ?? 'active') }}
                            </span>
                        </div>
                        @if($asset->description)
                        <p class="mt-2 text-xs text-gray-600">{{ Str::limit($asset->description, 100) }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No assets</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding an asset for this client.</p>
                </div>
                @endif
            </div>

            <!-- Contacts Tab -->
            <div x-show="activeTab === 'contacts'">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Contacts</h3>
                    <a href="#" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Contact
                    </a>
                </div>
                @if($client->contacts()->count() > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($client->contacts as $contact)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="#" class="hover:text-blue-600">{{ $contact->name }}</a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <a href="mailto:{{ $contact->email }}" class="text-blue-600 hover:text-blue-800">{{ $contact->email }}</a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $contact->phone }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $contact->title }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No contacts</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding a contact for this client.</p>
                </div>
                @endif
            </div>

            <!-- Locations Tab -->
            <div x-show="activeTab === 'locations'">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Locations</h3>
                    <a href="#" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Location
                    </a>
                </div>
                @if($client->locations()->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($client->locations as $location)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900">
                            <a href="#" class="hover:text-blue-600">{{ $location->name }}</a>
                        </h4>
                        <div class="mt-2 text-sm text-gray-600">
                            @if($location->address)
                                <div>{{ $location->address }}</div>
                            @endif
                            @if($location->city || $location->state || $location->zip_code)
                                <div>
                                    {{ $location->city }}{{ $location->city && ($location->state || $location->zip_code) ? ', ' : '' }}
                                    {{ $location->state }} {{ $location->zip_code }}
                                </div>
                            @endif
                            @if($location->country)
                                <div>{{ $location->country }}</div>
                            @endif
                        </div>
                        @if($location->phone)
                            <div class="mt-2 text-sm text-gray-500">
                                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $location->phone }}
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No locations</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding a location for this client.</p>
                </div>
                @endif
            </div>

            <!-- IT Documentation Tab -->
            <div x-show="activeTab === 'it-documentation'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">IT Documentation</h3>
                    @can('create', \App\Domains\Client\Models\ClientITDocumentation::class)
                        <a href="{{ route('clients.it-documentation.create', ['client_id' => $client->id]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            New Documentation
                        </a>
                    @endcan
                </div>

                @php
                    $itDocumentation = \App\Domains\Client\Models\ClientITDocumentation::where('client_id', $client->id)
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->active()
                        ->with(['author'])
                        ->latest()
                        ->take(10)
                        ->get();
                    $itStats = [
                        'total' => \App\Domains\Client\Models\ClientITDocumentation::where('client_id', $client->id)->where('tenant_id', auth()->user()->tenant_id)->count(),
                        'needs_review' => \App\Domains\Client\Models\ClientITDocumentation::where('client_id', $client->id)->where('tenant_id', auth()->user()->tenant_id)->whereDate('next_review_at', '<=', now())->count(),
                        'categories' => \App\Domains\Client\Models\ClientITDocumentation::where('client_id', $client->id)->where('tenant_id', auth()->user()->tenant_id)->groupBy('it_category')->pluck('it_category')->count()
                    ];
                @endphp

                <!-- IT Documentation Stats -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-blue-900">Total Documents</p>
                                <p class="text-2xl font-semibold text-blue-600">{{ $itStats['total'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-900">Need Review</p>
                                <p class="text-2xl font-semibold text-yellow-600">{{ $itStats['needs_review'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-900">Categories</p>
                                <p class="text-2xl font-semibold text-green-600">{{ $itStats['categories'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($itDocumentation->count() > 0)
                    <div class="bg-white border border-gray-200 rounded-lg">
                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-900">Recent Documentation</h4>
                            <a href="{{ route('clients.it-documentation.index', ['client_id' => $client->id]) }}" 
                               class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach($itDocumentation as $doc)
                                <div class="p-4 hover:bg-gray-50">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-lg">{{ $doc->category_icon }}</span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2">
                                                <h5 class="text-sm font-medium text-gray-900 truncate">
                                                    <a href="{{ route('clients.it-documentation.show', $doc) }}" 
                                                       class="hover:text-blue-600">{{ $doc->name }}</a>
                                                </h5>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ \App\Domains\Client\Models\ClientITDocumentation::getITCategories()[$doc->it_category] }}
                                                </span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $doc->access_level_color }}-100 text-{{ $doc->access_level_color }}-800">
                                                    {{ \App\Domains\Client\Models\ClientITDocumentation::getAccessLevels()[$doc->access_level] }}
                                                </span>
                                                @if($doc->needsReview())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Review Due
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center space-x-4 mt-1">
                                                <span class="text-xs text-gray-500">v{{ $doc->version }}</span>
                                                <span class="text-xs text-gray-500">{{ $doc->author->name }}</span>
                                                <span class="text-xs text-gray-500">{{ $doc->updated_at->format('M j, Y') }}</span>
                                                @if($doc->hasFile())
                                                    <span class="text-xs text-gray-500">{{ $doc->file_size_human }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            @can('view', $doc)
                                                <a href="{{ route('clients.it-documentation.show', $doc) }}" 
                                                   class="text-blue-600 hover:text-blue-800 text-xs font-medium">View</a>
                                            @endcan
                                            @can('update', $doc)
                                                <a href="{{ route('clients.it-documentation.edit', $doc) }}" 
                                                   class="text-gray-600 hover:text-gray-800 text-xs font-medium">Edit</a>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($itStats['total'] > 10)
                            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-center">
                                <a href="{{ route('clients.it-documentation.index', ['client_id' => $client->id]) }}" 
                                   class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    View all {{ $itStats['total'] }} documents →
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No IT documentation</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating technical documentation for this client.</p>
                        @can('create', \App\Domains\Client\Models\ClientITDocumentation::class)
                            <div class="mt-6">
                                <a href="{{ route('clients.it-documentation.create', ['client_id' => $client->id]) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Create IT Documentation
                                </a>
                            </div>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush