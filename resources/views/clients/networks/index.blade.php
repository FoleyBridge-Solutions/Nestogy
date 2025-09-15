@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Networks
    </flux:heading>

    <div class="flex justify-between items-center mb-6">
        <flux:subheading>
            Manage network configurations and documentation for {{ $client->name }}
        </flux:subheading>

        <div class="flex gap-3">
            <flux:button variant="outline" href="{{ route('clients.networks.export', $client) }}">
                Export CSV
            </flux:button>
            <flux:button href="{{ route('clients.networks.create', $client) }}">
                Add Network
            </flux:button>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Network Details</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>IP Range</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($networks as $network)
            <flux:table.row>
                <flux:table.cell>
                    <div class="font-medium">{{ $network->name }}</div>
                    @if($network->location)
                        <div class="text-sm text-gray-500">{{ $network->location }}</div>
                    @endif
                    @if($network->provider)
                        <div class="text-sm text-gray-500">{{ $network->provider }}</div>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge color="blue">{{ ucfirst($network->network_type) }}</flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    <div class="font-mono text-sm">{{ $network->ip_range }}</div>
                    @if($network->subnet_mask)
                        <div class="text-sm text-gray-500">/{{ $network->subnet_mask }}</div>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge color="{{ $network->is_active ? 'green' : 'gray' }}">
                        {{ $network->is_active ? 'Active' : 'Inactive' }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:button variant="ghost" size="sm" href="{{ route('clients.networks.show', [$client, $network]) }}">
                        View
                    </flux:button>
                    <flux:button variant="ghost" size="sm" href="{{ route('clients.networks.edit', [$client, $network]) }}">
                        Edit
                    </flux:button>
                </flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{ $networks->links() }}
</div>
@endsection
