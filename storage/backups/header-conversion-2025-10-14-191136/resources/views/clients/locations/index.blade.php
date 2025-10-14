@extends('layouts.app')

@section('title', $client->name . ' - Locations')

@section('content')
<div class="container-fluid h-full flex flex-col">
    <!-- Compact Header with Filters -->
    <flux:card class="mb-3">
        <div class="flex items-center justify-between mb-3">
            <div>
                <flux:heading>{{ $client->name }} - Locations</flux:heading>
                <flux:text size="sm">{{ $locations->total() }} total locations</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" size="sm" href="{{ route('clients.index') }}">
                    Back to Dashboard
                </flux:button>
                <flux:button 
                    variant="subtle" 
                    size="sm"
                    icon="arrow-down-tray"
                    href="{{ route('clients.locations.export', [$client] + request()->query()) }}"
                >
                    Export
                </flux:button>
                <flux:button 
                    variant="primary" 
                    size="sm"
                    icon="plus"
                    href="{{ route('clients.locations.create', $client) }}"
                >
                    Add Location
                </flux:button>
            </div>
        </div>
        
        <!-- Inline Filters -->
        <form method="GET" action="{{ route('clients.locations.index', $client) }}">
            <div class="flex gap-2">
                <flux:input 
                    name="search" 
                    placeholder="Search locations..." 
                    icon="magnifying-glass"
                    size="sm"
                    class="flex-1 max-w-xs"
                    value="{{ request('search') }}"
                />
                
                <flux:select name="state" placeholder="All States" size="sm" class="w-32" value="{{ request('state') }}">
                    <flux:select.option value="">All States</flux:select.option>
                    @foreach($states as $state)
                        <flux:select.option value="{{ $state }}">{{ $state }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:select name="country" placeholder="All Countries" size="sm" class="w-32" value="{{ request('country') }}">
                    <flux:select.option value="">All Countries</flux:select.option>
                    @foreach($countries as $country)
                        <flux:select.option value="{{ $country }}">{{ $country }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:checkbox name="primary_only" 
                              value="1"
                              label="Primary only"
                              :checked="request('primary_only')" />
                
                <flux:button type="submit" variant="primary" size="sm">
                    Apply
                </flux:button>
                @if(request()->hasAny(['search', 'state', 'country', 'primary_only']))
                    <flux:button 
                        variant="ghost" 
                        size="sm"
                        href="{{ route('clients.locations.index', $client) }}"
                    >
                        Clear
                    </flux:button>
                @endif
            </div>
        </form>
    </flux:card>

    <!-- Locations Table -->
    <flux:card class="flex-1">
        @if($locations->count() > 0)
            <div class="overflow-x-auto h-full">
                <flux:table class="text-base">
                    <flux:table.columns>
                        <flux:table.column class="w-64">Location</flux:table.column>
                        <flux:table.column class="w-48">Address</flux:table.column>
                        <flux:table.column class="w-48">Contact</flux:table.column>
                        <flux:table.column class="w-32">Phone</flux:table.column>
                        <flux:table.column class="w-24">Status</flux:table.column>
                        <flux:table.column class="w-16"></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($locations as $location)
                            <flux:table.row class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <!-- Location Name & Description -->
                                <flux:table.cell class="py-2">
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" class="flex-shrink-0 bg-green-100">
                                            <flux:icon.map-pin class="text-green-600 w-3 h-3" />
                                        </flux:avatar>
                                        <div class="min-w-0">
                                            <div class="font-medium truncate">{{ $location->name }}</div>
                                            @if($location->description)
                                                <div class="text-sm text-zinc-500 truncate">{{ $location->description }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </flux:table.cell>
                                
                                <!-- Address -->
                                <flux:table.cell class="py-2">
                                    <flux:tooltip content="{{ $location->address ? $location->address . ', ' : '' }}{{ $location->city }}, {{ $location->state }} {{ $location->zip }}{{ $location->country !== 'US' ? ', ' . $location->country : '' }}">
                                        <div>
                                            @if($location->address)
                                                <div class="text-sm truncate">{{ $location->address }}</div>
                                            @endif
                                            <div class="text-sm text-zinc-500 truncate">
                                                {{ $location->city }}, {{ $location->state }} {{ $location->zip }}
                                                @if($location->country !== 'US')
                                                    <br>{{ $location->country }}
                                                @endif
                                            </div>
                                        </div>
                                    </flux:tooltip>
                                </flux:table.cell>
                                
                                <!-- Contact -->
                                <flux:table.cell class="py-2">
                                    @if($location->contact)
                                        <flux:tooltip content="{{ $location->contact->name }}{{ $location->contact->title ? ' - ' . $location->contact->title : '' }}">
                                            <div>
                                                <div class="text-sm font-medium truncate">{{ $location->contact->name }}</div>
                                                @if($location->contact->title)
                                                    <div class="text-sm text-zinc-500 truncate">{{ $location->contact->title }}</div>
                                                @endif
                                            </div>
                                        </flux:tooltip>
                                    @else
                                        <span class="text-zinc-400 text-sm">-</span>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Phone -->
                                <flux:table.cell class="py-2">
                                    @if($location->phone)
                                        <flux:tooltip content="Call {{ $location->phone }}">
                                            <div class="flex items-center gap-1">
                                                <flux:icon.phone variant="micro" class="text-zinc-400 w-3 h-3 flex-shrink-0" />
                                                <span class="text-sm">{{ $location->phone }}</span>
                                            </div>
                                        </flux:tooltip>
                                    @else
                                        <span class="text-zinc-400 text-sm">-</span>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Status -->
                                <flux:table.cell class="py-2">
                                    @if($location->primary)
                                        <flux:tooltip content="Primary Location">
                                            <flux:badge color="blue" size="xs">Primary</flux:badge>
                                        </flux:tooltip>
                                    @else
                                        <flux:tooltip content="Secondary Location">
                                            <flux:badge color="zinc" size="xs">Secondary</flux:badge>
                                        </flux:tooltip>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Actions -->
                                <flux:table.cell class="py-2">
                                    <flux:dropdown align="end">
                                        <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                        <flux:menu>
                                            <flux:menu.item 
                                                icon="eye"
                                                href="{{ route('clients.locations.show', [$client, $location]) }}"
                                            >
                                                View
                                            </flux:menu.item>
                                            <flux:menu.item 
                                                icon="pencil"
                                                href="{{ route('clients.locations.edit', [$client, $location]) }}"
                                            >
                                                Edit
                                            </flux:menu.item>
                                            @if($location->phone)
                                                <flux:menu.item 
                                                    icon="phone"
                                                    href="tel:{{ $location->phone }}"
                                                >
                                                    Call
                                                </flux:menu.item>
                                            @endif
                                            <flux:separator />
                                            <form method="POST" action="{{ route('clients.locations.destroy', [$client, $location]) }}" 
                                                  onsubmit="return confirm('Delete {{ $location->name }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <flux:menu.item 
                                                    icon="trash"
                                                    type="submit"
                                                    variant="danger"
                                                >
                                                    Delete
                                                </flux:menu.item>
                                            </form>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
            
            @if($locations->hasPages())
                <div class="mt-3 border-t pt-3">
                    {{ $locations->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <flux:icon.map-pin class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No locations found</flux:heading>
                <flux:text class="mt-2">
                    @if(request()->hasAny(['search', 'state', 'country', 'primary_only']))
                        No locations match your filters. Try adjusting your search criteria.
                    @else
                        Get started by adding your first location for {{ $client->name }}.
                    @endif
                </flux:text>
                <div class="mt-6">
                    @if(request()->hasAny(['search', 'state', 'country', 'primary_only']))
                        <flux:button variant="subtle" href="{{ route('clients.locations.index', $client) }}">
                            Clear Filters
                        </flux:button>
                    @else
                        <flux:button variant="primary" icon="plus" href="{{ route('clients.locations.create', $client) }}">
                            Add First Location
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:card>
</div>
@endsection
