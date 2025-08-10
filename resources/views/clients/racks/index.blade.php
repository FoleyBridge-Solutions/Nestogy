@extends('layouts.app')

@section('title', 'Client Racks')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Client Racks</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage server racks and equipment placement across your clients.</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('clients.racks.standalone.export', request()->query()) }}" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export CSV
                        </a>
                        <a href="{{ route('clients.racks.standalone.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Rack
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <form method="GET" action="{{ route('clients.racks.standalone.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Name, location, serial..."
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

                        <!-- Location -->
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                            <select name="location" 
                                    id="location" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Locations</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location }}" {{ request('location') === $location ? 'selected' : '' }}>
                                        {{ $location }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Special Filters -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Alerts</label>
                            <div class="space-y-1">
                                <div class="flex items-center h-5">
                                    <input id="environmental_warning" 
                                           name="environmental_warning" 
                                           type="checkbox" 
                                           value="1"
                                           {{ request('environmental_warning') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    <label for="environmental_warning" class="ml-2 text-xs text-gray-700">Environmental warnings</label>
                                </div>
                                <div class="flex items-center h-5">
                                    <input id="warranty_expiring" 
                                           name="warranty_expiring" 
                                           type="checkbox" 
                                           value="1"
                                           {{ request('warranty_expiring') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    <label for="warranty_expiring" class="ml-2 text-xs text-gray-700">Warranty expiring</label>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-end space-x-3">
                            <div class="space-y-2">
                                <div class="flex space-x-2">
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Filter
                                    </button>
                                    <a href="{{ route('clients.racks.standalone.index') }}" 
                                       class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Racks Grid -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Server Racks 
                    <span class="text-sm text-gray-500">({{ $racks->total() }} total)</span>
                </h3>
            </div>
            
            @if($racks->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 p-6">
                    @foreach($racks as $rack)
                        @php
                            $environmentalStatus = $rack->environmental_status;
                            $isWarrantyExpiring = $rack->isWarrantyExpiring();
                        @endphp
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200 
                                    {{ $environmentalStatus === 'critical' ? 'bg-red-50 border-red-200' : '' }}
                                    {{ $environmentalStatus === 'warning' ? 'bg-yellow-50 border-yellow-200' : '' }}">
                            <!-- Rack Header -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center">
                                    <span class="text-2xl mr-2">🏢</span>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 truncate">{{ $rack->name }}</h4>
                                        <p class="text-xs text-gray-500">{{ $rack->location }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col space-y-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                                {{ $rack->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $rack->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $rack->status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $rack->status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ $statuses[$rack->status] }}
                                    </span>
                                    @if($environmentalStatus !== 'normal')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                    {{ $environmentalStatus === 'critical' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            🌡️ {{ ucfirst($environmentalStatus) }}
                                        </span>
                                    @endif
                                    @if($isWarrantyExpiring)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            ⚠️ Warranty
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Rack Info -->
                            <div class="space-y-2 mb-4">
                                <div class="text-sm text-gray-600">
                                    <strong>Client:</strong> {{ $rack->client->display_name }}
                                </div>
                                @if($rack->rack_number)
                                    <div class="text-sm text-gray-600">
                                        <strong>Rack #:</strong> {{ $rack->rack_number }}
                                    </div>
                                @endif
                                <div class="text-sm text-gray-600">
                                    <strong>Height:</strong> {{ $rack->height_units }}U
                                </div>
                                <div class="text-sm text-gray-600">
                                    <strong>Power:</strong> {{ number_format($rack->power_used_watts) }}W / {{ number_format($rack->power_capacity_watts) }}W 
                                    ({{ $rack->power_utilization }}% used)
                                </div>
                                @if($rack->temperature_celsius || $rack->humidity_percent)
                                    <div class="text-sm text-gray-600">
                                        <strong>Environment:</strong>
                                        @if($rack->temperature_celsius)
                                            {{ $rack->temperature_celsius }}°C
                                        @endif
                                        @if($rack->temperature_celsius && $rack->humidity_percent) | @endif
                                        @if($rack->humidity_percent)
                                            {{ $rack->humidity_percent }}% RH
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Power Utilization Bar -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                    <span>Power Utilization</span>
                                    <span>{{ $rack->power_utilization }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full 
                                               {{ $rack->power_utilization < 70 ? 'bg-green-500' : '' }}
                                               {{ $rack->power_utilization >= 70 && $rack->power_utilization < 90 ? 'bg-yellow-500' : '' }}
                                               {{ $rack->power_utilization >= 90 ? 'bg-red-500' : '' }}" 
                                         style="width: {{ min($rack->power_utilization, 100) }}%"></div>
                                </div>
                            </div>

                            <!-- Description -->
                            @if($rack->description)
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $rack->description }}</p>
                            @endif

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                                <div class="flex space-x-2">
                                    <a href="{{ route('clients.racks.standalone.show', $rack) }}" 
                                       class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        View
                                    </a>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <a href="{{ route('clients.racks.standalone.edit', $rack) }}" 
                                       class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</a>
                                    <form method="POST" 
                                          action="{{ route('clients.racks.standalone.destroy', $rack) }}" 
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this rack? This action cannot be undone.')">
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
                    {{ $racks->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No server racks found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding a new server rack.</p>
                    <div class="mt-6">
                        <a href="{{ route('clients.racks.standalone.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Rack
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection