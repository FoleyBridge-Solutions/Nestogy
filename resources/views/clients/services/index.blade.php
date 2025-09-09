@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Services
    </flux:heading>

    <div class="flex justify-between items-center mb-6">
        <flux:subheading>
            Manage service catalog and maintenance schedules for {{ $client->name }}
        </flux:subheading>

        <div class="flex gap-3">
            <flux:button variant="outline" href="{{ route('clients.services.export', $client) }}">
                Export CSV
            </flux:button>
            <flux:button href="{{ route('clients.services.create', $client) }}">
                Add Service
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <form method="GET" action="{{ route('clients.services.index', $client) }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Search -->
                <flux:field>
                    <flux:input
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, description, type..."
                        label="Search" />
                </flux:field>

                <!-- Service Type -->
                <flux:field>
                    <flux:select name="service_type" label="Type">
                        <flux:select.option value="">All Types</flux:select.option>
                        @foreach($serviceTypes as $key => $label)
                            <flux:select.option value="{{ $key }}" {{ request('service_type') === $key ? 'selected' : '' }}>{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Category -->
                <flux:field>
                    <flux:select name="category" label="Category">
                        <flux:select.option value="">All Categories</flux:select.option>
                        @foreach($serviceCategories as $key => $label)
                            <flux:select.option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Status -->
                <flux:field>
                    <flux:select name="status" label="Status">
                        <flux:select.option value="">All Statuses</flux:select.option>
                        @foreach($serviceStatuses as $key => $label)
                            <flux:select.option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <div class="flex items-end gap-3">
                <flux:button type="submit" variant="primary">
                    Filter
                </flux:button>
                <flux:button variant="outline" href="{{ route('clients.services.index', $client) }}">
                    Clear
                </flux:button>
            </div>
        </form>
    </flux:card>

    <!-- Services Table -->
    <flux:card>
        <flux:table>
            <flux:columns>
                <flux:column>Service</flux:column>
                <flux:column>Type</flux:column>
                <flux:column>Technician</flux:column>
                <flux:column>Status</flux:column>
                <flux:column>Next Review</flux:column>
                <flux:column></flux:column>
            </flux:columns>

            <flux:rows>
                @forelse($services as $service)
                <flux:flex flex-wrap>
                    <flux:cell>
                        <div class="font-medium">{{ $service->name }}</div>
                        @if($service->description)
                            <div class="text-sm text-gray-500">{{ Str::limit($service->description, 50) }}</div>
                        @endif
                    </flux:cell>
                    <flux:cell>
                        <flux:badge color="blue">
                            {{ $serviceTypes[$service->service_type] ?? $service->service_type }}
                        </flux:badge>
                    </flux:cell>
                    <flux:cell>
                        @if($service->technician)
                            {{ $service->technician->name }}
                            @if($service->backupTechnician)
                                <div class="text-xs text-gray-500">Backup: {{ $service->backupTechnician->name }}</div>
                            @endif
                        @else
                            <span class="text-gray-400">Unassigned</span>
                        @endif
                    </flux:cell>
                    <flux:cell>
                        <flux:badge color="{{ $service->status === 'active' ? 'green' : ($service->status === 'inactive' ? 'gray' : 'yellow') }}">
                            {{ $serviceStatuses[$service->status] ?? $service->status }}
                        </flux:badge>
                    </flux:cell>
                    <flux:cell>
                        @if($service->next_review_date)
                            <div class="{{ $service->isReviewOverdue() ? 'text-red-600 font-medium' : ($service->isReviewDueSoon() ? 'text-orange-600 font-medium' : '') }}">
                                {{ $service->next_review_date->format('M j, Y') }}
                            </div>
                            @if($service->days_until_review !== null)
                                <div class="text-xs text-gray-500">
                                    @if($service->days_until_review > 0)
                                        {{ $service->days_until_review }} days left
                                    @elseif($service->days_until_review == 0)
                                        Due today
                                    @else
                                        Overdue {{ abs($service->days_until_review) }} days
                                    @endif
                                </div>
                            @endif
                        @else
                            <span class="text-gray-400">Not scheduled</span>
                        @endif
                    </flux:cell>
                    <flux:cell>
                        <div class="flex gap-2">
                            <flux:button variant="outline" size="sm" href="{{ route('clients.services.show', [$client, $service]) }}">
                                View
                            </flux:button>
                            <flux:button variant="outline" size="sm" href="{{ route('clients.services.edit', [$client, $service]) }}">
                                Edit
                            </flux:button>
                        </div>
                    </flux:cell>
                </flux:flex flex-wrap>
                @empty
                <flux:flex flex-wrap>
                    <flux:cell colspan="6" class="text-center py-8">
                        <div class="text-gray-500">No services found</div>
                        <flux:button href="{{ route('clients.services.create', $client) }}" class="mt-6">
                            Add Service
                        </flux:button>
                    </flux:cell>
                </flux:flex flex-wrap>
                @endforelse
            </flux:rows>
        </flux:table>

        @if($services->hasPages())
            <div class="mt-6">
                {{ $services->links() }}
            </div>
        @endif
    </flux:card>
</div>
@endsection
