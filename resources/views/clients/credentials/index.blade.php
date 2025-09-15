@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Credentials
    </flux:heading>

    <div class="flex justify-between items-center mb-6">
        <flux:subheading>
            Manage access credentials and login information for {{ $client->name }}
        </flux:subheading>

        <div class="flex gap-3">
            <flux:button variant="outline" href="{{ route('clients.credentials.export', $client) }}">
                Export CSV
            </flux:button>
            <flux:button href="{{ route('clients.credentials.create', $client) }}">
                Add Credential
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <form method="GET" action="{{ route('clients.credentials.index', $client) }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Search -->
                <flux:field>
                    <flux:input
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, service, username..."
                        label="Search" />
                </flux:field>

                <!-- Credential Type -->
                <flux:field>
                    <flux:select name="credential_type" label="Type">
                        <flux:select.option value="">All Types</flux:select.option>
                        @foreach($credentialTypes as $key => $label)
                            <flux:select.option value="{{ $key }}" {{ request('credential_type') === $key ? 'selected' : '' }}>{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Environment -->
                <flux:field>
                    <flux:select name="environment" label="Environment">
                        <flux:select.option value="">All Environments</flux:select.option>
                        @foreach($environments as $key => $label)
                            <flux:select.option value="{{ $key }}" {{ request('environment') === $key ? 'selected' : '' }}>{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Actions -->
                <div class="flex items-end gap-3">
                    <flux:button type="submit" variant="primary">
                        Filter
                    </flux:button>
                    <flux:button variant="outline" href="{{ route('clients.credentials.index', $client) }}">
                        Clear
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:card>

    <!-- Credentials Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Service</flux:table.column>
                <flux:table.column>Username</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Environment</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($credentials as $credential)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="font-medium">{{ $credential->name }}</div>
                        @if($credential->service_name)
                            <div class="text-sm text-gray-500">{{ $credential->service_name }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $credential->username ?: '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="gray">
                            {{ $credentialTypes[$credential->credential_type] ?? $credential->credential_type }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue">
                            {{ $environments[$credential->environment] ?? $credential->environment }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $credential->is_active ? 'green' : 'gray' }}">
                            {{ $credential->status_label }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button variant="outline" size="sm" href="{{ route('clients.credentials.show', [$client, $credential]) }}">
                                View
                            </flux:button>
                            <flux:button variant="outline" size="sm" href="{{ route('clients.credentials.edit', [$client, $credential]) }}">
                                Edit
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-8">
                        <div class="text-gray-500">No credentials found</div>
                        <flux:button href="{{ route('clients.credentials.create', $client) }}" class="mt-6">
                            Add Credential
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($credentials->hasPages())
            <div class="mt-6">
                {{ $credentials->links() }}
            </div>
        @endif
    </flux:card>
</div>
@endsection
