@extends('layouts.app')

@section('title', 'Add Client Domain')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Add Client Domain</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Register a new domain for a client.</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('clients.domains.standalone.index') }}" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Domains
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('clients.domains.standalone.store') }}" class="space-y-8 divide-y divide-gray-200">
                @csrf
                
                <div class="space-y-8 divide-y divide-gray-200 p-6">
                    <!-- Basic Information -->
                    <div>
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Basic Information</h3>
                            <p class="mt-1 text-sm text-gray-500">Domain details and client assignment.</p>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <!-- Client -->
                            <div class="sm:col-span-3">
                                <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                                <select id="client_id" 
                                        name="client_id" 
                                        required
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('client_id') border-red-300 @enderror">
                                    <option value="">Select a client</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ (old('client_id', $selectedClientId) == $client->id) ? 'selected' : '' }}>
                                            {{ $client->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Domain Name -->
                            <div class="sm:col-span-3">
                                <label for="name" class="block text-sm font-medium text-gray-700">Domain Name *</label>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       value="{{ old('name') }}"
                                       required
                                       placeholder="e.g., My Company Domain"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Domain -->
                            <div class="sm:col-span-3">
                                <label for="domain_name" class="block text-sm font-medium text-gray-700">Domain *</label>
                                <input type="text" 
                                       name="domain_name" 
                                       id="domain_name" 
                                       value="{{ old('domain_name') }}"
                                       required
                                       placeholder="example"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('domain_name') border-red-300 @enderror">
                                @error('domain_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- TLD -->
                            <div class="sm:col-span-3">
                                <label for="tld" class="block text-sm font-medium text-gray-700">TLD *</label>
                                <select id="tld" 
                                        name="tld" 
                                        required
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('tld') border-red-300 @enderror">
                                    <option value="">Select TLD</option>
                                    @foreach($commonTlds as $key => $label)
                                        <option value="{{ $key }}" {{ old('tld', 'com') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tld')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="sm:col-span-3">
                                <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                                <select id="status" 
                                        name="status" 
                                        required
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('status') border-red-300 @enderror">
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}" {{ old('status', 'active') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="sm:col-span-6">
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea id="description" 
                                          name="description" 
                                          rows="3" 
                                          class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Registration Details -->
                    <div class="pt-8">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Registration Details</h3>
                            <p class="mt-1 text-sm text-gray-500">Domain registrar and renewal information.</p>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <!-- Registrar -->
                            <div class="sm:col-span-3">
                                <label for="registrar" class="block text-sm font-medium text-gray-700">Registrar</label>
                                <select id="registrar" 
                                        name="registrar" 
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('registrar') border-red-300 @enderror">
                                    <option value="">Select registrar</option>
                                    @foreach($registrars as $key => $label)
                                        <option value="{{ $key }}" {{ old('registrar') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('registrar')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Registrar Account -->
                            <div class="sm:col-span-3">
                                <label for="registrar_account" class="block text-sm font-medium text-gray-700">Registrar Account</label>
                                <input type="text" 
                                       name="registrar_account" 
                                       id="registrar_account" 
                                       value="{{ old('registrar_account') }}"
                                       placeholder="Account ID or email"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('registrar_account') border-red-300 @enderror">
                                @error('registrar_account')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Registered Date -->
                            <div class="sm:col-span-3">
                                <label for="registered_at" class="block text-sm font-medium text-gray-700">Registration Date</label>
                                <input type="date" 
                                       name="registered_at" 
                                       id="registered_at" 
                                       value="{{ old('registered_at') }}"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('registered_at') border-red-300 @enderror">
                                @error('registered_at')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Expiry Date -->
                            <div class="sm:col-span-3">
                                <label for="expires_at" class="block text-sm font-medium text-gray-700">Expiry Date *</label>
                                <input type="date" 
                                       name="expires_at" 
                                       id="expires_at" 
                                       value="{{ old('expires_at') }}"
                                       required
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('expires_at') border-red-300 @enderror">
                                @error('expires_at')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Auto Renewal -->
                            <div class="sm:col-span-6 flex items-center">
                                <div class="flex items-center h-5">
                                    <input id="auto_renewal" 
                                           name="auto_renewal" 
                                           type="checkbox" 
                                           value="1"
                                           {{ old('auto_renewal') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    <label for="auto_renewal" class="ml-2 text-sm font-medium text-gray-700">Enable Auto Renewal</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DNS Configuration -->
                    <div class="pt-8">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">DNS Configuration</h3>
                            <p class="mt-1 text-sm text-gray-500">DNS provider and nameserver settings.</p>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <!-- DNS Provider -->
                            <div class="sm:col-span-3">
                                <label for="dns_provider" class="block text-sm font-medium text-gray-700">DNS Provider</label>
                                <select id="dns_provider" 
                                        name="dns_provider" 
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('dns_provider') border-red-300 @enderror">
                                    <option value="">Select DNS provider</option>
                                    @foreach($dnsProviders as $key => $label)
                                        <option value="{{ $key }}" {{ old('dns_provider') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('dns_provider')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- DNS Account -->
                            <div class="sm:col-span-3">
                                <label for="dns_account" class="block text-sm font-medium text-gray-700">DNS Account</label>
                                <input type="text" 
                                       name="dns_account" 
                                       id="dns_account" 
                                       value="{{ old('dns_account') }}"
                                       placeholder="DNS account or zone ID"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('dns_account') border-red-300 @enderror">
                                @error('dns_account')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Nameservers -->
                            <div class="sm:col-span-6">
                                <label for="nameservers" class="block text-sm font-medium text-gray-700">Nameservers</label>
                                <textarea id="nameservers" 
                                          name="nameservers" 
                                          rows="2" 
                                          placeholder="ns1.example.com, ns2.example.com (comma-separated)"
                                          class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('nameservers') border-red-300 @enderror">{{ old('nameservers') }}</textarea>
                                @error('nameservers')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Security & Settings -->
                    <div class="pt-8">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Security & Settings</h3>
                            <p class="mt-1 text-sm text-gray-500">Domain security and protection settings.</p>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <!-- Security Options -->
                            <div class="sm:col-span-6">
                                <fieldset>
                                    <legend class="text-base font-medium text-gray-900">Security Features</legend>
                                    <div class="mt-4 space-y-4">
                                        <div class="flex items-center">
                                            <input id="privacy_protection" 
                                                   name="privacy_protection" 
                                                   type="checkbox" 
                                                   value="1"
                                                   {{ old('privacy_protection') ? 'checked' : '' }}
                                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                            <label for="privacy_protection" class="ml-3 text-sm font-medium text-gray-700">
                                                Privacy Protection
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="lock_status" 
                                                   name="lock_status" 
                                                   type="checkbox" 
                                                   value="1"
                                                   {{ old('lock_status', true) ? 'checked' : '' }}
                                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                            <label for="lock_status" class="ml-3 text-sm font-medium text-gray-700">
                                                Domain Lock
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="whois_guard" 
                                                   name="whois_guard" 
                                                   type="checkbox" 
                                                   value="1"
                                                   {{ old('whois_guard') ? 'checked' : '' }}
                                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                            <label for="whois_guard" class="ml-3 text-sm font-medium text-gray-700">
                                                WHOIS Guard
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="transfer_lock" 
                                                   name="transfer_lock" 
                                                   type="checkbox" 
                                                   value="1"
                                                   {{ old('transfer_lock', true) ? 'checked' : '' }}
                                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                            <label for="transfer_lock" class="ml-3 text-sm font-medium text-gray-700">
                                                Transfer Lock
                                            </label>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>

                            <!-- Usage Stats -->
                            <div class="sm:col-span-2">
                                <label for="dns_records_count" class="block text-sm font-medium text-gray-700">DNS Records</label>
                                <input type="number" 
                                       name="dns_records_count" 
                                       id="dns_records_count" 
                                       value="{{ old('dns_records_count', '0') }}"
                                       min="0"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('dns_records_count') border-red-300 @enderror">
                                @error('dns_records_count')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="subdomains_count" class="block text-sm font-medium text-gray-700">Subdomains</label>
                                <input type="number" 
                                       name="subdomains_count" 
                                       id="subdomains_count" 
                                       value="{{ old('subdomains_count', '0') }}"
                                       min="0"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('subdomains_count') border-red-300 @enderror">
                                @error('subdomains_count')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="email_forwards_count" class="block text-sm font-medium text-gray-700">Email Forwards</label>
                                <input type="number" 
                                       name="email_forwards_count" 
                                       id="email_forwards_count" 
                                       value="{{ old('email_forwards_count', '0') }}"
                                       min="0"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('email_forwards_count') border-red-300 @enderror">
                                @error('email_forwards_count')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Costs -->
                            <div class="sm:col-span-3">
                                <label for="purchase_cost" class="block text-sm font-medium text-gray-700">Purchase Cost</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" 
                                           name="purchase_cost" 
                                           id="purchase_cost" 
                                           value="{{ old('purchase_cost') }}"
                                           min="0"
                                           step="0.01"
                                           placeholder="0.00"
                                           class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md @error('purchase_cost') border-red-300 @enderror">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">USD</span>
                                    </div>
                                </div>
                                @error('purchase_cost')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="renewal_cost" class="block text-sm font-medium text-gray-700">Annual Renewal Cost</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" 
                                           name="renewal_cost" 
                                           id="renewal_cost" 
                                           value="{{ old('renewal_cost') }}"
                                           min="0"
                                           step="0.01"
                                           placeholder="0.00"
                                           class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md @error('renewal_cost') border-red-300 @enderror">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">USD</span>
                                    </div>
                                </div>
                                @error('renewal_cost')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div class="sm:col-span-6">
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea id="notes" 
                                          name="notes" 
                                          rows="3" 
                                          class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('notes') border-red-300 @enderror">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="pt-5">
                    <div class="flex justify-end space-x-3 px-6 pb-6">
                        <a href="{{ route('clients.domains.standalone.index') }}" 
                           class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Create Domain
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection