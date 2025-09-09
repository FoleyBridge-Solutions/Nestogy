@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Files
    </flux:heading>

    <div class="flex justify-between items-center mb-6">
        <flux:subheading>
            Manage file storage and organization for {{ $client->name }}
        </flux:subheading>

        <div class="flex gap-3">
            <flux:button variant="outline" href="{{ route('clients.files.export', $client) }}">
                Export CSV
            </flux:button>
            <flux:button href="{{ route('clients.files.create', $client) }}">
                Upload File
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <form method="GET" action="{{ route('clients.files.index', $client) }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Search -->
                <flux:field>
                    <flux:input
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, description, filename..."
                        label="Search" />
                </flux:field>

                <!-- Folder -->
                <flux:field>
                    <flux:select name="folder" label="Folder">
                        <flux:select.option value="">All Folders</flux:select.option>
                        @foreach($folders as $key => $label)
                            <flux:select.option value="{{ $key }}" {{ request('folder') === $key ? 'selected' : '' }}>{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <!-- Actions -->
                <div class="flex items-end gap-3">
                    <flux:button type="submit" variant="primary">
                        Filter
                    </flux:button>
                    <flux:button variant="outline" href="{{ route('clients.files.index', $client) }}">
                        Clear
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:card>

    <!-- Files Table -->
    <flux:card>
        <flux:table>
            <flux:columns>
                <flux:column>File</flux:column>
                <flux:column>Folder</flux:column>
                <flux:column>Size</flux:column>
                <flux:column>Uploaded</flux:column>
                <flux:column></flux:column>
            </flux:columns>

            <flux:rows>
                @forelse($files as $file)
                <flux:flex flex-wrap>
                    <flux:cell>
                        <div class="font-medium">{{ $file->name }}</div>
                        <div class="text-sm text-gray-500">{{ $file->original_filename }}</div>
                        @if($file->description)
                            <div class="text-xs text-gray-400">{{ Str::limit($file->description, 50) }}</div>
                        @endif
                    </flux:cell>
                    <flux:cell>
                        <flux:badge color="gray">
                            {{ $folders[$file->folder] ?? $file->folder ?? 'Root' }}
                        </flux:badge>
                    </flux:cell>
                    <flux:cell>{{ $file->formatted_file_size }}</flux:cell>
                    <flux:cell>
                        <div>{{ $file->created_at->format('M j, Y') }}</div>
                        <div class="text-sm text-gray-500">by {{ $file->uploader->name ?? 'Unknown' }}</div>
                    </flux:cell>
                    <flux:cell>
                        <div class="flex gap-2">
                            <flux:button variant="outline" size="sm" href="{{ route('clients.files.show', [$client, $file]) }}">
                                View
                            </flux:button>
                            <flux:button variant="outline" size="sm" href="{{ route('clients.files.download', [$client, $file]) }}">
                                Download
                            </flux:button>
                        </div>
                    </flux:cell>
                </flux:flex flex-wrap>
                @empty
                <flux:flex flex-wrap>
                    <flux:cell colspan="5" class="text-center py-8">
                        <div class="text-gray-500">No files found</div>
                        <flux:button href="{{ route('clients.files.create', $client) }}" class="mt-6">
                            Upload File
                        </flux:button>
                    </flux:cell>
                </flux:flex flex-wrap>
                @endforelse
            </flux:rows>
        </flux:table>

        @if($files->hasPages())
            <div class="mt-6">
                {{ $files->links() }}
            </div>
        @endif
    </flux:card>
</div>
@endsection
