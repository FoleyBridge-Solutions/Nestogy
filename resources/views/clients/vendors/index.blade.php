@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Vendors
    </flux:heading>

    <div class="mb-6">
        <flux:subheading>
            Manage vendor relationships and contracts for {{ $client->name }}
        </flux:subheading>
    </div>

    <!-- Actions Bar -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex space-x-2">
            <flux:button href="{{ route('clients.vendors.create', ['client' => $client->id]) }}" color="primary">
                <flux:icon name="plus" />
                Add Vendor
            </flux:button>
        </div>

        <!-- Filters -->
        <div class="flex space-x-2">
            <flux:select placeholder="Filter by Type" wire:model.live="vendorType">
                <flux:select.option value="">All Types</flux:select.option>
                @foreach($vendorTypes as $key => $type)
                    <flux:select.option value="{{ $key }}">{{ $type }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select placeholder="Filter by Category" wire:model.live="category">
                <flux:select.option value="">All Categories</flux:select.option>
                @foreach($vendorCategories as $key => $category)
                    <flux:select.option value="{{ $key }}">{{ $category }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Vendors Table -->
    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable="name">Vendor Name</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Category</flux:table.column>
            <flux:table.column>Contact</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Rating</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($vendors as $vendor)
            <flux:table.row>
                <flux:table.cell>
                    <div class="font-medium">{{ $vendor->vendor_name }}</div>
                    @if($vendor->description)
                        <div class="text-sm text-gray-500">{{ Str::limit($vendor->description, 50) }}</div>
                    @endif
                </flux:table.cell>
                <flux:table.cell>{{ $vendor->vendor_type }}</flux:table.cell>
                <flux:table.cell>{{ $vendor->category ?: 'N/A' }}</flux:table.cell>
                <flux:table.cell>
                    @if($vendor->contact_person)
                        <div>{{ $vendor->contact_person }}</div>
                        @if($vendor->email)
                            <div class="text-sm text-gray-500">{{ $vendor->email }}</div>
                        @endif
                    @else
                        <span class="text-gray-400">No contact</span>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge :color="$vendor->is_preferred ? 'green' : ($vendor->is_approved ? 'blue' : 'gray')">
                        @if($vendor->is_preferred)
                            Preferred
                        @elseif($vendor->is_approved)
                            Approved
                        @else
                            Pending
                        @endif
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    @if($vendor->overall_rating)
                        <div class="flex items-center">
                            <flux:icon name="star" class="w-4 h-4 text-yellow-400 mr-1" />
                            {{ number_format($vendor->overall_rating, 1) }}
                        </div>
                    @else
                        <span class="text-gray-400">Not rated</span>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <div class="flex space-x-1">
                        <flux:button href="{{ route('clients.vendors.show', ['client' => $client->id, 'vendor' => $vendor->id]) }}" size="sm" variant="ghost">
                            <flux:icon name="eye" />
                        </flux:button>
                        <flux:button href="{{ route('clients.vendors.edit', ['client' => $client->id, 'vendor' => $vendor->id]) }}" size="sm" variant="ghost">
                            <flux:icon name="pencil" />
                        </flux:button>
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @empty
            <flux:table.row>
                <flux:table.cell colspan="7" class="text-center py-8">
                    <div class="text-gray-500">
                        <flux:icon name="building-storefront" class="w-12 h-12 mx-auto mb-6 text-gray-300" />
                        <p class="text-lg font-medium">No vendors found</p>
                        <p class="text-sm">Get started by adding your first vendor for this client.</p>
                        <flux:button href="{{ route('clients.vendors.create', ['client' => $client->id]) }}" color="primary" class="mt-6">
                            <flux:icon name="plus" />
                            Add First Vendor
                        </flux:button>
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <!-- Pagination -->
    @if($vendors->hasPages())
        <div class="mt-6">
            {{ $vendors->links() }}
        </div>
    @endif

    <!-- Summary Stats -->
    @if($vendors->count() > 0)
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card>
            <div class="px-6 py-6 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Total Vendors</flux:heading>
            </div>
            <div class="p-6">
                <div class="text-2xl font-bold">{{ $vendors->total() }}</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="px-6 py-6 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Preferred Vendors</flux:heading>
            </div>
            <div class="p-6">
                <div class="text-2xl font-bold text-green-600">
                    {{ $vendors->where('is_preferred', true)->count() }}
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="px-6 py-6 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Approved Vendors</flux:heading>
            </div>
            <div class="p-6">
                <div class="text-2xl font-bold text-blue-600">
                    {{ $vendors->where('is_approved', true)->count() }}
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="px-6 py-6 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Average Rating</flux:heading>
            </div>
            <div class="p-6">
                <div class="text-2xl font-bold">
                    {{ $vendors->avg('overall_rating') ? number_format($vendors->avg('overall_rating'), 1) : 'N/A' }}
                </div>
            </div>
        </flux:card>
    </div>
    @endif
</div>
@endsection
