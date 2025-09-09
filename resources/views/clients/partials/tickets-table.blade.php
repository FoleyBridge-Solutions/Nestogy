@if($tickets->count() > 0)
    <flux:table>
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Subject</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Priority</flux:table.column>
            <flux:table.column>Created</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($tickets as $ticket)
                <flux:table.row class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800" onclick="window.location='{{ route('tickets.show', $ticket) }}'">
                    <flux:table.cell>#{{ $ticket->number ?? $ticket->id }}</flux:table.cell>
                    <flux:table.cell>{{ Str::limit($ticket->subject, 50) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $ticket->status === 'Open' ? 'yellow' : ($ticket->status === 'Closed' ? 'green' : 'blue') }}" size="sm">
                            {{ $ticket->status }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $ticket->priority === 'high' ? 'red' : ($ticket->priority === 'medium' ? 'yellow' : 'zinc') }}" size="sm">
                            {{ ucfirst($ticket->priority ?? 'normal') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $ticket->created_at->format('M d, Y') }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
@else
    <p class="text-gray-500 dark:text-gray-400">No tickets found for this client.</p>
@endif