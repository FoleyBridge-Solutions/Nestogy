@extends('layouts.app')

@section('title', $client->name . ' - Locations')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>{{ $client->name }} - Locations</flux:heading>
                <flux:text>Manage locations for {{ $client->name }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button href="{{ route('clients.locations.export', [$client] + request()->query()) }}" 
                             variant="subtle"
                             icon="arrow-down-tray">
                    Export CSV
                </flux:button>
                <flux:button href="{{ route('clients.locations.create', $client) }}" 
                             variant="primary"
                             icon="plus">
                    Add Location
                </flux:button>
            </div>
        </div>
    </flux:card>

        <!-- Filters -->
        <flux:card class="mb-4">
            <form method="GET" action="{{ route('clients.locations.index', $client) }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <flux:input name="search" 
                               value="{{ request('search') }}"
                               placeholder="Search locations..."
                               icon="magnifying-glass" />

                    <flux:select name="state" placeholder="All States" value="{{ request('state') }}">
                        <flux:select.option value="">All States</flux:select.option>
                        @foreach($states as $state)
                            <flux:select.option value="{{ $state }}">{{ $state }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select name="country" placeholder="All Countries" value="{{ request('country') }}">
                        <flux:select.option value="">All Countries</flux:select.option>
                        @foreach($countries as $country)
                            <flux:select.option value="{{ $country }}">{{ $country }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex gap-2 items-end">
                        <flux:checkbox name="primary_only" 
                                      value="1"
                                      label="Primary only"
                                      :checked="request('primary_only')" />
                        <flux:button type="submit" variant="primary">
                            Filter
                        </flux:button>
                        @if(request()->hasAny(['search', 'state', 'country', 'primary_only']))
                            <flux:button href="{{ route('clients.locations.index', $client) }}" 
                                        variant="ghost">
                                Clear
                            </flux:button>
                        @endif
                    </div>
                </div>
            </form>
        </flux:card>

        <!-- Locations Table -->
        <flux:card>
            
            @if($locations->count() > 0)
                <flux:table :paginate="$locations">
                    <flux:table.columns>
                        <flux:table.column>Location</flux:table.column>
                        <flux:table.column>Client</flux:table.column>
                        <flux:table.column>Address</flux:table.column>
                        <flux:table.column>Contact</flux:table.column>
                        <flux:table.column>Phone</flux:table.column>
                        <flux:table.column class="w-1"></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($locations as $location)
                            <flux:table.row :key="$location->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                            <flux:icon name="map-pin" variant="mini" class="text-green-600" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <flux:text variant="strong">{{ $location->name }}</flux:text>
                                                @if($location->primary)
                                                    <flux:badge size="sm" color="blue">Primary</flux:badge>
                                                @endif
                                            </div>
                                            @if($location->description)
                                                <flux:text size="sm" class="text-gray-500">{{ $location->description }}</flux:text>
                                            @endif
                                        </div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $location->client->display_name }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($location->address)
                                        <flux:text>{{ $location->address }}</flux:text>
                                    @endif
                                    <flux:text size="sm" class="text-gray-500">
                                        {{ $location->city }}, {{ $location->state }} {{ $location->zip }}
                                        @if($location->country !== 'US')
                                            <br>{{ $location->country }}
                                        @endif
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($location->contact)
                                        <flux:text>{{ $location->contact->name }}</flux:text>
                                        @if($location->contact->title)
                                            <flux:text size="sm" class="text-gray-500">{{ $location->contact->title }}</flux:text>
                                        @endif
                                    @else
                                        <flux:text class="text-gray-500">-</flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $location->phone ?? '-' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button href="{{ route('clients.locations.show', [$client, $location]) }}" 
                                                    variant="ghost" 
                                                    size="sm"
                                                    icon="eye"
                                                    inset="top bottom" />
                                        <flux:button href="{{ route('clients.locations.edit', [$client, $location]) }}" 
                                                    variant="ghost" 
                                                    size="sm"
                                                    icon="pencil"
                                                    inset="top bottom" />
                                        <form method="POST" 
                                              action="{{ route('clients.locations.destroy', [$client, $location]) }}" 
                                              class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete this location?')">
                                            @csrf
                                            @method('DELETE')
                                            <flux:button type="submit" 
                                                        variant="ghost" 
                                                        size="sm"
                                                        icon="trash"
                                                        color="red"
                                                        inset="top bottom" />
                                        </form>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <div class="text-center py-12">
                    <flux:icon name="map-pin" variant="outline" class="mx-auto h-12 w-12 text-gray-400" />
                    <flux:heading size="lg" class="mt-2">No locations found</flux:heading>
                    <flux:text class="mt-1">Get started by adding a new location.</flux:text>
                    <div class="mt-6">
                        <flux:button href="{{ route('clients.locations.create', $client) }}" 
                                    variant="primary"
                                    icon="plus">
                            Add Location
                        </flux:button>
                    </div>
                </div>
            @endif
        </flux:card>
</div>
@endsection
