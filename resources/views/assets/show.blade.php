@extends('layouts.app')

@section('title', $asset->name . ' - Asset Details')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    {{-- Page Header --}}
    <div class="mb-6 flex items-center justify-end">
        <div class="flex items-center gap-3">
            <flux:button 
                href="{{ route('assets.edit', $asset) }}" 
                variant="primary"
                icon="pencil"
            >
                Edit Asset
            </flux:button>
            
            <flux:dropdown align="end">
                <flux:button icon="ellipsis-vertical" variant="filled">Actions</flux:button>
                
                <flux:menu>
                    <flux:menu.item icon="qr-code" href="{{ route('assets.qr-code', $asset) }}" target="_blank">
                        View QR Code
                    </flux:menu.item>
                    <flux:menu.item icon="tag" href="{{ route('assets.label', $asset) }}" target="_blank">
                        Print Label
                    </flux:menu.item>
                    
                    <flux:separator />
                    
                    <flux:modal.trigger name="check-in-out-{{ $asset->id }}">
                        <flux:menu.item icon="arrow-path">
                            Check In/Out
                        </flux:menu.item>
                    </flux:modal.trigger>
                    
                    <flux:separator />
                    
                    <flux:menu.item 
                        icon="archive-box" 
                        href="{{ route('assets.archive', $asset) }}"
                        x-on:click.prevent="$el.closest('form') ? $el.closest('form').submit() : (function() { 
                            let form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '{{ route('assets.archive', $asset) }}';
                            let csrf = document.createElement('input');
                            csrf.type = 'hidden';
                            csrf.name = '_token';
                            csrf.value = '{{ csrf_token() }}';
                            form.appendChild(csrf);
                            let method = document.createElement('input');
                            method.type = 'hidden';
                            method.name = '_method';
                            method.value = 'PATCH';
                            form.appendChild(method);
                            document.body.appendChild(form);
                            form.submit();
                        })()"
                    >
                        Archive Asset
                    </flux:menu.item>
                    
                    @can('delete', $asset)
                    <flux:menu.item 
                        variant="danger" 
                        icon="trash"
                        x-on:click.prevent="if(confirm('Are you sure you want to delete this asset? This action cannot be undone.')) {
                            let form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '{{ route('assets.destroy', $asset) }}';
                            let csrf = document.createElement('input');
                            csrf.type = 'hidden';
                            csrf.name = '_token';
                            csrf.value = '{{ csrf_token() }}';
                            form.appendChild(csrf);
                            let method = document.createElement('input');
                            method.type = 'hidden';
                            method.name = '_method';
                            method.value = 'DELETE';
                            form.appendChild(method);
                            document.body.appendChild(form);
                            form.submit();
                        }"
                    >
                        Delete Asset
                    </flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Asset Overview Card --}}
    <flux:card class="mb-6">
        {{-- Card Header with Asset Info and Status --}}
        <div class="flex items-start justify-between pb-6 mb-6 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-start gap-4">
                <div class="p-3 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.{{ $asset->type === 'Server' ? 'server' : ($asset->type === 'Desktop' ? 'computer-desktop' : 'device-phone-mobile') }} class="size-8 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ $asset->name }}</flux:heading>
                    <flux:text variant="subtle" class="mt-1">{{ $asset->type }} • {{ $asset->make }} {{ $asset->model }}</flux:text>
                </div>
            </div>
            <div>
                @php
                    $statusColors = [
                        'active' => 'green',
                        'inactive' => 'zinc',
                        'maintenance' => 'yellow',
                        'retired' => 'red',
                        'Ready To Deploy' => 'emerald',
                        'Deployed' => 'blue',
                        'Archived' => 'zinc',
                        'Broken - Pending Repair' => 'amber',
                        'Broken - Not Repairable' => 'red',
                        'Out for Repair' => 'amber',
                        'Lost/Stolen' => 'red',
                        'Unknown' => 'zinc',
                    ];
                    $statusColor = $statusColors[$asset->status] ?? 'zinc';
                @endphp
                <flux:badge color="{{ $statusColor }}" size="lg">
                    {{ ucfirst($asset->status) }}
                </flux:badge>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Basic Information --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <flux:text variant="subtle" class="text-sm mb-1">Client</flux:text>
                        <flux:link href="{{ route('clients.show', $asset->client) }}" class="text-base">
                            {{ $asset->client->name }}
                        </flux:link>
                    </div>
                    <div>
                        <flux:text variant="subtle" class="text-sm mb-1">Serial Number</flux:text>
                        <flux:text class="font-mono">
                            {{ $asset->serial ?: 'N/A' }}
                        </flux:text>
                    </div>
                </div>
                
                @if($asset->description)
                <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:text variant="subtle" class="text-sm mb-1">Description</flux:text>
                    <flux:text>{{ $asset->description }}</flux:text>
                </div>
                @endif
            </div>
            
            {{-- QR Code --}}
            <div class="flex flex-col items-center justify-center p-6 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="mb-3 bg-white p-3 rounded">
                    {!! $qrCode !!}
                </div>
                <flux:text variant="subtle" class="text-xs">
                    Asset ID: {{ $asset->id }}
                </flux:text>
            </div>
        </div>
    </flux:card>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Left Column --}}
        <div class="space-y-6">
            {{-- Hardware Information --}}
            <flux:card>
                <flux:heading class="flex items-center gap-2 mb-4">
                    <flux:icon.cpu-chip class="size-5" />
                    Hardware Information
                </flux:heading>
                
                @php
                    $rmmData = null;
                    if ($asset->notes) {
                        try {
                            $rmmData = json_decode($asset->notes, true);
                        } catch (Exception $e) {
                            $rmmData = null;
                        }
                    }
                @endphp
                
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Make</flux:text>
                        <flux:text variant="strong">{{ $asset->make ?: 'N/A' }}</flux:text>
                    </div>
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Model</flux:text>
                        <flux:text variant="strong">{{ $asset->model ?: 'N/A' }}</flux:text>
                    </div>
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Operating System</flux:text>
                        <flux:text variant="strong">
                            @if($asset->os)
                                {{ $asset->os }}
                            @elseif($asset->model && str_contains($asset->model, 'Windows'))
                                {{ $asset->model }}
                            @else
                                N/A
                            @endif
                        </flux:text>
                    </div>
                    @if($rmmData && isset($rmmData['rmm_platform']))
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Platform</flux:text>
                        <flux:text variant="strong">{{ ucfirst($rmmData['rmm_platform']) }}</flux:text>
                    </div>
                    @endif
                    @if($rmmData && isset($rmmData['rmm_version']))
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">RMM Version</flux:text>
                        <flux:text variant="strong">{{ $rmmData['rmm_version'] }}</flux:text>
                    </div>
                    @endif
                    @if($asset->vendor)
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Vendor</flux:text>
                        <flux:text variant="strong">{{ $asset->vendor->name }}</flux:text>
                    </div>
                    @endif
                </div>
            </flux:card>

            {{-- Network Information - Real-time with Livewire --}}
            @livewire('assets.asset-rmm-status', ['asset' => $asset])

            {{-- Important Dates --}}
            <flux:card>
                <flux:heading class="flex items-center gap-2 mb-4">
                    <flux:icon.calendar class="size-5" />
                    Important Dates
                </flux:heading>
                
                <div class="space-y-3">
                    @if($asset->purchase_date)
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Purchase Date</flux:text>
                        <div class="text-right">
                            <flux:text variant="strong">{{ $asset->purchase_date->format('M d, Y') }}</flux:text>
                            @if(method_exists($asset, 'age_in_years') && $asset->age_in_years !== null)
                                <flux:text variant="subtle" class="text-xs block">({{ $asset->age_in_years }} years old)</flux:text>
                            @endif
                        </div>
                    </div>
                    @endif
                    @if($asset->warranty_expire)
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Warranty Expires</flux:text>
                        <div class="flex items-center gap-2">
                            <flux:text variant="strong">{{ $asset->warranty_expire->format('M d, Y') }}</flux:text>
                            @php
                                $isExpired = $asset->warranty_expire < now();
                                $isExpiringSoon = !$isExpired && $asset->warranty_expire->diffInDays() <= 30;
                            @endphp
                            @if($isExpired)
                                <flux:badge color="red">Expired</flux:badge>
                            @elseif($isExpiringSoon)
                                <flux:badge color="yellow">Expiring Soon</flux:badge>
                            @else
                                <flux:badge color="green">Active</flux:badge>
                            @endif
                        </div>
                    </div>
                    @endif
                    @if($asset->install_date)
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Install Date</flux:text>
                        <flux:text variant="strong">{{ $asset->install_date->format('M d, Y') }}</flux:text>
                    </div>
                    @endif
                </div>
            </flux:card>

            @if($rmmData)
            {{-- RMM Information --}}
            <flux:card>
                <flux:heading class="flex items-center gap-2 mb-4">
                    <flux:icon.server class="size-5" />
                    Remote Monitoring
                </flux:heading>
                
                <div class="space-y-3">
                    @if(isset($rmmData['rmm_agent_id']))
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Agent ID</flux:text>
                        <flux:text variant="strong" class="font-mono">{{ $rmmData['rmm_agent_id'] }}</flux:text>
                    </div>
                    @endif
                    @if(isset($rmmData['rmm_monitoring_type']))
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Monitoring Type</flux:text>
                        <flux:text variant="strong">{{ ucfirst($rmmData['rmm_monitoring_type']) }}</flux:text>
                    </div>
                    @endif
                    @if(isset($rmmData['rmm_timezone']))
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Timezone</flux:text>
                        <flux:text variant="strong">{{ $rmmData['rmm_timezone'] ?: 'N/A' }}</flux:text>
                    </div>
                    @endif
                    @if(isset($rmmData['sync_timestamp']))
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Last Sync</flux:text>
                        <div class="text-right">
                            @php
                                try {
                                    $syncTime = \Carbon\Carbon::parse($rmmData['sync_timestamp']);
                                } catch (Exception $e) {
                                    $syncTime = null;
                                }
                            @endphp
                            @if($syncTime)
                                <flux:text variant="strong">{{ $syncTime->format('M d, Y H:i') }}</flux:text>
                                <flux:text variant="subtle" class="text-xs block">({{ $syncTime->diffForHumans() }})</flux:text>
                            @else
                                <flux:text variant="subtle">N/A</flux:text>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </flux:card>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Assignment & Location --}}
            <flux:card>
                <flux:heading class="flex items-center gap-2 mb-4">
                    <flux:icon.map-pin class="size-5" />
                    Assignment & Location
                </flux:heading>
                
                @if($asset->location || $asset->contact)
                <div class="space-y-3">
                    @if($asset->location)
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Location</flux:text>
                        <flux:text variant="strong">{{ $asset->location->name }}</flux:text>
                    </div>
                    @endif
                    @if($asset->contact)
                    <div class="flex justify-between items-center">
                        <flux:text variant="subtle">Assigned To</flux:text>
                        <flux:link href="{{ route('contacts.show', $asset->contact) }}">
                            {{ $asset->contact->name }}
                        </flux:link>
                    </div>
                    @endif
                </div>
                @else
                <div class="text-center py-8">
                    <flux:icon.inbox class="size-12 mx-auto mb-2 text-zinc-300 dark:text-zinc-600" />
                    <flux:text variant="subtle">No assignment or location set</flux:text>
                </div>
                @endif
            </flux:card>

            @if($asset->notes && !$rmmData)
            {{-- Notes (only show if not RMM JSON data) --}}
            <flux:card>
                <flux:heading class="flex items-center gap-2 mb-4">
                    <flux:icon.document-text class="size-5" />
                    Notes
                </flux:heading>
                
                <flux:text>{!! nl2br(e($asset->notes)) !!}</flux:text>
            </flux:card>
            @endif

            @if($asset->files && $asset->files->count() > 0)
            {{-- Files --}}
            <flux:card>
                <div class="flex items-center gap-2 mb-4">
                    <flux:heading class="flex items-center gap-2">
                        <flux:icon.paper-clip class="size-5" />
                        Attachments
                    </flux:heading>
                    <flux:badge color="blue">{{ $asset->files->count() }}</flux:badge>
                </div>
                
                <div class="space-y-2">
                    @foreach($asset->files as $file)
                    <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            @php
                                $fileIcon = 'document';
                                if(method_exists($file, 'file_type')) {
                                    $fileIcon = match($file->file_type) {
                                        'image' => 'photo',
                                        'pdf' => 'document-text',
                                        'document' => 'document-text',
                                        'spreadsheet' => 'document-text',
                                        'archive' => 'archive-box',
                                        default => 'document'
                                    };
                                }
                            @endphp
                            <flux:icon.{{ $fileIcon }} class="size-5 text-zinc-400" />
                            <div class="min-w-0 flex-1">
                                <flux:text class="truncate">{{ $file->name }}</flux:text>
                                <flux:text variant="subtle" class="text-xs">
                                    @if(method_exists($file, 'getFormattedSize'))
                                        {{ $file->getFormattedSize() }}
                                    @else
                                        {{ number_format(($file->file_size ?? $file->size ?? 0) / 1024, 2) }} KB
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                        <flux:button 
                            href="{{ route('files.download', $file) }}" 
                            size="sm" 
                            variant="ghost"
                            icon="arrow-down-tray"
                        >
                            Download
                        </flux:button>
                    </div>
                    @endforeach
                </div>
            </flux:card>
            @endif

            @if($asset->tickets && $asset->tickets->count() > 0)
            {{-- Related Tickets --}}
            <flux:card>
                <div class="flex items-center gap-2 mb-4">
                    <flux:heading class="flex items-center gap-2">
                        <flux:icon.ticket class="size-5" />
                        Related Tickets
                    </flux:heading>
                    <flux:badge color="blue">{{ $asset->tickets->count() }}</flux:badge>
                </div>
                
                <div class="space-y-2">
                    @foreach($asset->tickets->take(5) as $ticket)
                    <div class="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors">
                        <div class="flex items-start justify-between mb-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:text variant="subtle" class="text-sm">#{{ $ticket->number }}</flux:text>
                                    <div class="flex gap-1">
                                        @php
                                            $priorityColors = [
                                                'low' => 'zinc',
                                                'medium' => 'blue',
                                                'high' => 'yellow',
                                                'critical' => 'red'
                                            ];
                                            $statusColors = [
                                                'open' => 'green',
                                                'in_progress' => 'blue',
                                                'pending' => 'yellow',
                                                'resolved' => 'purple',
                                                'closed' => 'zinc'
                                            ];
                                            $priorityColor = $priorityColors[$ticket->priority] ?? 'zinc';
                                            $statusColor = $statusColors[$ticket->status] ?? 'zinc';
                                        @endphp
                                        <flux:badge size="sm" color="{{ $priorityColor }}">
                                            {{ ucfirst($ticket->priority) }}
                                        </flux:badge>
                                        <flux:badge size="sm" color="{{ $statusColor }}">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </flux:badge>
                                    </div>
                                </div>
                                <flux:link href="{{ route('tickets.show', $ticket) }}" class="font-medium">
                                    {{ $ticket->subject }}
                                </flux:link>
                                <flux:text variant="subtle" class="text-xs block mt-1">
                                    {{ $ticket->created_at->diffForHumans() }}
                                </flux:text>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                @if($asset->tickets->count() > 5)
                <div class="mt-4 text-center">
                    <flux:button 
                        href="{{ route('tickets.index', ['asset_id' => $asset->id]) }}" 
                        variant="ghost"
                        icon="arrow-top-right-on-square"
                    >
                        View All {{ $asset->tickets->count() }} Tickets
                    </flux:button>
                </div>
                @endif
            </flux:card>
            @endif
        </div>
    </div>

    {{-- Metadata --}}
    <flux:card class="mt-6">
        <flux:heading class="flex items-center gap-2 mb-4">
            <flux:icon.information-circle class="size-5" />
            Metadata
        </flux:heading>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <div>
                    <flux:text variant="subtle" class="text-sm">Created</flux:text>
                    <flux:text variant="strong" class="block mt-1">
                        {{ $asset->created_at->format('M d, Y g:i A') }}
                    </flux:text>
                    <flux:text variant="subtle" class="text-xs block">
                        {{ $asset->created_at->diffForHumans() }}
                    </flux:text>
                </div>
                <div>
                    <flux:text variant="subtle" class="text-sm">Last Updated</flux:text>
                    <flux:text variant="strong" class="block mt-1">
                        {{ $asset->updated_at->format('M d, Y g:i A') }}
                    </flux:text>
                    <flux:text variant="subtle" class="text-xs block">
                        {{ $asset->updated_at->diffForHumans() }}
                    </flux:text>
                </div>
            </div>
            <div class="space-y-3">
                @if($asset->accessed_at)
                <div>
                    <flux:text variant="subtle" class="text-sm">Last Accessed</flux:text>
                    <flux:text variant="strong" class="block mt-1">
                        {{ $asset->accessed_at->format('M d, Y g:i A') }}
                    </flux:text>
                    <flux:text variant="subtle" class="text-xs block">
                        {{ $asset->accessed_at->diffForHumans() }}
                    </flux:text>
                </div>
                @endif
                @if($asset->rmm_id)
                <div>
                    <flux:text variant="subtle" class="text-sm">RMM ID</flux:text>
                    <flux:text variant="strong" class="font-mono block mt-1">{{ $asset->rmm_id }}</flux:text>
                </div>
                @endif
            </div>
        </div>
    </flux:card>
