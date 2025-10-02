<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Time Entry Approval & Invoicing</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review and approve time entries, then generate invoices</p>
    </div>

    <flux:card class="mb-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <flux:select wire:model.live="selectedClient" label="Client" placeholder="All Clients">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="selectedTechnician" label="Technician" placeholder="All Technicians">
                    <option value="">All Technicians</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:input wire:model.live="startDate" type="date" label="Start Date" />
            </div>

            <div>
                <flux:input wire:model.live="endDate" type="date" label="End Date" />
            </div>
        </div>

        <div class="mt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full sm:w-auto">
                <flux:checkbox wire:model.live="billableOnly" label="Billable only" />
                
                <flux:select wire:model.live="groupBy" label="Group by" class="w-full sm:w-auto">
                    <option value="ticket">Ticket</option>
                    <option value="date">Date</option>
                    <option value="user">Technician</option>
                    <option value="none">None (Combined)</option>
                </flux:select>

                <flux:dropdown>
                    <flux:button size="sm" variant="ghost" icon="arrow-down-tray" class="w-full sm:w-auto">
                        Export
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="exportTimeEntries('csv')">CSV Format</flux:menu.item>
                        <flux:menu.item wire:click="exportTimeEntries('quickbooks_iif')">QuickBooks IIF</flux:menu.item>
                        <flux:menu.item wire:click="exportTimeEntries('xero')">Xero CSV</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>

            <div class="text-sm text-zinc-600 dark:text-zinc-400 w-full sm:w-auto text-left sm:text-right">
                <strong>{{ number_format($summary['total_hours'], 2) }}</strong> hours across 
                <strong>{{ $summary['total_entries'] }}</strong> entries
                @if($summary['selected_count'] > 0)
                    <br class="sm:hidden" />
                    <span class="sm:inline-block sm:ml-2">
                        <strong>{{ $summary['selected_count'] }}</strong> selected
                    </span>
                @endif
            </div>
        </div>
    </flux:card>

    @if($summary['selected_count'] > 0)
        <flux:card class="mb-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $summary['selected_count'] }} time entries selected
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <flux:button wire:click="bulkApprove" variant="primary" size="sm" class="w-full sm:w-auto">
                        Approve Selected
                    </flux:button>
                    <flux:button wire:click="bulkReject" variant="danger" size="sm" class="w-full sm:w-auto">
                        Reject Selected
                    </flux:button>
                    <flux:button wire:click="previewInvoice" variant="success" size="sm">
                        Preview Invoice
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @endif

    <flux:card>
        <flux:table>
            <flux:columns>
                <flux:column>
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:column>
                <flux:column>Date</flux:column>
                <flux:column>Client</flux:column>
                <flux:column>Ticket</flux:column>
                <flux:column>Technician</flux:column>
                <flux:column>Description</flux:column>
                <flux:column>Hours</flux:column>
                <flux:column>Status</flux:column>
                <flux:column>Actions</flux:column>
            </flux:columns>

            <flux:rows>
                @forelse($timeEntries as $entry)
                    <flux:row :key="$entry->id">
                        <flux:cell>
                            <flux:checkbox wire:model.live="selectedEntries" value="{{ $entry->id }}" />
                        </flux:cell>
                        <flux:cell>
                            {{ $entry->work_date->format('M d, Y') }}
                        </flux:cell>
                        <flux:cell>
                            @if($entry->ticket && $entry->ticket->client)
                                {{ $entry->ticket->client->name }}
                            @else
                                <span class="text-zinc-400">N/A</span>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            @if($entry->ticket)
                                <a href="{{ route('tickets.show', $entry->ticket) }}" class="text-blue-600 hover:text-blue-800">
                                    #{{ $entry->ticket->number }}
                                </a>
                            @else
                                <span class="text-zinc-400">N/A</span>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            {{ $entry->user->name ?? 'Unknown' }}
                        </flux:cell>
                        <flux:cell>
                            <div class="max-w-xs truncate" title="{{ $entry->description }}">
                                {{ $entry->description ?? 'No description' }}
                            </div>
                        </flux:cell>
                        <flux:cell>
                            {{ number_format($entry->hours_worked, 2) }}
                            @if($entry->billable)
                                <flux:badge color="green" size="sm">Billable</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Non-billable</flux:badge>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            @if($entry->approved_at)
                                <flux:badge color="green">Approved</flux:badge>
                            @elseif($entry->rejected_at)
                                <flux:badge color="red">Rejected</flux:badge>
                            @else
                                <flux:badge color="amber">Pending</flux:badge>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            <div class="flex gap-2">
                                @if(!$entry->approved_at && !$entry->rejected_at)
                                    <flux:button size="xs" variant="ghost" wire:click="$set('selectedEntries', [{{ $entry->id }}])">
                                        Select
                                    </flux:button>
                                @endif
                            </div>
                        </flux:cell>
                    </flux:row>
                @empty
                    <flux:row>
                        <flux:cell colspan="9">
                            <div class="py-8 text-center text-zinc-500">
                                No uninvoiced time entries found matching your filters.
                            </div>
                        </flux:cell>
                    </flux:row>
                @endforelse
            </flux:rows>
        </flux:table>

        <div class="mt-4">
            {{ $timeEntries->links() }}
        </div>
    </flux:card>

    @if($showPreview && $previewData)
        <flux:modal name="invoice-preview" variant="flyout" wire:model="showPreview">
            <form wire:submit="generateInvoice">
                <flux:heading size="lg">Invoice Preview</flux:heading>
                
                <flux:subheading class="mb-4">
                    Client: {{ $previewData['client']->name }}
                </flux:subheading>

                <div class="space-y-4">
                    <flux:card>
                        <flux:heading size="sm" class="mb-2">Summary</flux:heading>
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span>Time Entries:</span>
                                <strong>{{ $previewData['time_entry_count'] }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Total Hours:</span>
                                <strong>{{ number_format($previewData['total_hours'], 2) }}</strong>
                            </div>
                        </div>
                    </flux:card>

                    <flux:card>
                        <flux:heading size="sm" class="mb-2">Line Items</flux:heading>
                        <div class="space-y-2">
                            @foreach($previewData['items'] as $item)
                                <div class="border-b border-zinc-200 dark:border-zinc-700 pb-2">
                                    <div class="text-sm">{{ $item['description'] }}</div>
                                    <div class="text-xs text-zinc-600 dark:text-zinc-400 flex justify-between">
                                        <span>{{ number_format($item['quantity'], 2) }} hours × ${{ number_format($item['price'], 2) }}</span>
                                        <strong>${{ number_format($item['amount'], 2) }}</strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="flex justify-between text-lg font-semibold">
                                <span>Total:</span>
                                <span>${{ number_format($previewData['total'], 2) }}</span>
                            </div>
                        </div>
                    </flux:card>
                </div>

                <flux:button type="submit" variant="primary" class="mt-4">
                    Generate Invoice
                </flux:button>
                
                <flux:button type="button" variant="ghost" wire:click="closePreview" class="mt-4">
                    Cancel
                </flux:button>
            </form>
        </flux:modal>
    @endif
</div>
