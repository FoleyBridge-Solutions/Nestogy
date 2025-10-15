<div>
    @if($this->credits->total() > 0)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @if($this->totals['total_amount'] > 0)
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <flux:icon name="ticket" variant="solid" class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <flux:text variant="muted" size="sm">Total Credits</flux:text>
                    <flux:heading size="lg" class="text-blue-600">${{ number_format($this->totals['total_amount'], 2) }}</flux:heading>
                </div>
            </flux:card>
            @endif

            @if($this->totals['available_amount'] > 0)
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <flux:icon name="currency-dollar" variant="solid" class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <flux:text variant="muted" size="sm">Available</flux:text>
                    <flux:heading size="lg" class="text-green-600">${{ number_format($this->totals['available_amount'], 2) }}</flux:heading>
                </div>
            </flux:card>
            @endif

            @if($this->totals['active_count'] > 0)
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center">
                            <flux:icon name="check-circle" variant="solid" class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <flux:text variant="muted" size="sm">Active</flux:text>
                    <flux:heading size="lg">{{ $this->totals['active_count'] }}</flux:heading>
                </div>
            </flux:card>
            @endif

            @if($this->totals['expired_count'] > 0)
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-10 h-10 bg-amber-500 rounded-full flex items-center justify-center">
                            <flux:icon name="clock" variant="solid" class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <flux:text variant="muted" size="sm">Expired</flux:text>
                    <flux:heading size="lg">{{ $this->totals['expired_count'] }}</flux:heading>
                </div>
            </flux:card>
            @endif
        </div>
    @endif

    <div class="flex flex-wrap gap-4 mb-6">
        <flux:input 
            wire:model.live.debounce.300ms="search"
            placeholder="Search credits..."
            class="flex-1 max-w-md"
        />
        
        <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="depleted">Depleted</flux:select.option>
            <flux:select.option value="expired">Expired</flux:select.option>
            <flux:select.option value="voided">Voided</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="typeFilter" placeholder="All Types">
            <flux:select.option value="">All Types</flux:select.option>
            <flux:select.option value="overpayment">Overpayment</flux:select.option>
            <flux:select.option value="refund">Refund</flux:select.option>
            <flux:select.option value="promotional">Promotional</flux:select.option>
            <flux:select.option value="goodwill">Goodwill</flux:select.option>
            <flux:select.option value="adjustment">Adjustment</flux:select.option>
        </flux:select>
    </div>

    @if(count($selected) > 0)
    <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-center justify-between">
            <flux:text>
                <span class="font-semibold">{{ count($selected) }}</span> credit(s) selected
            </flux:text>
            
            <div class="flex items-center gap-2">
                <flux:button wire:click="bulkVoid" size="sm" variant="danger">
                    Void Selected
                </flux:button>
            </div>
        </div>
    </div>
    @endif

    <flux:card>
        @if($this->credits->count() > 0)
            <flux:table :paginate="$this->credits">
                <flux:table.columns>
                    <flux:table.column class="w-12">
                        <flux:checkbox wire:model.live="selectAll" />
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
                        :sorted="$sortBy === 'type'" 
                        :direction="$sortDirection" 
                        wire:click="sort('type')"
                    >
                        Type
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'amount'" 
                        :direction="$sortDirection" 
                        wire:click="sort('amount')"
                    >
                        Amount
                    </flux:table.column>
                    
                    <flux:table.column>
                        Available
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'status'" 
                        :direction="$sortDirection" 
                        wire:click="sort('status')"
                    >
                        Status
                    </flux:table.column>
                    
                    <flux:table.column>
                        Expiry Date
                    </flux:table.column>
                    
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->credits as $credit)
                        <flux:table.row :key="$credit->id">
                            <flux:table.cell>
                                <flux:checkbox wire:model.live="selected" :value="$credit->id" />
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @if($credit->client)
                                    <div>
                                        <flux:text variant="strong">{{ $credit->client->name }}</flux:text>
                                        @if($credit->client->company_name)
                                            <flux:text size="sm" variant="muted" class="block">{{ $credit->client->company_name }}</flux:text>
                                        @endif
                                    </div>
                                @else
                                    <flux:text variant="muted">-</flux:text>
                                @endif
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <div>
                                    <flux:text>{{ ucfirst(str_replace('_', ' ', $credit->type)) }}</flux:text>
                                    @if($credit->reason)
                                        <flux:text size="xs" variant="muted" class="block">{{ Str::limit($credit->reason, 30) }}</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <flux:text variant="strong">${{ number_format($credit->amount, 2) }}</flux:text>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <div>
                                    <flux:text variant="strong" class="{{ $credit->available_amount > 0 ? 'text-green-600' : 'text-zinc-500' }}">
                                        ${{ number_format($credit->available_amount, 2) }}
                                    </flux:text>
                                    @if($credit->applications->count() > 0)
                                        <flux:text size="xs" variant="muted" class="block">
                                            {{ $credit->applications->count() }} {{ Str::plural('application', $credit->applications->count()) }}
                                        </flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @php
                                    $statusColor = $this->statusColor[$credit->status] ?? 'zinc';
                                @endphp
                                <flux:badge size="sm" :color="$statusColor" inset="top bottom">
                                    {{ ucfirst($credit->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @if($credit->expiry_date)
                                    <div>
                                        <flux:text size="sm" class="{{ $credit->expiry_date->isPast() ? 'text-red-600 font-medium' : '' }}">
                                            {{ $credit->expiry_date->format('M d, Y') }}
                                        </flux:text>
                                        @if($credit->expiry_date->isPast() && $credit->status === 'active')
                                            <flux:icon name="exclamation-triangle" class="size-3 inline ml-1 text-red-500" />
                                        @endif
                                    </div>
                                @else
                                    <flux:text variant="muted" size="sm">No expiry</flux:text>
                                @endif
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <div class="flex items-center gap-1">
                                    <flux:button 
                                        href="{{ route('financial.credits.show', $credit) }}"
                                        size="sm"
                                        variant="ghost"
                                        icon="eye"
                                        title="View"
                                    />
                                    
                                    @if($credit->status === 'active')
                                        <flux:button 
                                            wire:click="voidCredit({{ $credit->id }})"
                                            wire:confirm="Are you sure you want to void this credit? This action cannot be undone."
                                            size="sm"
                                            variant="ghost"
                                            icon="x-circle"
                                            class="text-red-600 hover:text-red-700"
                                            title="Void"
                                        />
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
                    <flux:icon name="ticket" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-6" />
                    @if($search || $statusFilter || $typeFilter)
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No credits found</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">Try adjusting your filters or search terms</p>
                        <flux:button variant="ghost" wire:click="$set('search', ''); $set('statusFilter', ''); $set('typeFilter', '');">
                            Clear all filters
                        </flux:button>
                    @else
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No credits found</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">Credits are created automatically from overpayments or can be created manually.</p>
                        <flux:button variant="primary" href="{{ route('financial.credits.create') }}">
                            Create Credit
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:card>
</div>
