<div>
    {{-- Only show stats if we have data --}}
    @if($this->invoices->total() > 0)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @if($this->totals['draft'] > 0)
            <flux:card>
                <div class="p-4">
                    <flux:text variant="muted" size="sm">Draft</flux:text>
                    <flux:heading size="lg">${{ number_format($this->totals['draft'], 2) }}</flux:heading>
                </div>
            </flux:card>
            @endif

            @if($this->totals['sent'] > 0)
            <flux:card>
                <div class="p-4">
                    <flux:text variant="muted" size="sm">Awaiting Payment</flux:text>
                    <flux:heading size="lg">${{ number_format($this->totals['sent'], 2) }}</flux:heading>
                </div>
            </flux:card>
            @endif

            @if($this->totals['paid'] > 0)
            <flux:card>
                <div class="p-4">
                    <flux:text variant="muted" size="sm">Paid</flux:text>
                    <flux:heading size="lg" class="text-green-600">${{ number_format($this->totals['paid'], 2) }}</flux:heading>
                </div>
            </flux:card>
            @endif

            @if($this->totals['overdue'] > 0)
            <flux:card>
                <div class="p-4">
                    <flux:text variant="muted" size="sm">Overdue</flux:text>
                    <flux:heading size="lg" class="text-red-600">${{ number_format($this->totals['overdue'], 2) }}</flux:heading>
                </div>
            </flux:card>
            @endif
        </div>
    @endif

    {{-- Simple Search and Filter Bar --}}
    <div class="flex gap-4 mb-6">
        <flux:input 
            wire:model.live.debounce.300ms="search"
            placeholder="Search invoices..."
            class="flex-1 max-w-md"
        />
        
        <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="Draft">Draft</flux:select.option>
            <flux:select.option value="Sent">Sent</flux:select.option>
            <flux:select.option value="Paid">Paid</flux:select.option>
            <flux:select.option value="Overdue">Overdue</flux:select.option>
        </flux:select>
    </div>

    {{-- Invoices Table --}}
    <flux:card>
        @if($this->invoices->count() > 0)
            <flux:table :paginate="$this->invoices">
                <flux:table.columns>
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'number'" 
                        :direction="$sortDirection" 
                        wire:click="sort('number')"
                    >
                        Invoice #
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'client'" 
                        :direction="$sortDirection" 
                        wire:click="sort('client')"
                    >
                        Client
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'amount'" 
                        :direction="$sortDirection" 
                        wire:click="sort('amount')"
                    >
                        Amount
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'status'" 
                        :direction="$sortDirection" 
                        wire:click="sort('status')"
                    >
                        Status
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'date'" 
                        :direction="$sortDirection" 
                        wire:click="sort('date')"
                    >
                        Date
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'due_date'" 
                        :direction="$sortDirection" 
                        wire:click="sort('due_date')"
                    >
                        Due Date
                    </flux:table.column>
                    
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->invoices as $invoice)
                        <flux:table.row :key="$invoice->id">
                            <flux:table.cell>
                                <div>
                                    <flux:text variant="strong">{{ $invoice->prefix }}{{ $invoice->number }}</flux:text>
                                    @if($invoice->scope)
                                        <flux:text size="sm" variant="muted" class="block">{{ Str::limit($invoice->scope, 30) }}</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @if($invoice->client)
                                    <div>
                                        <flux:text>{{ $invoice->client->name }}</flux:text>
                                        @if($invoice->client->company_name)
                                            <flux:text size="sm" variant="muted" class="block">{{ $invoice->client->company_name }}</flux:text>
                                        @endif
                                    </div>
                                @else
                                    <flux:text variant="muted">-</flux:text>
                                @endif
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <flux:text variant="strong">${{ number_format($invoice->amount, 2) }}</flux:text>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @php
                                    $statusColor = $this->statusColor[$invoice->status] ?? 'zinc';
                                    if ($invoice->status === 'Sent' && $invoice->due_date && \Carbon\Carbon::parse($invoice->due_date)->isPast()) {
                                        $statusColor = 'amber';
                                        $displayStatus = 'Overdue';
                                    } else {
                                        $displayStatus = $invoice->status;
                                    }
                                @endphp
                                <flux:badge size="sm" :color="$statusColor" inset="top bottom">
                                    {{ $displayStatus }}
                                </flux:badge>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                {{ \Carbon\Carbon::parse($invoice->date)->format('M d, Y') }}
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @if($invoice->due_date)
                                    <div class="flex items-center gap-2">
                                        {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}
                                        @if($invoice->status === 'Sent' && \Carbon\Carbon::parse($invoice->due_date)->isPast())
                                            <flux:icon name="exclamation-triangle" variant="mini" class="text-amber-500" />
                                        @endif
                                    </div>
                                @else
                                    <flux:text variant="muted">-</flux:text>
                                @endif
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <div class="flex items-center gap-1">
                                    <flux:button 
                                        href="{{ route('financial.invoices.show', $invoice) }}"
                                        size="sm"
                                        variant="ghost"
                                        icon="eye"
                                        title="View"
                                    />
                                    
                                    @if($invoice->status === 'Draft')
                                        <flux:button 
                                            href="{{ route('financial.invoices.edit', $invoice) }}"
                                            size="sm"
                                            variant="ghost"
                                            icon="pencil"
                                            title="Edit"
                                        />
                                        
                                        <flux:button 
                                            wire:click="markAsSent({{ $invoice->id }})"
                                            wire:confirm="Mark this invoice as sent?"
                                            size="sm"
                                            variant="ghost"
                                            icon="paper-airplane"
                                            class="text-blue-600 hover:text-blue-700"
                                            title="Mark as Sent"
                                        />
                                    @endif
                                    
                                    @if($invoice->status === 'Sent')
                                        <flux:button 
                                            wire:click="markAsPaid({{ $invoice->id }})"
                                            wire:confirm="Mark this invoice as paid?"
                                            size="sm"
                                            variant="ghost"
                                            icon="check"
                                            class="text-green-600 hover:text-green-700"
                                            title="Mark as Paid"
                                        />
                                    @endif
                                    
                                    @if(in_array($invoice->status, ['Draft', 'Sent']))
                                        <flux:dropdown align="end">
                                            <flux:button 
                                                size="sm"
                                                variant="ghost"
                                                icon="ellipsis-vertical"
                                            />
                                            
                                            <flux:menu>
                                                @if($invoice->status === 'Draft')
                                                    <flux:menu.item 
                                                        wire:click="deleteInvoice({{ $invoice->id }})"
                                                        wire:confirm="Are you sure you want to delete this invoice? This action cannot be undone."
                                                        icon="trash"
                                                        variant="danger"
                                                    >
                                                        Delete
                                                    </flux:menu.item>
                                                @endif
                                                
                                                <flux:menu.item 
                                                    wire:click="cancelInvoice({{ $invoice->id }})"
                                                    wire:confirm="Are you sure you want to cancel this invoice?"
                                                    icon="x-circle"
                                                >
                                                    Cancel Invoice
                                                </flux:menu.item>
                                                
                                <flux:menu.separator />
                                
                                <flux:menu.item 
                                    href="{{ route('financial.invoices.pdf', $invoice) }}"
                                    icon="arrow-down-tray"
                                >
                                    Download PDF
                                </flux:menu.item>
                                
                                @if($invoice->status !== 'Draft')
                                    <flux:menu.item 
                                        href="{{ route('financial.invoices.send', $invoice) }}"
                                        icon="envelope"
                                    >
                                        Send Email
                                    </flux:menu.item>
                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <div class="text-center py-16">
                <div class="max-w-md mx-auto">
                    <flux:icon name="document-text" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-6" />
                    @if($search || $statusFilter || $dateFrom || $dateTo)
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No results found</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">Try adjusting your filters or search terms</p>
                        <flux:button variant="ghost" wire:click="$set('search', ''); $set('statusFilter', ''); $set('dateFrom', ''); $set('dateTo', '');">
                            Clear all filters
                        </flux:button>
                    @else
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">Create your first invoice</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">Start billing your clients by creating an invoice</p>
                        <flux:button variant="primary" href="{{ route('financial.invoices.create') }}">
                            Create Invoice
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:card>
</div>
