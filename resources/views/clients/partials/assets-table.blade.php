@if($assets->count() > 0)
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Serial Number</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($assets as $asset)
                <flux:table.row class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800" onclick="window.location='{{ route('assets.show', $asset) }}'">
                    <flux:table.cell>{{ $asset->name }}</flux:table.cell>
                    <flux:table.cell>{{ ucfirst($asset->type ?? 'Unknown') }}</flux:table.cell>
                    <flux:table.cell>{{ $asset->serial_number ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $asset->status === 'Active' ? 'green' : 'zinc' }}" size="sm">
                            {{ ucfirst($asset->status ?? 'unknown') }}
                        </flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
@else
    <p class="text-gray-500 dark:text-gray-400">No assets found for this client.</p>
@endif