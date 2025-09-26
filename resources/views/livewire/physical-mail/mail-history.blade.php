<div>
    <!-- Header and Filters -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <flux:heading size="lg">
                @if($client)
                    Physical Mail History for {{ $client->name }}
                @else
                    Physical Mail History
                @endif
            </flux:heading>
            
            <div class="flex items-center gap-2">
                <flux:button size="sm" variant="primary" icon="plus" wire:click="$dispatch('sendPhysicalMail')">
                    Send New Mail
                </flux:button>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <flux:input 
                wire:model.live.debounce="search" 
                placeholder="Search mail..." 
                icon="magnifying-glass"
                clearable
            />
            
            <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
                <flux:option value="">All Statuses</flux:option>
                @foreach($statuses as $value => $label)
                    <flux:option value="{{ $value }}">{{ $label }}</flux:option>
                @endforeach
            </flux:select>
            
            <flux:select wire:model.live="typeFilter" placeholder="All Types">
                <flux:option value="">All Types</flux:option>
                @foreach($types as $value => $label)
                    <flux:option value="{{ $value }}">{{ $label }}</flux:option>
                @endforeach
            </flux:select>
            
            <flux:input 
                type="date" 
                wire:model.live="dateFrom" 
                placeholder="From Date"
            />
            
            <flux:input 
                type="date" 
                wire:model.live="dateTo" 
                placeholder="To Date"
            />
        </div>
        
        @if($search || $statusFilter || $typeFilter || $dateFrom || $dateTo)
            <div class="mt-2">
                <flux:button size="xs" variant="ghost" wire:click="clearFilters">
                    Clear Filters
                </flux:button>
            </div>
        @endif
    </div>
    
    <!-- Mail History Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.row>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Recipient</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Tracking</flux:table.column>
                    <flux:table.column>Cost</flux:table.column>
                    <flux:table.column class="text-right">Actions</flux:table.column>
                </flux:table.row>
            </flux:table.columns>
            
            <flux:table.rows>
                @forelse($orders as $order)
                    <flux:table.row wire:key="order-{{ $order->id }}">
                        <flux:table.cell>
                            <flux:text size="sm">
                                {{ $order->created_at->format('M d, Y') }}
                            </flux:text>
                            <flux:text size="xs" class="text-zinc-500">
                                {{ $order->created_at->format('g:i A') }}
                            </flux:text>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @if($order->contact)
                                <flux:text size="sm" class="font-medium">
                                    {{ $order->contact->name }}
                                </flux:text>
                                @if($order->contact->company)
                                    <flux:text size="xs" class="text-zinc-500">
                                        {{ $order->contact->company }}
                                    </flux:text>
                                @endif
                            @else
                                <flux:text size="sm" class="text-zinc-500">N/A</flux:text>
                            @endif
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ ucfirst($order->mail_type) }}
                            </flux:badge>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:text size="sm">
                                {{ Str::limit($order->description ?? 'Physical mail', 40) }}
                            </flux:text>
                            @if($order->mailable_type)
                                <flux:text size="xs" class="text-zinc-500">
                                    {{ class_basename($order->mailable_type) }} #{{ $order->mailable_id }}
                                </flux:text>
                            @endif
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:badge 
                                size="sm" 
                                :color="$this->getStatusColor($order->status)" 
                                :icon="$this->getStatusIcon($order->status)"
                                inset="top bottom"
                            >
                                {{ ucfirst($order->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @if($order->tracking_number)
                                <flux:text size="xs" class="font-mono">
                                    {{ $order->tracking_number }}
                                </flux:text>
                            @else
                                <flux:text size="sm" class="text-zinc-400">—</flux:text>
                            @endif
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:text size="sm" class="font-medium">
                                ${{ number_format($order->total_cost ?? 0, 2) }}
                            </flux:text>
                        </flux:table.cell>
                        
                        <flux:table.cell class="text-right">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" />
                                
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="viewDetails({{ $order->id }})">
                                        View Details
                                    </flux:menu.item>
                                    
                                    @if($order->pdf_url)
                                        <flux:menu.item icon="document-arrow-down" wire:click="downloadPdf({{ $order->id }})">
                                            Download PDF
                                        </flux:menu.item>
                                    @endif
                                    
                                    @if($order->tracking_url)
                                        <flux:menu.item icon="map-pin" href="{{ $order->tracking_url }}" target="_blank">
                                            Track Shipment
                                        </flux:menu.item>
                                    @endif
                                    
                                    <flux:menu.separator />
                                    
                                    @if(in_array($order->status, ['delivered', 'returned', 'failed']))
                                        <flux:menu.item icon="arrow-path" wire:click="resendMail({{ $order->id }})">
                                            Resend Mail
                                        </flux:menu.item>
                                    @endif
                                    
                                    @if(in_array($order->status, ['pending', 'processing']))
                                        <flux:menu.item 
                                            icon="x-circle" 
                                            wire:click="cancelOrder({{ $order->id }})"
                                            wire:confirm="Are you sure you want to cancel this mail order?"
                                            class="text-red-600"
                                        >
                                            Cancel Order
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-8">
                            <div class="flex flex-col items-center gap-2">
                                <flux:icon name="envelope" class="size-12 text-zinc-300" />
                                <flux:text size="sm" class="text-zinc-500">
                                    No physical mail history found
                                </flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        
        @if($orders->hasPages())
            <div class="mt-4 px-4 pb-4">
                {{ $orders->links() }}
            </div>
        @endif
    </flux:card>
    
    <!-- Include the send mail modal -->
    @livewire('physical-mail.send-mail-modal')
</div>