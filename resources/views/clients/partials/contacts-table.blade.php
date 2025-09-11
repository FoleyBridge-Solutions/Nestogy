@if($contacts->count() > 0)
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Phone</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($contacts as $contact)
                <flux:table.row 
                    class="hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition-colors"
                    onclick="window.location.href='{{ route('clients.contacts.edit', $contact) }}'">
                    <flux:table.cell>
                        {{ $contact->name }}
                        @if($contact->primary)
                            <flux:badge color="blue" size="sm" class="ml-2">Primary</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:link href="mailto:{{ $contact->email }}" onclick="event.stopPropagation()">{{ $contact->email }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $contact->phone ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $contact->role ?? 'Contact' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button variant="ghost" size="sm" onclick="event.stopPropagation(); window.location.href='{{ route('clients.contacts.edit', $contact) }}'">
                            Edit
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
@else
    <p class="text-gray-500 dark:text-gray-400">No contacts found for this client.</p>
@endif