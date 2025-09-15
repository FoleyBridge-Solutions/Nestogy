@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Licenses
    </flux:heading>

    <div class="flex justify-between items-center mb-6">
        <flux:subheading>
            Manage software and hardware licenses for {{ $client->name }}
        </flux:subheading>

        <div class="flex gap-3">
            <flux:button variant="outline" href="{{ route('clients.licenses.export', $client) }}">
                Export CSV
            </flux:button>
            <flux:button href="{{ route('clients.licenses.create', $client) }}">
                Add License
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <form method="GET" action="{{ route('clients.licenses.index', $client) }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Search -->
                <flux:field>
                    <flux:input
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, vendor, key..."
                        label="Search" />
                </flux:field>

                <!-- License Type -->
                <flux:field>
                    <flux:select name="license_type" label="License Type">
                        <flux:select.option value="">All Types</flux:select.option>
                        @foreach($licenseTypes as $key => $label)
                            <flux:select.option value="{{ $key }}" {{ request('license_type') === $key ? 'selected' : '' }}>{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Status -->
                <flux:field>
                    <flux:select name="status" label="Status">
                        <flux:select.option value="">All Statuses</flux:select.option>
                        <flux:select.option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</flux:select.option>
                        <flux:select.option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</flux:select.option>
                        <flux:select.option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</flux:select.option>
                        <flux:select.option value="expiring_soon" {{ request('status') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</flux:select.option>
                    </flux:select>
                </flux:field>

                <!-- Actions -->
                <div class="flex items-end gap-3">
                    <flux:button type="submit" variant="primary">
                        Filter
                    </flux:button>
                    <flux:button variant="outline" href="{{ route('clients.licenses.index', $client) }}">
                        Clear
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:card>

    <!-- Licenses Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>License</flux:table.column>
                <flux:table.column>Vendor</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Expiry</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($licenses as $license)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="font-medium">{{ $license->name }}</div>
                        @if($license->version)
                            <div class="text-sm text-gray-500">v{{ $license->version }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $license->vendor ?? '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="gray">
                            {{ $licenseTypes[$license->license_type] ?? $license->license_type }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $license->is_active ? 'green' : 'gray' }}">
                            {{ $license->status_label }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($license->expiry_date)
                            <div class="{{ $license->isExpiringSoon() ? 'text-orange-600 font-medium' : ($license->isExpired() ? 'text-red-600 font-medium' : '') }}">
                                {{ $license->expiry_date->format('M j, Y') }}
                            </div>
                            @if($license->days_until_expiry !== null)
                                <div class="text-xs text-gray-500">
                                    @if($license->days_until_expiry > 0)
                                        {{ $license->days_until_expiry }} days left
                                    @elseif($license->days_until_expiry == 0)
                                        Expires today
                                    @else
                                        Expired {{ abs($license->days_until_expiry) }} days ago
                                    @endif
                                </div>
                            @endif
                        @else
                            <span class="text-gray-400">Never</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button variant="outline" size="sm" href="{{ route('clients.licenses.show', [$client, $license]) }}">
                                View
                            </flux:button>
                            <flux:button variant="outline" size="sm" href="{{ route('clients.licenses.edit', [$client, $license]) }}">
                                Edit
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-8">
                        <div class="text-gray-500">No licenses found</div>
                        <flux:button href="{{ route('clients.licenses.create', $client) }}" class="mt-6">
                            Add License
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($licenses->hasPages())
            <div class="mt-6">
                {{ $licenses->links() }}
            </div>
        @endif
    </flux:card>
</div>
@endsection
