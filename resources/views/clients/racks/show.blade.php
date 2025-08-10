@extends('layouts.app')

@section('title', 'Rack Details - ' . $rack->name)

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-3xl mr-3">🏢</span>
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $rack->name }}</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                {{ $rack->client->display_name }} - {{ $rack->location }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                    {{ $rack->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $rack->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $rack->status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $rack->status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ ucfirst($rack->status) }}
                        </span>
                        @if($rack->environmental_status !== 'normal')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        {{ $rack->environmental_status === 'critical' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                🌡️ {{ ucfirst($rack->environmental_status) }}
                            </span>
                        @endif
                        @if($rack->isWarrantyExpiring())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                ⚠️ Warranty Expiring
                            </span>
                        @endif
                        <div class="flex space-x-3">
                            <a href="{{ route('clients.racks.standalone.edit', $rack) }}" 
                               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </a>
                            <a href="{{ route('clients.racks.standalone.index') }}" 
                               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to Racks
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Basic Information</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Client</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $rack->client->display_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Location</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $rack->location }}</dd>
                            </div>
                            @if($rack->rack_number)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Rack Number</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $rack->rack_number }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $rack->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $rack->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $rack->status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $rack->status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ ucfirst($rack->status) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $rack->created_at->format('M d, Y g:i A') }}</dd>
                            </div>
                            @if($rack->accessed_at)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Last Accessed</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $rack->accessed_at->format('M d, Y g:i A') }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if($rack->description)
                            <div class="mt-6">
                                <dt class="text-sm font-medium text-gray-500">Description</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $rack->description }}</dd>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Physical Specifications -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Physical Specifications</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Height</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $rack->height_units }}U</dd>
                            </div>
                            @if($rack->width_inches)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Width</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $rack->width_inches }}"</dd>
                                </div>
                            @endif
                            @if($rack->depth_inches)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Depth</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $rack->depth_inches }}"</dd>
                                </div>
                            @endif
                            @if($rack->max_weight_lbs)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Max Weight</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ number_format($rack->max_weight_lbs) }} lbs</dd>
                                </div>
                            @endif
                            @if($rack->cooling_requirements)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Cooling</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $rack->cooling_requirements }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if($rack->network_connections)
                            <div class="mt-6">
                                <dt class="text-sm font-medium text-gray-500">Network Connections</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $rack->network_connections }}</dd>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Hardware Details -->
                @if($rack->manufacturer || $rack->model || $rack->serial_number || $rack->purchase_date || $rack->warranty_expiry)
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Hardware Details</h3>
                        </div>
                        <div class="px-4 py-5 sm:p-6">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                @if($rack->manufacturer)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Manufacturer</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $rack->manufacturer }}</dd>
                                    </div>
                                @endif
                                @if($rack->model)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Model</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $rack->model }}</dd>
                                    </div>
                                @endif
                                @if($rack->serial_number)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Serial Number</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $rack->serial_number }}</dd>
                                    </div>
                                @endif
                                @if($rack->purchase_date)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Purchase Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $rack->purchase_date->format('M d, Y') }}</dd>
                                    </div>
                                @endif
                                @if($rack->warranty_expiry)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Warranty Expiry</dt>
                                        <dd class="mt-1 text-sm text-gray-900 {{ $rack->isWarrantyExpiring() ? 'text-red-600' : '' }}">
                                            {{ $rack->warranty_expiry->format('M d, Y') }}
                                            @if($rack->warranty_expiry->isPast())
                                                <span class="text-red-600">(Expired)</span>
                                            @elseif($rack->isWarrantyExpiring())
                                                <span class="text-orange-600">(Expiring Soon)</span>
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                            </dl>

                            @if($rack->maintenance_schedule)
                                <div class="mt-6">
                                    <dt class="text-sm font-medium text-gray-500">Maintenance Schedule</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $rack->maintenance_schedule }}</dd>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Notes -->
                @if($rack->notes)
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Notes</h3>
                        </div>
                        <div class="px-4 py-5 sm:p-6">
                            <p class="text-sm text-gray-900">{{ $rack->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Power Usage -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Power Usage</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <!-- Power Utilization Circle -->
                        <div class="flex items-center justify-center mb-4">
                            <div class="relative inline-flex items-center justify-center w-24 h-24">
                                <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 36 36">
                                    <path class="text-gray-300" stroke="currentColor" stroke-width="3" fill="transparent"
                                          d="M18,2.0845 a 15.9155,15.9155 0 0,1 0,31.831 a 15.9155,15.9155 0 0,1 0,-31.831"/>
                                    <path class="{{ $rack->power_utilization < 70 ? 'text-green-500' : ($rack->power_utilization < 90 ? 'text-yellow-500' : 'text-red-500') }}" 
                                          stroke="currentColor" stroke-width="3" stroke-linecap="round" fill="transparent"
                                          stroke-dasharray="{{ $rack->power_utilization }}, 100"
                                          d="M18,2.0845 a 15.9155,15.9155 0 0,1 0,31.831 a 15.9155,15.9155 0 0,1 0,-31.831"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-lg font-medium text-gray-900">{{ $rack->power_utilization }}%</span>
                                </div>
                            </div>
                        </div>

                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Capacity</dt>
                                <dd class="text-sm text-gray-900">{{ number_format($rack->power_capacity_watts) }}W</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Used</dt>
                                <dd class="text-sm text-gray-900">{{ number_format($rack->power_used_watts) }}W</dd>
                            </div>
                            <div class="flex justify-between border-t pt-3">
                                <dt class="text-sm font-medium text-gray-500">Available</dt>
                                <dd class="text-sm font-semibold {{ $rack->available_power < 1000 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($rack->available_power) }}W
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Environmental Monitoring -->
                @if($rack->temperature_celsius || $rack->humidity_percent)
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Environment</h3>
                        </div>
                        <div class="px-4 py-5 sm:p-6">
                            <dl class="space-y-4">
                                @if($rack->temperature_celsius)
                                    <div>
                                        <div class="flex justify-between items-center mb-2">
                                            <dt class="text-sm font-medium text-gray-500">Temperature</dt>
                                            <dd class="text-lg font-semibold {{ ($rack->temperature_celsius < 18 || $rack->temperature_celsius > 24) ? 'text-red-600' : 'text-green-600' }}">
                                                {{ $rack->temperature_celsius }}°C
                                            </dd>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            @php
                                                $tempMin = 15;
                                                $tempMax = 30;
                                                $tempPercent = max(0, min(100, (($rack->temperature_celsius - $tempMin) / ($tempMax - $tempMin)) * 100));
                                                $tempColor = ($rack->temperature_celsius < 18 || $rack->temperature_celsius > 24) ? 'bg-red-500' : 'bg-green-500';
                                            @endphp
                                            <div class="h-2 rounded-full {{ $tempColor }}" style="width: {{ $tempPercent }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                                            <span>15°C</span>
                                            <span class="text-green-600">18-24°C Optimal</span>
                                            <span>30°C</span>
                                        </div>
                                    </div>
                                @endif

                                @if($rack->humidity_percent)
                                    <div>
                                        <div class="flex justify-between items-center mb-2">
                                            <dt class="text-sm font-medium text-gray-500">Humidity</dt>
                                            <dd class="text-lg font-semibold {{ ($rack->humidity_percent < 40 || $rack->humidity_percent > 60) ? 'text-red-600' : 'text-green-600' }}">
                                                {{ $rack->humidity_percent }}%
                                            </dd>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            @php
                                                $humidityColor = ($rack->humidity_percent < 40 || $rack->humidity_percent > 60) ? 'bg-red-500' : 'bg-green-500';
                                            @endphp
                                            <div class="h-2 rounded-full {{ $humidityColor }}" style="width: {{ $rack->humidity_percent }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                                            <span>0%</span>
                                            <span class="text-green-600">40-60% Optimal</span>
                                            <span>100%</span>
                                        </div>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                @endif

                <!-- Quick Stats -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Quick Stats</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Rack Units</dt>
                                <dd class="text-sm text-gray-900">{{ $rack->height_units }}U</dd>
                            </div>
                            @if($rack->rack_number)
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Rack #</dt>
                                    <dd class="text-sm text-gray-900">{{ $rack->rack_number }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                {{ $rack->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $rack->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $rack->status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $rack->status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ ucfirst($rack->status) }}
                                    </span>
                                </dd>
                            </div>
                            @if($rack->environmental_status !== 'normal')
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Environment</dt>
                                    <dd class="text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $rack->environmental_status === 'critical' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ ucfirst($rack->environmental_status) }}
                                        </span>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection