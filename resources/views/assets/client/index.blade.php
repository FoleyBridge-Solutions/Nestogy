@extends('layouts.app')

@section('title', $client->name . ' - Assets')

@section('content')
<div class="container mx-auto px-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="flex items-center mb-4">
            <a href="{{ route('clients.show', $client) }}" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to {{ $client->name }}
            </a>
        </nav>
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Assets for {{ $client->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Manage and track all assets for this client</p>
            </div>
            
            <div class="flex gap-2">
                <flux:button href="{{ route('clients.assets.create', $client) }}" variant="primary">
                    <i class="fas fa-plus mr-2"></i>
                    Add Asset
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:field>
                <flux:label for="search">Search</flux:label>
                <flux:input 
                    type="text" 
                    id="search" 
                    placeholder="Search assets..."
                    wire:model.live="search" />
            </flux:field>
            
            <flux:field>
                <flux:label for="type">Type</flux:label>
                <flux:select id="type" wire:model.live="type">
                    <option value="">All Types</option>
                    @foreach(App\Models\Asset::TYPES as $assetType)
                        <option value="{{ $assetType }}">{{ $assetType }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
            
            <flux:field>
                <flux:label for="status">Status</flux:label>
                <flux:select id="status" wire:model.live="status">
                    <option value="">All Statuses</option>
                    @foreach(App\Models\Asset::STATUSES as $assetStatus)
                        <option value="{{ $assetStatus }}">{{ $assetStatus }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
            
            <flux:field>
                <flux:label for="location">Location</flux:label>
                <flux:select id="location" wire:model.live="locationId">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    <!-- Assets Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Serial Number</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Location</flux:table.column>
                <flux:table.column>Assigned To</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($assets as $asset)
                    <flux:table.row>
                        <flux:table.cell>
                            <a href="{{ route('assets.show', $asset) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                {{ $asset->name }}
                            </a>
                            @if($asset->description)
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($asset->description, 50) }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge>{{ $asset->type }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-mono text-sm">{{ $asset->serial ?: '-' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $statusColors = [
                                    'Ready To Deploy' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'Deployed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'Pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'Broken' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'Archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    'Out for Repair' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                ];
                                $statusColor = $statusColors[$asset->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <flux:badge class="{{ $statusColor }}">{{ $asset->status }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $asset->location->name ?? '-' }}
                            @if($asset->room)
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $asset->room }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $asset->contact->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button 
                                    href="{{ route('assets.show', $asset) }}" 
                                    variant="ghost" 
                                    size="sm">
                                    <i class="fas fa-eye"></i>
                                </flux:button>
                                <flux:button 
                                    href="{{ route('assets.edit', $asset) }}" 
                                    variant="ghost" 
                                    size="sm">
                                    <i class="fas fa-edit"></i>
                                </flux:button>
                            </div>
                        </flux:cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8">
                            <div class="text-gray-500 dark:text-gray-400">
                                <i class="fas fa-desktop text-4xl mb-4"></i>
                                <p>No assets found for this client.</p>
                                <flux:button href="{{ route('clients.assets.create', $client) }}" variant="primary" class="mt-4">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add First Asset
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        
        @if($assets->hasPages())
            <div class="mt-4">
                {{ $assets->links() }}
            </div>
        @endif
    </flux:card>
</div>
@endsection