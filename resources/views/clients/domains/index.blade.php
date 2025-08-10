@extends('layouts.app')

@section('title', 'Client Domains')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Client Domains</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage domain registrations and DNS settings across your clients.</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('clients.domains.standalone.export', request()->query()) }}" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export CSV
                        </a>
                        <a href="{{ route('clients.domains.standalone.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Domain
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <form method="GET" action="{{ route('clients.domains.standalone.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Domain, registrar..."
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" 
                                    id="status" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Client -->
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700">Client</label>
                            <select name="client_id" 
                                    id="client_id" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Clients</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Registrar -->
                        <div>
                            <label for="registrar" class="block text-sm font-medium text-gray-700">Registrar</label>
                            <select name="registrar" 
                                    id="registrar" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Registrars</option>
                                @foreach($registrars as $key => $label)
                                    <option value="{{ $key }}" {{ request('registrar') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- TLD -->
                        <div>
                            <label for="tld" class="block text-sm font-medium text-gray-700">TLD</label>
                            <select name="tld" 
                                    id="tld" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All TLDs</option>
                                @foreach($tlds as $tld)
                                    <option value="{{ $tld }}" {{ request('tld') === $tld ? 'selected' : '' }}>
                                        .{{ $tld }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Actions and Filters -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Filters</label>
                            <div class="space-y-1">
                                <div class="flex items-center h-5">
                                    <input id="expired_only" 
                                           name="expired_only" 
                                           type="checkbox" 
                                           value="1"
                                           {{ request('expired_only') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    <label for="expired_only" class="ml-2 text-xs text-gray-700">Expired only</label>
                                </div>
                                <div class="flex items-center h-5">
                                    <input id="expiring_soon" 
                                           name="expiring_soon" 
                                           type="checkbox" 
                                           value="1"
                                           {{ request('expiring_soon') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    <label for="expiring_soon" class="ml-2 text-xs text-gray-700">Expiring soon</label>
                                </div>
                                <div class="flex items-center h-5">
                                    <input id="auto_renewal" 
                                           name="auto_renewal" 
                                           type="checkbox" 
                                           value="1"
                                           {{ request('auto_renewal') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    <label for="auto_renewal" class="ml-2 text-xs text-gray-700">Auto renewal</label>
                                </div>
                            </div>
                            <div class="flex space-x-2 pt-2">
                                <button type="submit" 
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Filter
                                </button>
                                <a href="{{ route('clients.domains.standalone.index') }}" 
                                   class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Domains Grid -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Domains 
                    <span class="text-sm text-gray-500">({{ $domains->total() }} total)</span>
                </h3>
            </div>
            
            @if($domains->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 p-6">
                    @foreach($domains as $domain)
                        @php
                            $expiryStatus = $domain->expiry_status;
                            $securityStatus = $domain->security_status;
                        @endphp
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200 
                                    {{ $expiryStatus === 'expired' ? 'bg-red-50 border-red-200' : '' }}
                                    {{ $expiryStatus === 'expiring_soon' ? 'bg-yellow-50 border-yellow-200' : '' }}">
                            <!-- Domain Header -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center">
                                    <span class="text-2xl mr-2">🌐</span>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 truncate">{{ $domain->full_domain }}</h4>
                                        <p class="text-xs text-gray-500">{{ $domain->name }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col space-y-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                                {{ $domain->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $domain->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $domain->status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $domain->status_color === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $domain->status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ $statuses[$domain->status] }}
                                    </span>
                                    @if($domain->auto_renewal)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            🔄 Auto Renewal
                                        </span>
                                    @endif
                                    @if($expiryStatus === 'expired')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            ❌ Expired
                                        </span>
                                    @elseif($expiryStatus === 'expiring_soon')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            ⚠️ Expiring
                                        </span>
                                    @endif
                                    @if($securityStatus === 'vulnerable')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            🔓 Vulnerable
                                        </span>
                                    @elseif($securityStatus === 'warning')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            ⚠️ Security
                                        </span>
                                    @elseif($securityStatus === 'secure')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            🔒 Secure
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Domain Info -->
                            <div class="space-y-2 mb-4">
                                <div class="text-sm text-gray-600">
                                    <strong>Client:</strong> {{ $domain->client->display_name }}
                                </div>
                                @if($domain->registrar)
                                    <div class="text-sm text-gray-600">
                                        <strong>Registrar:</strong> {{ $registrars[$domain->registrar] ?? $domain->registrar }}
                                    </div>
                                @endif
                                @if($domain->dns_provider)
                                    <div class="text-sm text-gray-600">
                                        <strong>DNS:</strong> {{ $domain->dns_provider }}
                                    </div>
                                @endif
                                <div class="text-sm text-gray-600">
                                    <strong>Expires:</strong> 
                                    <span class="{{ $expiryStatus === 'expired' ? 'text-red-600' : ($expiryStatus === 'expiring_soon' ? 'text-yellow-600' : 'text-gray-900') }}">
                                        {{ $domain->expires_at ? $domain->expires_at->format('M d, Y') : 'No expiry set' }}
                                    </span>
                                </div>
                                @if($domain->days_until_expiry !== null)
                                    <div class="text-sm text-gray-600">
                                        <strong>Days remaining:</strong> 
                                        <span class="{{ $domain->days_until_expiry < 0 ? 'text-red-600' : ($domain->days_until_expiry <= 30 ? 'text-yellow-600' : 'text-green-600') }}">
                                            {{ abs($domain->days_until_expiry) }}{{ $domain->days_until_expiry < 0 ? ' (overdue)' : '' }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Usage Stats -->
                            @if($domain->dns_records_count || $domain->subdomains_count || $domain->email_forwards_count)
                                <div class="mb-4">
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div class="text-xs">
                                            <div class="font-medium text-gray-900">{{ $domain->dns_records_count ?? 0 }}</div>
                                            <div class="text-gray-500">DNS</div>
                                        </div>
                                        <div class="text-xs">
                                            <div class="font-medium text-gray-900">{{ $domain->subdomains_count ?? 0 }}</div>
                                            <div class="text-gray-500">Subdomains</div>
                                        </div>
                                        <div class="text-xs">
                                            <div class="font-medium text-gray-900">{{ $domain->email_forwards_count ?? 0 }}</div>
                                            <div class="text-gray-500">Email</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Security Features -->
                            <div class="mb-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($domain->privacy_protection)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Privacy
                                        </span>
                                    @endif
                                    @if($domain->lock_status)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Locked
                                        </span>
                                    @endif
                                    @if($domain->whois_guard)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            WHOIS Guard
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Description -->
                            @if($domain->description)
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $domain->description }}</p>
                            @endif

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                                <div class="flex space-x-2">
                                    <a href="{{ route('clients.domains.standalone.show', $domain) }}" 
                                       class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        View
                                    </a>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <a href="{{ route('clients.domains.standalone.edit', $domain) }}" 
                                       class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</a>
                                    <form method="POST" 
                                          action="{{ route('clients.domains.standalone.destroy', $domain) }}" 
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this domain? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900 text-sm ml-2">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $domains->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9V3" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No domains found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding a new domain.</p>
                    <div class="mt-6">
                        <a href="{{ route('clients.domains.standalone.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Domain
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection