<div>
    <flux:heading size="xl" class="mb-6">Ticket Billing Configuration</flux:heading>

    <div class="space-y-6">
        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:subheading>Pending Tickets</flux:subheading>
                        <div class="text-3xl font-bold text-blue-600">{{ number_format($pendingTicketsCount) }}</div>
                        <p class="text-sm text-gray-500 mt-1">Unbilled closed/resolved tickets</p>
                    </div>
                    <flux:icon.document-currency-dollar class="size-12 text-blue-600 opacity-20" />
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:subheading>Queue Jobs</flux:subheading>
                        <div class="text-3xl font-bold text-green-600">{{ number_format($billingQueueCount) }}</div>
                        <p class="text-sm text-gray-500 mt-1">Billing jobs in queue</p>
                    </div>
                    <flux:icon.queue-list class="size-12 text-green-600 opacity-20" />
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:subheading>System Status</flux:subheading>
                        <div class="text-3xl font-bold {{ $enabled ? 'text-green-600' : 'text-red-600' }}">
                            {{ $enabled ? 'ENABLED' : 'DISABLED' }}
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Billing system status</p>
                    </div>
                    <flux:icon.{{ $enabled ? 'check-circle' : 'x-circle' }} class="size-12 {{ $enabled ? 'text-green-600' : 'text-red-600' }} opacity-20" />
                </div>
            </flux:card>
        </div>

        {{-- Quick Actions --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>
            
            <div class="flex gap-3">
                @can('processPendingTickets', App\Policies\TicketBillingPolicy::class)
                    <flux:button 
                        wire:click="processPendingTickets" 
                        :disabled="processing || !enabled"
                        variant="primary"
                        icon="play">
                        {{ processing ? 'Processing...' : 'Process Pending Tickets' }}
                    </flux:button>
                @endcan

                @can('runDryRun', App\Policies\TicketBillingPolicy::class)
                    <flux:button 
                        wire:click="testDryRun" 
                        variant="ghost"
                        icon="eye">
                        Dry Run (Preview)
                    </flux:button>
                @endcan

                <flux:button 
                    wire:click="$refresh" 
                    variant="ghost"
                    icon="arrow-path">
                    Refresh Stats
                </flux:button>
            </div>

            @if($pendingTicketsCount > 0)
                <flux:callout variant="info" class="mt-4">
                    {{ number_format($pendingTicketsCount) }} ticket(s) are ready to be billed. Click "Process Pending Tickets" to queue them for billing.
                </flux:callout>
            @endif
        </flux:card>

        <form wire:submit="save">
            {{-- Master Control --}}
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">Master Control</flux:heading>
                
                <flux:field>
                    <flux:checkbox wire:model="enabled" label="Enable Ticket Billing System" />
                    <flux:description>
                        Master switch for the entire ticket billing system. When disabled, no automatic billing will occur.
                    </flux:description>
                </flux:field>

                @if(!$enabled)
                    <flux:callout variant="warning" class="mt-4">
                        The billing system is currently disabled. No automatic billing will occur.
                    </flux:callout>
                @endif
            </flux:card>

            {{-- Auto-Billing Triggers --}}
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">Auto-Billing Triggers</flux:heading>
                
                <flux:callout variant="warning" class="mb-4">
                    <strong>Recommended:</strong> Keep these disabled initially and use manual processing to test the system.
                </flux:callout>

                <div class="space-y-4">
                    <flux:field>
                        <flux:checkbox 
                            wire:model="autoBillOnClose" 
                            label="Auto-Bill When Ticket is Closed"
                            :disabled="!enabled" />
                        <flux:description>
                            Automatically queue billing when a ticket's status changes to "Closed".
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:checkbox 
                            wire:model="autoBillOnResolve" 
                            label="Auto-Bill When Ticket is Resolved"
                            :disabled="!enabled" />
                        <flux:description>
                            Automatically queue billing when a ticket is marked as resolved (earlier than "Closed").
                        </flux:description>
                    </flux:field>
                </div>
            </flux:card>

            {{-- Billing Strategy --}}
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">Billing Strategy</flux:heading>
                
                <flux:field>
                    <flux:label>Default Billing Strategy</flux:label>
                    <flux:radio.group wire:model="defaultStrategy">
                        <flux:radio value="time_based" label="Time-Based" description="Bill based on tracked time entries (hourly)" />
                        <flux:radio value="per_ticket" label="Per-Ticket" description="Use fixed per-ticket rate from contract" />
                        <flux:radio value="mixed" label="Mixed" description="Combine time entries + per-ticket rate" />
                    </flux:radio.group>
                    <flux:description>
                        The system automatically detects the best strategy based on available data. This is used as a fallback.
                    </flux:description>
                </flux:field>
            </flux:card>

            {{-- Time & Rounding --}}
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">Time & Rounding Settings</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Minimum Billable Hours</flux:label>
                        <flux:input type="number" wire:model="minBillableHours" step="0.25" min="0" max="24" />
                        <flux:description>
                            Minimum hours to charge (e.g., 0.25 = 15 minutes minimum)
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Round Hours To</flux:label>
                        <flux:select wire:model="roundHoursTo">
                            <option value="0.25">15 minutes (0.25)</option>
                            <option value="0.5">30 minutes (0.5)</option>
                            <option value="1.0">1 hour (1.0)</option>
                        </flux:select>
                        <flux:description>
                            Round billable hours to nearest increment
                        </flux:description>
                    </flux:field>
                </div>
            </flux:card>

            {{-- Invoice Settings --}}
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">Invoice Settings</flux:heading>
                
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Invoice Due Days</flux:label>
                        <flux:input type="number" wire:model="invoiceDueDays" min="1" max="365" />
                        <flux:description>
                            Number of days until generated invoices are due
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="requireApproval" label="Require Manual Approval" />
                        <flux:description>
                            When enabled, generated invoices will be marked as drafts requiring manual approval before being sent.
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="skipZeroInvoices" label="Skip Zero Amount Invoices" />
                        <flux:description>
                            Don't create invoices with $0 amount.
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="autoSend" label="Auto-Send Invoices" :disabled="requireApproval" />
                        <flux:description>
                            Automatically send invoices to clients after generation (only works if approval is not required).
                        </flux:description>
                    </flux:field>
                </div>
            </flux:card>

            {{-- Processing Settings --}}
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">Processing Settings</flux:heading>
                
                <flux:field>
                    <flux:label>Batch Size</flux:label>
                    <flux:input type="number" wire:model="batchSize" min="1" max="1000" />
                    <flux:description>
                        Number of tickets to process per batch in scheduled tasks (recommended: 100-200)
                    </flux:description>
                </flux:field>
            </flux:card>

            {{-- Save Button --}}
            @can('manageSettings', App\Policies\TicketBillingPolicy::class)
                <div class="flex justify-end gap-3">
                    <flux:button type="submit" variant="primary">
                        Save Configuration
                    </flux:button>
                </div>
            @else
                <flux:callout variant="info" class="mt-4">
                    You have view-only access to billing settings. Contact an administrator to make changes.
                </flux:callout>
            @endcan
        </form>
    </div>
</div>
