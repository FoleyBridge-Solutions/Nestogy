@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Documents
    </flux:heading>

    <div class="flex justify-between items-center mb-6">
        <flux:subheading>
            Manage and organize documents for {{ $client->name }}
        </flux:subheading>

        <div class="flex gap-3">
            <flux:button variant="outline" href="{{ route('clients.documents.export', $client) }}">
                Export CSV
            </flux:button>
            <flux:button href="{{ route('clients.documents.create', $client) }}">
                Upload Document
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <form method="GET" action="{{ route('clients.documents.index', $client) }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Search -->
                <flux:field>
                    <flux:input
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, description, filename..."
                        label="Search" />
                </flux:field>

                <!-- Category -->
                <flux:field>
                    <flux:select name="category" label="Category">
                        <flux:select.option value="">All Categories</flux:select.option>
                        @foreach($categories as $key => $label)
                            <flux:select.option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Actions -->
                <div class="flex items-end gap-3">
                    <flux:button type="submit" variant="primary">
                        Filter
                    </flux:button>
                    <flux:button variant="outline" href="{{ route('clients.documents.index', $client) }}">
                        Clear
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:card>

    <!-- Documents Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Document</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Size</flux:table.column>
                <flux:table.column>Uploaded</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($documents as $document)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="font-medium">{{ $document->name }}</div>
                        <div class="text-sm text-gray-500">{{ $document->original_filename }}</div>
                        @if($document->is_confidential)
                            <flux:badge color="red" class="mt-1">Confidential</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue">
                            {{ $categories[$document->category] ?? $document->category }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $document->formatted_file_size }}</flux:table.cell>
                    <flux:table.cell>
                        <div>{{ $document->created_at->format('M j, Y') }}</div>
                        <div class="text-sm text-gray-500">by {{ $document->uploader->name ?? 'Unknown' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button variant="outline" size="sm" href="{{ route('clients.documents.show', [$client, $document]) }}">
                                View
                            </flux:button>
                            <flux:button variant="outline" size="sm" href="{{ route('clients.documents.download', [$client, $document]) }}">
                                Download
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-8">
                        <div class="text-gray-500">No documents found</div>
                        <flux:button href="{{ route('clients.documents.create', $client) }}" class="mt-6">
                            Upload Document
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($documents->hasPages())
            <div class="mt-6">
                {{ $documents->links() }}
            </div>
        @endif
    </flux:card>
</div>
@endsection
