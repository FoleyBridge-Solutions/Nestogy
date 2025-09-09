@extends('layouts.app')

@section('title', $license->name . ' - License Details')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-8 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $license->name }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ $license->vendor ? $license->vendor . ' - ' : '' }}{{ $license->client->display_name }}
                            </p>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('clients.licenses.standalone.index') }}" 
                           class="inline-flex items-center px-6 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Licenses
                        </a>
                        <a href="{{ route('clients.licenses.standalone.edit', $license) }}" 
                           class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit License
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main License Information -->
            <div class="lg:flex-1 px-6-span-2">
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">License Information</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">License Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $license->name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">License Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $license->license_type)) }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Client</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="{{ route('clients.show', $license->client) }}" 
                                       class="text-indigo-600 hover:text-indigo-500">
                                        {{ $license->client->display_name }}
                                    </a>
                                </dd>
                            </div>

                            @if($license->vendor)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Vendor</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $license->vendor }}</dd>
                            </div>
                            @endif

                            @if($license->version)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Version</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $license->version }}</dd>
                            </div>
                            @endif

                            @if($license->seats)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Number of Seats</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ number_format($license->seats) }}</dd>
                            </div>
                            @endif

                            @if($license->support_level)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Support Level</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $license->support_level)) }}</dd>
                            </div>
                            @endif

                            @if($license->description)
                            <div class="sm:flex-1 px-6-span-2">
                                <dt class="text-sm font-medium text-gray-500">Description</dt>
                                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $license->description }}</dd>
                            </div>
                            @endif

                            @if($license->license_key)
                            <div class="sm:flex-1 px-6-span-2">
                                <dt class="text-sm font-medium text-gray-500">License Key</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono bg-gray-50 p-6 rounded border break-all">{{ $license->license_key }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                @if($license->license_terms)
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">License Terms</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $license->license_terms }}</p>
                    </div>
                </div>
                @endif

                @if($license->notes)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Notes</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $license->notes }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:flex-1 px-6-span-1">
                <!-- Status -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Status</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <span class="inline-flex items-center px-6 py-1 rounded-full text-sm font-medium 
                            bg-{{ $license->status_color }}-100 text-{{ $license->status_color }}-800">
                            {{ $license->status_label }}
                        </span>
                        
                        <div class="mt-6 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Active</span>
                                <span class="{{ $license->is_active ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $license->is_active ? 'Yes' : 'No' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Auto Renewal</span>
                                <span class="{{ $license->auto_renewal ? 'text-green-600' : 'text-gray-600' }}">
                                    {{ $license->auto_renewal ? 'Yes' : 'No' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Dates -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Important Dates</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <dl class="space-y-4">
                            @if($license->purchase_date)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Purchase Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $license->purchase_date->format('M j, Y') }}</dd>
                            </div>
                            @endif

                            @if($license->renewal_date)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Renewal Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $license->renewal_date->format('M j, Y') }}</dd>
                            </div>
                            @endif

                            @if($license->expiry_date)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Expiry Date</dt>
                                <dd class="mt-1 text-sm {{ $license->isExpiringSoon() ? 'text-orange-600 font-medium' : ($license->isExpired() ? 'text-red-600 font-medium' : 'text-gray-900') }}">
                                    {{ $license->expiry_date->format('M j, Y') }}
                                    @if($license->days_until_expiry !== null)
                                        <div class="text-xs mt-1">
                                            @if($license->days_until_expiry > 0)
                                                {{ $license->days_until_expiry }} days left
                                            @elseif($license->days_until_expiry == 0)
                                                Expires today
                                            @else
                                                Expired {{ abs($license->days_until_expiry) }} days ago
                                            @endif
                                        </div>
                                    @endif
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Financial Information -->
                @if($license->purchase_cost || $license->renewal_cost)
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Financial</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <dl class="space-y-4">
                            @if($license->purchase_cost)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Purchase Cost</dt>
                                <dd class="mt-1 text-sm text-gray-900">${{ number_format($license->purchase_cost, 2) }}</dd>
                            </div>
                            @endif

                            @if($license->renewal_cost)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Renewal Cost</dt>
                                <dd class="mt-1 text-sm text-gray-900">${{ number_format($license->renewal_cost, 2) }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
                @endif

                <!-- Quick Actions -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Quick Actions</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <div class="space-y-3">
                            <a href="{{ route('clients.show', $license->client) }}" 
                               class="w-full inline-flex justify-center items-center px-6 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                View Client
                            </a>

                            <form method="POST" 
                                  action="{{ route('clients.licenses.standalone.destroy', $license) }}" 
                                  onsubmit="return confirm('Are you sure you want to delete this license? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full inline-flex justify-center items-center px-6 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete License
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
