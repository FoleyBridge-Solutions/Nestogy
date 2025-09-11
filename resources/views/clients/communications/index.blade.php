@extends('layouts.app')

@section('title', $client->name . ' - Communications')

@section('content')
<div class="container-fluid h-full flex flex-col">
    <!-- Compact Header with Filters -->
    <flux:card class="mb-3">
        <div class="flex items-center justify-between mb-3">
            <div>
                <flux:heading>{{ $client->name }} - Communications</flux:heading>
                <flux:text size="sm">{{ $communications->total() }} total communications</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" size="sm" href="{{ route('clients.index') }}">
                    Back to Dashboard
                </flux:button>
                <flux:button 
                    variant="subtle" 
                    size="sm"
                    icon="arrow-down-tray"
                    href="{{ route('clients.communications.export', request()->query()) }}"
                >
                    Export
                </flux:button>
                <flux:button 
                    variant="primary" 
                    size="sm"
                    icon="plus"
                    href="{{ route('clients.communications.create') }}"
                >
                    Add Communication
                </flux:button>
            </div>
        </div>
        
        <!-- Inline Filters -->
        <form method="GET" action="{{ route('clients.communications.index') }}">
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
                    @foreach($types as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:select name="channel" placeholder="All Channels" size="sm" class="w-40" value="{{ request('channel') }}">
                    <flux:select.option value="">All Channels</flux:select.option>
                    @foreach($channels as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:checkbox 
                    name="include_automatic" 
                    value="1"
                    label="Include Auto"
                    :checked="request('include_automatic', '1') == '1'"
                    size="sm"
                />
                
                <flux:button type="submit" variant="primary" size="sm">
                    Apply
                </flux:button>
                @if(request()->hasAny(['search', 'type', 'channel']))
                    <flux:button 
                        variant="ghost" 
                        size="sm"
                        href="{{ route('clients.communications.index') }}?include_automatic=1"
                    >
                        Clear
                    </flux:button>
                @endif
            </div>
        </form>
    </flux:card>

    <!-- Communications Table -->
    <flux:card class="flex-1">
        @if($communications->count() > 0)
            <div class="overflow-x-auto h-full">
                <flux:table class="text-base">
                    <flux:table.columns>
                        <flux:table.column class="w-32">Date & Time</flux:table.column>
                        <flux:table.column class="w-24">Source</flux:table.column>
                        <flux:table.column class="w-24">Type</flux:table.column>
                        <flux:table.column class="w-32">Channel</flux:table.column>
                        <flux:table.column class="w-48">Contact</flux:table.column>
                        <flux:table.column class="w-64">Subject</flux:table.column>
                        <flux:table.column class="w-32">User</flux:table.column>
                        <flux:table.column class="w-24">Follow Up</flux:table.column>
                        <flux:table.column class="w-16"></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($communications as $communication)
                            <flux:table.row class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <!-- Date & Time -->
                                <flux:table.cell class="py-2">
                                    <flux:tooltip content="{{ $communication->created_at->format('F j, Y g:i A') }}">
                                        <div class="text-sm">
                                            <div class="font-medium">{{ $communication->created_at->format('M j, Y') }}</div>
                                            <div class="text-zinc-500">{{ $communication->created_at->format('g:i A') }}</div>
                                        </div>
                                    </flux:tooltip>
                                </flux:table.cell>
                                
                                <!-- Source -->
                                <flux:table.cell class="py-2">
                                    <flux:tooltip content="{{ $communication->source }}">
                                        <flux:badge 
                                            color="{{ $communication->type === 'manual' ? 'blue' : 'green' }}" 
                                            size="xs"
                                        >
                                            {{ $communication->type === 'manual' ? 'Manual' : 'Auto' }}
                                        </flux:badge>
                                    </flux:tooltip>
                                </flux:table.cell>
                                
                                <!-- Type -->
                                <flux:table.cell class="py-2">
                                    <flux:tooltip content="{{ ucfirst(str_replace('_', ' ', $communication->communication_type)) }}">
                                        <flux:badge 
                                            color="{{ $communication->communication_type === 'inbound' ? 'green' : 
                                                    ($communication->communication_type === 'outbound' ? 'blue' : 
                                                    ($communication->communication_type === 'meeting' ? 'purple' : 
                                                    ($communication->communication_type === 'support' ? 'red' :
                                                    ($communication->communication_type === 'billing' ? 'orange' : 'zinc')))) }}" 
                                            size="xs"
                                        >
                                            {{ ucfirst(str_replace('_', ' ', $communication->communication_type)) }}
                                        </flux:badge>
                                    </flux:tooltip>
                                </flux:table.cell>
                                
                                <!-- Channel -->
                                <flux:table.cell class="py-2">
                                    <flux:tooltip content="{{ ucfirst(str_replace('_', ' ', $communication->channel)) }}">
                                        <div class="flex items-center gap-1">
                                            @if($communication->channel === 'phone')
                                                <flux:icon.phone variant="micro" class="text-green-500 w-3 h-3" />
                                            @elseif($communication->channel === 'email')
                                                <flux:icon.envelope variant="micro" class="text-blue-500 w-3 h-3" />
                                            @elseif($communication->channel === 'sms')
                                                <flux:icon.chat-bubble-left variant="micro" class="text-orange-500 w-3 h-3" />
                                            @elseif($communication->channel === 'video_call')
                                                <flux:icon.video-camera variant="micro" class="text-purple-500 w-3 h-3" />
                                            @elseif($communication->channel === 'in_person')
                                                <flux:icon.user-group variant="micro" class="text-indigo-500 w-3 h-3" />
                                            @elseif($communication->channel === 'ticket_system')
                                                <flux:icon.ticket variant="micro" class="text-red-500 w-3 h-3" />
                                            @else
                                                <flux:icon.chat-bubble-oval-left variant="micro" class="text-zinc-400 w-3 h-3" />
                                            @endif
                                            <span class="text-sm">{{ ucfirst(str_replace('_', ' ', $communication->channel)) }}</span>
                                        </div>
                                    </flux:tooltip>
                                </flux:table.cell>
                                
                                <!-- Contact -->
                                <flux:table.cell class="py-2">
                                    <div>
                                        <div class="text-sm font-medium truncate">{{ $communication->contact_name }}</div>
                                        @if($communication->contact_email)
                                            <div class="text-sm text-zinc-500 truncate">{{ $communication->contact_email }}</div>
                                        @elseif($communication->contact_phone)
                                            <div class="text-sm text-zinc-500 truncate">{{ $communication->contact_phone }}</div>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                
                                <!-- Subject -->
                                <flux:table.cell class="py-2">
                                    <flux:tooltip content="{{ $communication->subject }}">
                                        <div class="text-sm truncate">{{ $communication->subject }}</div>
                                    </flux:tooltip>
                                </flux:table.cell>
                                
                                <!-- User -->
                                <flux:table.cell class="py-2">
                                    <div class="text-sm">{{ $communication->user_name }}</div>
                                </flux:table.cell>
                                
                                <!-- Follow Up -->
                                <flux:table.cell class="py-2">
                                    @if($communication->follow_up_required)
                                        <flux:tooltip content="Follow up due: {{ $communication->follow_up_date ? $communication->follow_up_date->format('M j, Y') : 'No date set' }}">
                                            <flux:badge 
                                                color="{{ $communication->follow_up_date && $communication->follow_up_date->isPast() ? 'red' : 'yellow' }}" 
                                                size="xs"
                                            >
                                                @if($communication->follow_up_date && $communication->follow_up_date->isPast())
                                                    Overdue
                                                @else
                                                    Due {{ $communication->follow_up_date ? $communication->follow_up_date->format('M j') : 'TBD' }}
                                                @endif
                                            </flux:badge>
                                        </flux:tooltip>
                                    @else
                                        <span class="text-zinc-400 text-sm">-</span>
                                    @endif
                                </flux:table.cell>
                                
                                <!-- Actions -->
                                <flux:table.cell class="py-2">
                                    <flux:dropdown align="end">
                                        <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                        <flux:menu>
                                            @if($communication->route)
                                                <flux:menu.item 
                                                    icon="eye"
                                                    href="{{ $communication->route }}"
                                                >
                                                    View {{ $communication->source }}
                                                </flux:menu.item>
                                            @endif
                                            @if($communication->type === 'manual')
                                                <flux:menu.item 
                                                    icon="pencil"
                                                    href="{{ route('clients.communications.edit', $communication->raw_data) }}"
                                                >
                                                    Edit
                                                </flux:menu.item>
                                            @endif
                                            @if($communication->contact_email)
                                                <flux:menu.item 
                                                    icon="envelope"
                                                    href="mailto:{{ $communication->contact_email }}"
                                                >
                                                    Email Contact
                                                </flux:menu.item>
                                            @endif
                                            @if($communication->contact_phone)
                                                <flux:menu.item 
                                                    icon="phone"
                                                    href="tel:{{ $communication->contact_phone }}"
                                                >
                                                    Call Contact
                                                </flux:menu.item>
                                            @endif
                                            @if($communication->type === 'manual')
                                                <flux:separator />
                                                <form method="POST" action="{{ route('clients.communications.destroy', $communication->raw_data) }}" 
                                                      onsubmit="return confirm('Delete this communication log?');">
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
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
            
            @if($communications->hasPages())
                <div class="mt-3 border-t pt-3">
                    {{ $communications->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <flux:icon.chat-bubble-left-right class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No communications found</flux:heading>
                <flux:text class="mt-2">
                    @if(request()->hasAny(['search', 'type', 'channel']))
                        No communications match your filters. Try adjusting your search criteria.
                    @else
                        Start tracking client communications by adding your first log entry.
                    @endif
                </flux:text>
                <div class="mt-6">
                    @if(request()->hasAny(['search', 'type', 'channel']))
                        <flux:button variant="subtle" href="{{ route('clients.communications.index') }}?include_automatic=1">
                            Clear Filters
                        </flux:button>
                    @else
                        <flux:button variant="primary" icon="plus" href="{{ route('clients.communications.create') }}">
                            Add First Communication
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:card>
</div>
@endsection