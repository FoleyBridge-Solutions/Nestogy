<div class="space-y-6">
    {{-- Header Section --}}
    <flux:card>
        <div class="flex items-center justify-between p-6">
            <div class="flex items-center gap-4">
                <flux:avatar size="lg">
                    {{ substr($client->name, 0, 2) }}
                </flux:avatar>
                <div>
                    <h1 class="text-2xl font-bold">{{ $client->name }}</h1>
                    <div class="flex items-center gap-4 mt-1">
                        <flux:badge variant="{{ $client->is_active ? 'success' : 'danger' }}">
                            {{ $client->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                        <flux:badge variant="outline">
                            {{ ucfirst($client->type ?? 'Individual') }}
                        </flux:badge>
                        @if($client->lead)
                            <flux:badge variant="warning">Lead</flux:badge>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <flux:button variant="outline" href="{{ route('clients.edit', $client) }}">
                    <flux:icon.pencil class="size-4" />
                    Edit
                </flux:button>
                
                <flux:dropdown>
                    <flux:button variant="ghost">
                        <flux:icon.ellipsis-horizontal class="size-4" />
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="archiveClient">
                            <flux:icon.archive-box class="size-4" />
                            Archive
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item wire:click="deleteClient" variant="danger">
                            <flux:icon.trash class="size-4" />
                            Delete
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </flux:card>

    {{-- Metrics Section --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <flux:card>
            <div class="p-4">
                <div class="text-2xl font-bold">{{ $metrics['openTickets'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Open Tickets</div>
            </div>
        </flux:card>
        
        <flux:card>
            <div class="p-4">
                <div class="text-2xl font-bold">${{ number_format($metrics['monthlyRecurring'] ?? 0, 2) }}</div>
                <div class="text-sm text-gray-500">Monthly Recurring</div>
            </div>
        </flux:card>
        
        <flux:card>
            <div class="p-4">
                <div class="text-2xl font-bold">${{ number_format($metrics['totalRevenue'] ?? 0, 2) }}</div>
                <div class="text-sm text-gray-500">Total Revenue</div>
            </div>
        </flux:card>
        
        <flux:card>
            <div class="p-4">
                <div class="text-2xl font-bold">{{ $metrics['activeAssets'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Active Assets</div>
            </div>
        </flux:card>
    </div>

    {{-- Tabs Section --}}
    <flux:tab.group default="overview">
        <flux:tabs>
            <flux:tab name="overview">Overview</flux:tab>
            <flux:tab name="contacts">Contacts ({{ $client->contacts->count() }})</flux:tab>
            <flux:tab name="locations">Locations ({{ $client->locations->count() }})</flux:tab>
            <flux:tab name="assets">Assets ({{ $client->assets->count() }})</flux:tab>
            <flux:tab name="tickets">Tickets ({{ $client->tickets->count() }})</flux:tab>
            <flux:tab name="invoices">Invoices ({{ $client->invoices->count() }})</flux:tab>
            <flux:tab name="projects">Projects ({{ $client->projects->count() }})</flux:tab>
        </flux:tabs>

        {{-- Tab Panels --}}
        <flux:tab.panel name="overview">
            <flux:card>
                <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Client Information --}}
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Client Information</h3>
                        <dl class="space-y-2">
                            @if($client->email)
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Email:</dt>
                                    <dd class="text-sm">{{ $client->email }}</dd>
                                </div>
                            @endif
                            @if($client->phone)
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Phone:</dt>
                                    <dd class="text-sm">{{ $client->phone }}</dd>
                                </div>
                            @endif
                            @if($client->website)
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Website:</dt>
                                    <dd class="text-sm">
                                        <a href="{{ $client->website }}" target="_blank" class="text-blue-600 hover:underline">
                                            {{ $client->website }}
                                        </a>
                                    </dd>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Created:</dt>
                                <dd class="text-sm">{{ $client->created_at?->format('M d, Y') ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Recent Activity --}}
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
                        @if($recentActivity && count($recentActivity) > 0)
                            <div class="space-y-2">
                                @foreach($recentActivity as $activity)
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="text-gray-500">{{ $activity['time'] ?? '' }}</span>
                                        <span>{{ $activity['description'] ?? '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No recent activity</p>
                        @endif
                    </div>
                </div>
                </div>
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="contacts">
            <flux:card>
                <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Contacts</h3>
                    <flux:button variant="primary" size="sm" wire:click="createContact">
                        <flux:icon.plus class="size-4" />
                        Add Contact
                    </flux:button>
                </div>
                
                @if($client->contacts->count() > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Name</flux:table.column>
                            <flux:table.column>Email</flux:table.column>
                            <flux:table.column>Phone</flux:table.column>
                            <flux:table.column>Primary</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($client->contacts as $contact)
                                <flux:table.row>
                                    <flux:table.cell>{{ $contact->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $contact->email }}</flux:table.cell>
                                    <flux:table.cell>{{ $contact->phone }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if($contact->primary)
                                            <flux:badge variant="success">Primary</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button variant="ghost" size="sm">
                                            <flux:icon.pencil class="size-4" />
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <p class="text-gray-500">No contacts found</p>
                @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="locations">
            <flux:card>
                <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Locations</h3>
                    <flux:button variant="primary" size="sm" wire:click="createLocation">
                        <flux:icon.plus class="size-4" />
                        Add Location
                    </flux:button>
                </div>
                
                @if($client->locations->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($client->locations as $location)
                            <flux:card>
                                <div class="p-4">
                                    <div class="font-semibold">{{ $location->name }}</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        {{ $location->address }}<br>
                                        {{ $location->city }}, {{ $location->state }} {{ $location->zip }}
                                    </div>
                                    @if($location->primary)
                                        <flux:badge variant="success" size="sm" class="mt-2">Primary</flux:badge>
                                    @endif
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">No locations found</p>
                @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="assets">
            <flux:card>
                <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Assets</h3>
                    <flux:button variant="primary" size="sm" href="{{ route('assets.create', ['client_id' => $client->id]) }}">
                        <flux:icon.plus class="size-4" />
                        Add Asset
                    </flux:button>
                </div>
                
                @if($client->assets->count() > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Name</flux:table.column>
                            <flux:table.column>Type</flux:table.column>
                            <flux:table.column>Serial Number</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($client->assets as $asset)
                                <flux:table.row>
                                    <flux:table.cell>{{ $asset->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $asset->type }}</flux:table.cell>
                                    <flux:table.cell>{{ $asset->serial_number ?? '-' }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge variant="{{ $asset->status === 'active' ? 'success' : 'outline' }}">
                                            {{ ucfirst($asset->status) }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button variant="ghost" size="sm" href="{{ route('assets.show', $asset) }}">
                                            <flux:icon.eye class="size-4" />
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <p class="text-gray-500">No assets found</p>
                @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="tickets">
            <flux:card>
                <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Recent Tickets</h3>
                    <flux:button variant="primary" size="sm" wire:click="createTicket">
                        <flux:icon.plus class="size-4" />
                        Create Ticket
                    </flux:button>
                </div>
                
                @if($client->tickets->count() > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>#</flux:table.column>
                            <flux:table.column>Subject</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Priority</flux:table.column>
                            <flux:table.column>Created</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($client->tickets as $ticket)
                                <flux:table.row>
                                    <flux:table.cell>{{ $ticket->ticket_number }}</flux:table.cell>
                                    <flux:table.cell>{{ $ticket->subject }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge variant="{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'closed' ? 'success' : 'info') }}">
                                            {{ ucfirst($ticket->status) }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge variant="{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'outline') }}">
                                            {{ ucfirst($ticket->priority) }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $ticket->created_at?->format('M d, Y') ?? '-' }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button variant="ghost" size="sm" href="{{ route('tickets.show', $ticket) }}">
                                            <flux:icon.eye class="size-4" />
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <p class="text-gray-500">No tickets found</p>
                @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="invoices">
            <flux:card>
                <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Recent Invoices</h3>
                    <flux:button variant="primary" size="sm" href="{{ route('financial.invoices.create', ['client_id' => $client->id]) }}">
                        <flux:icon.plus class="size-4" />
                        Create Invoice
                    </flux:button>
                </div>
                
                @if($client->invoices->count() > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Invoice #</flux:table.column>
                            <flux:table.column>Date</flux:table.column>
                            <flux:table.column>Due Date</flux:table.column>
                            <flux:table.column>Amount</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($client->invoices as $invoice)
                                <flux:table.row>
                                    <flux:table.cell>{{ $invoice->invoice_number }}</flux:table.cell>
                                    <flux:table.cell>{{ $invoice->invoice_date?->format('M d, Y') ?? '-' }}</flux:table.cell>
                                    <flux:table.cell>{{ $invoice->due_date?->format('M d, Y') ?? '-' }}</flux:table.cell>
                                    <flux:table.cell>${{ number_format($invoice->total ?? 0, 2) }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge variant="{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($invoice->status) }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button variant="ghost" size="sm" href="{{ route('financial.invoices.show', $invoice) }}">
                                            <flux:icon.eye class="size-4" />
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <p class="text-gray-500">No invoices found</p>
                @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="projects">
            <flux:card>
                <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Projects</h3>
                    <flux:button variant="primary" size="sm" wire:click="createProject">
                        <flux:icon.plus class="size-4" />
                        Create Project
                    </flux:button>
                </div>
                
                @if($client->projects->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($client->projects as $project)
                            <flux:card>
                                <div class="p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold">{{ $project->name }}</div>
                                            <div class="text-sm text-gray-500 mt-1">{{ $project->description }}</div>
                                        </div>
                                        <flux:badge variant="{{ $project->status === 'completed' ? 'success' : ($project->status === 'in_progress' ? 'info' : 'outline') }}">
                                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                        </flux:badge>
                                    </div>
                                    <div class="flex justify-between items-center mt-3">
                                        <span class="text-xs text-gray-500">Due: {{ $project->due_date?->format('M d, Y') ?? 'No due date' }}</span>
                                        <flux:button variant="ghost" size="sm" href="{{ route('projects.show', $project) }}">
                                            View
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">No projects found</p>
                @endif
                </div>
            </flux:card>
        </flux:tab.panel>
    </flux:tab.group>
</div>