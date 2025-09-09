@if($locations->count() > 0)
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Address</flux:table.column>
            <flux:table.column>City, State</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($locations as $location)
                <flux:table.row 
                    class="hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition-colors"
                    onclick="window.location.href='{{ route('clients.locations.edit', [$location->client_id, $location]) }}'">
                    <flux:table.cell>
                        {{ $location->name }}
                        @if($location->primary)
                            <flux:badge color="blue" size="sm" class="ml-2">Primary</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $location->address ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $location->city ?? '' }}{{ $location->city && $location->state ? ', ' : '' }}{{ $location->state ?? '' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button variant="ghost" size="sm" onclick="event.stopPropagation(); window.location.href='{{ route('clients.locations.edit', [$location->client_id, $location]) }}'">
                            Edit
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
@else
    <p class="text-gray-500 dark:text-gray-400">No locations found for this client.</p>
@endif