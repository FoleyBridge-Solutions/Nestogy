@extends('layouts.app')

@section('title', $client->name . ' - Contacts')

@section('content')
<div class="container-fluid h-full flex flex-col">
    <!-- Compact Header with Filters -->
    <flux:card class="mb-3">
        <div class="flex items-center justify-between mb-3">
            <div>
                <flux:heading>{{ $client->name }} - Contacts</flux:heading>
                <flux:text size="sm">{{ $contacts->total() }} total contacts</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" size="sm" href="{{ route('clients.index') }}">
                    Back to Dashboard
                </flux:button>
                <flux:button 
                    variant="subtle" 
                    size="sm"
                    icon="arrow-down-tray"
                    href="{{ route('clients.contacts.export', request()->query()) }}"
                >
                    Export
                </flux:button>
                <flux:button 
                    variant="primary" 
                    size="sm"
                    icon="plus"
                    href="{{ route('clients.contacts.create') }}"
                >
                    Add Contact
                </flux:button>
            </div>
        </div>
        
        <!-- Inline Filters -->
        <form method="GET" action="{{ route('clients.contacts.index') }}">
            <div class="flex gap-2">
                <flux:input 
                    name="search" 
                    placeholder="Search..." 
                    icon="magnifying-glass"
                    size="sm"
                    class="flex-1 max-w-xs"
                    value="{{ request('search') }}"
                />
                
                <flux:select name="type" placeholder="All Types" size="sm" class="w-40" value="{{ request('type') }}">
                    <flux:select.option value="">All Types</flux:select.option>
                    <flux:select.option value="primary">Primary</flux:select.option>
                    <flux:select.option value="billing">Billing</flux:select.option>
                    <flux:select.option value="technical">Technical</flux:select.option>
                    <flux:select.option value="important">Important</flux:select.option>
                </flux:select>
                
                <flux:button type="submit" variant="primary" size="sm">
                    Apply
                </flux:button>
                @if(request()->hasAny(['search', 'type']))
                    <flux:button 
                        variant="ghost" 
                        size="sm"
                        href="{{ route('clients.contacts.index') }}"
                    >
                        Clear
                    </flux:button>
                @endif
            </div>
        </form>
    </flux:card>

    <!-- Dense Contacts Table -->
    <flux:card class="flex-1">
        @if($contacts->count() > 0)
            <div class="overflow-x-auto h-full">
                <flux:table class="text-base">
                    <flux:table.columns>
                        <flux:table.column class="w-40">Name</flux:table.column>
                        <flux:table.column class="w-24">Department</flux:table.column>
                        <flux:table.column class="w-48">Email</flux:table.column>
                        <flux:table.column class="w-40">Phone & Mobile</flux:table.column>
                        <flux:table.column class="w-24">Type & Status</flux:table.column>
                        <flux:table.column class="w-20">Timezone</flux:table.column>
                        <flux:table.column class="w-24">Preferred Contact</flux:table.column>
                        <flux:table.column class="w-20">Language</flux:table.column>
                        <flux:table.column class="w-32">Last Activity</flux:table.column>
                        <flux:table.column class="w-24">Location</flux:table.column>
                        <flux:table.column class="w-16">Portal</flux:table.column>
                        <flux:table.column class="w-16"></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($contacts as $contact)
                            <flux:table.row class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <!-- Name & Title -->
                                <flux:table.cell class="py-2">
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" class="flex-shrink-0">
                                            {{ substr($contact->name, 0, 1) }}
                                        </flux:avatar>
                                        <div class="min-w-0">
                                            <div class="font-medium truncate">{{ $contact->name }}</div>
                                            @if($contact->title)
                                                <div class="text-sm text-zinc-500 truncate">{{ $contact->title }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </flux:table.cell>
                                
                                <!-- Department -->
                                <flux:table.cell class="py-2">
                                    <div class="text-sm text-zinc-900 font-medium truncate">
                                        {{ $contact->department ?: 'General' }}
                                    </div>
                                    @if($contact->role)
                                        <div class="text-sm text-zinc-500 truncate">{{ $contact->role }}</div>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Email -->
                                <flux:table.cell class="py-2">
                                    @if($contact->email)
                                        <flux:link href="mailto:{{ $contact->email }}" class="text-sm truncate block">
                                            {{ $contact->email }}
                                        </flux:link>
                                    @else
                                        <span class="text-zinc-400 text-sm">-</span>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Phone & Mobile Combined -->
                                <flux:table.cell class="py-2">
                                    <div class="space-y-0.5">
                                        @if($contact->phone)
                                            <div class="flex items-center gap-1">
                                                <flux:icon.phone variant="micro" class="text-zinc-400 w-3 h-3 flex-shrink-0" />
                                                <span class="text-sm text-zinc-700">{{ $contact->phone }}@if($contact->extension) ext. {{ $contact->extension }}@endif</span>
                                            </div>
                                        @endif
                                        @if($contact->mobile)
                                            <div class="flex items-center gap-1">
                                                <flux:icon.device-phone-mobile variant="micro" class="text-zinc-400 w-3 h-3 flex-shrink-0" />
                                                <span class="text-sm text-zinc-700">{{ $contact->mobile }}</span>
                                            </div>
                                        @endif
                                        @if(!$contact->phone && !$contact->mobile)
                                            <span class="text-zinc-400 text-sm">-</span>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                
                                <!-- Type & Status Combined -->
                                <flux:table.cell class="py-2">
                                    <div class="flex flex-wrap gap-0.5">
                                        <!-- Contact Type Badges -->
                                        @if($contact->primary)
                                            <flux:tooltip content="Primary Contact">
                                                <flux:badge color="blue" size="xs">P</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                        @if($contact->billing)
                                            <flux:tooltip content="Billing Contact">
                                                <flux:badge color="green" size="xs">B</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                        @if($contact->technical)
                                            <flux:tooltip content="Technical Contact">
                                                <flux:badge color="purple" size="xs">T</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                        @if($contact->important)
                                            <flux:tooltip content="Important Contact">
                                                <flux:badge color="amber" size="xs">!</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                        @if(!$contact->primary && !$contact->billing && !$contact->technical && !$contact->important)
                                            <flux:tooltip content="General Contact">
                                                <flux:badge color="zinc" size="xs">G</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                        
                                        <!-- Status Badges -->
                                        @if($contact->is_emergency_contact)
                                            <flux:tooltip content="Emergency Contact">
                                                <flux:badge color="red" size="xs">E</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                        @if($contact->is_after_hours_contact)
                                            <flux:tooltip content="After Hours">
                                                <flux:badge color="orange" size="xs">AH</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                        @if($contact->do_not_disturb)
                                            <flux:tooltip content="Do Not Disturb">
                                                <flux:badge color="gray" size="xs">DND</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                        @if($contact->out_of_office_start && $contact->out_of_office_end && 
                                           now()->between($contact->out_of_office_start, $contact->out_of_office_end))
                                            <flux:tooltip content="Out of Office">
                                                <flux:badge color="yellow" size="xs">OOO</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                
                                <!-- Timezone -->
                                <flux:table.cell class="py-2">
                                    @if($contact->timezone)
                                        <flux:tooltip content="{{ $contact->timezone }}">
                                            <span class="text-sm text-zinc-600">
                                                {{ \Carbon\Carbon::now($contact->timezone)->format('g:i A') }}
                                            </span>
                                        </flux:tooltip>
                                    @else
                                        <span class="text-zinc-400 text-sm">-</span>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Preferred Contact Method -->
                                <flux:table.cell class="py-2">
                                    @if($contact->preferred_contact_method)
                                        <flux:tooltip content="Preferred: {{ ucfirst($contact->preferred_contact_method) }}">
                                            <div class="flex items-center gap-1">
                                                @if($contact->preferred_contact_method === 'email')
                                                    <flux:icon.envelope variant="micro" class="text-blue-500 w-3 h-3" />
                                                @elseif($contact->preferred_contact_method === 'phone')
                                                    <flux:icon.phone variant="micro" class="text-green-500 w-3 h-3" />
                                                @elseif($contact->preferred_contact_method === 'mobile')
                                                    <flux:icon.device-phone-mobile variant="micro" class="text-purple-500 w-3 h-3" />
                                                @elseif($contact->preferred_contact_method === 'sms')
                                                    <flux:icon.chat-bubble-left variant="micro" class="text-orange-500 w-3 h-3" />
                                                @endif
                                                <span class="text-sm">{{ ucfirst($contact->preferred_contact_method) }}</span>
                                            </div>
                                        </flux:tooltip>
                                    @else
                                        <span class="text-zinc-400 text-sm">-</span>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Language -->
                                <flux:table.cell class="py-2">
                                    @if($contact->language && $contact->language !== 'en')
                                        <span class="text-sm font-mono uppercase">{{ $contact->language }}</span>
                                    @else
                                        <span class="text-sm text-zinc-400">EN</span>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Last Activity -->
                                <flux:table.cell class="py-2">
                                    @if($contact->updated_at)
                                        <flux:tooltip content="{{ $contact->updated_at->format('M j, Y g:i A') }}">
                                            <div class="text-sm">
                                                <div>{{ $contact->updated_at->format('M j') }}</div>
                                                <div class="text-zinc-500">{{ $contact->updated_at->format('g:i A') }}</div>
                                            </div>
                                        </flux:tooltip>
                                    @else
                                        <span class="text-zinc-400 text-sm">-</span>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Location -->
                                <flux:table.cell class="py-2">
                                    @if($contact->officeLocation)
                                        <flux:tooltip content="{{ $contact->officeLocation->name }}">
                                            <div class="flex items-center gap-1">
                                                <flux:icon.map-pin variant="micro" class="text-zinc-400 w-3 h-3" />
                                                <span class="text-sm truncate">{{ $contact->officeLocation->name }}</span>
                                            </div>
                                        </flux:tooltip>
                                    @else
                                        <span class="text-zinc-400 text-sm">-</span>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Portal Access -->
                                <flux:table.cell class="py-2">
                                    @if($contact->has_portal_access)
                                        <flux:tooltip content="Has Portal Access">
                                            <flux:icon.check-circle variant="micro" class="text-green-500 w-4 h-4" />
                                        </flux:tooltip>
                                    @else
                                        <flux:tooltip content="No Portal Access">
                                            <flux:icon.x-circle variant="micro" class="text-zinc-300 w-4 h-4" />
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
                                                href="{{ route('clients.contacts.show', $contact) }}"
                                            >
                                                View
                                            </flux:menu.item>
                                            <flux:menu.item 
                                                icon="pencil"
                                                href="{{ route('clients.contacts.edit', $contact) }}"
                                            >
                                                Edit
                                            </flux:menu.item>
                                            @if($contact->email)
                                                <flux:menu.item 
                                                    icon="envelope"
                                                    href="mailto:{{ $contact->email }}"
                                                >
                                                    Email
                                                </flux:menu.item>
                                            @endif
                                            <flux:separator />
                                            <form method="POST" action="{{ route('clients.contacts.destroy', $contact) }}" 
                                                  onsubmit="return confirm('Delete {{ $contact->name }}?');">
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
            
            @if($contacts->hasPages())
                <div class="mt-3 border-t pt-3">
                    {{ $contacts->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <flux:icon.user-group class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No contacts found</flux:heading>
                <flux:text class="mt-2">
                    @if(request()->hasAny(['search', 'type']))
                        No contacts match your filters. Try adjusting your search criteria.
                    @else
                        Get started by adding your first contact for {{ $client->name }}.
                    @endif
                </flux:text>
                <div class="mt-6">
                    @if(request()->hasAny(['search', 'type']))
                        <flux:button variant="subtle" href="{{ route('clients.contacts.index') }}">
                            Clear Filters
                        </flux:button>
                    @else
                        <flux:button variant="primary" icon="plus" href="{{ route('clients.contacts.create') }}">
                            Add First Contact
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:card>
</div>
@endsection