</div>

{{-- Check In/Out Modal --}}
<flux:modal name="check-in-out-{{ $asset->id }}" class="md:w-96">
    <form action="{{ route('assets.check-in-out', $asset) }}" method="POST">
        @csrf
        
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Check In/Out Asset</flux:heading>
                <flux:text class="mt-2">Manage asset assignment and tracking.</flux:text>
            </div>

            <div>
                <flux:text variant="subtle" class="text-sm mb-3">Action</flux:text>
                <div class="space-y-2">
                    <flux:radio 
                        name="action" 
                        value="check_out" 
                        label="Check Out (Assign to someone)"
                        {{ !$asset->contact_id ? 'checked' : '' }}
                        x-on:change="document.getElementById('contactSelect').style.display = $event.target.checked ? 'block' : 'none'"
                    />
                    <flux:radio 
                        name="action" 
                        value="check_in"
                        label="Check In (Return to inventory)"
                        {{ $asset->contact_id ? 'checked' : '' }}
                        x-on:change="document.getElementById('contactSelect').style.display = $event.target.checked ? 'none' : 'block'"
                    />
                </div>
            </div>

            <div id="contactSelect" style="{{ $asset->contact_id ? 'display: none;' : '' }}">
                <flux:select name="contact_id" label="Assign To" placeholder="Select Contact">
                    @foreach($asset->client->contacts as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea 
                name="notes" 
                label="Notes" 
                rows="3"
                placeholder="Optional notes about this check in/out"
            />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Submit</flux:button>
            </div>
        </div>
    </form>
</flux:modal>
@endsection
