<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Pay Periods</flux:heading>
            <flux:subheading>Manage payroll periods and approvals</flux:subheading>
        </div>

        <div class="flex items-center space-x-2">
            <flux:button wire:click="generateNextPeriod" variant="outline" icon="plus">
                Generate Next Period
            </flux:button>
            <flux:button wire:click="$set('showCreateModal', true)" variant="primary" icon="plus">
                Create Pay Period
            </flux:button>
        </div>
    </div>

    <div class="mb-4">
        <flux:tab.group>
            <flux:tabs wire:model.live="filterStatus">
                <flux:tab name="all">All</flux:tab>
                <flux:tab name="open">Open</flux:tab>
                <flux:tab name="approved">Approved</flux:tab>
                <flux:tab name="paid">Paid</flux:tab>
            </flux:tabs>
            
            <!-- Hidden panels - content is controlled by Livewire filter property -->
            <flux:tab.panel name="all" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="open" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="approved" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="paid" class="hidden"></flux:tab.panel>
        </flux:tab.group>
    </div>

    <div class="grid gap-4">
        @forelse($this->payPeriods() as $period)
        <flux:card>
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <flux:heading size="lg">{{ $period->getLabel() }}</flux:heading>
                        <flux:badge color="{{ $period->status === 'paid' ? 'green' : ($period->status === 'approved' ? 'blue' : 'zinc') }}">
                            {{ ucfirst($period->status) }}
                        </flux:badge>
                    </div>
                    <div class="mt-2 flex items-center space-x-6 text-sm text-zinc-600 dark:text-zinc-400">
                        <span>Frequency: {{ ucfirst($period->frequency) }}</span>
                        <span>Total Hours: {{ $period->getTotalHours() }}</span>
                        @if($period->approved_at)
                        <span>Approved: {{ $period->approved_at->format('M d, Y') }}</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    @if($period->isOpen())
                    <flux:button wire:click="approvePayPeriod({{ $period->id }})" 
                               wire:confirm="Approve this pay period?"
                               variant="primary" 
                               size="sm">
                        Approve
                    </flux:button>
                    @endif

                    @if($period->isApproved())
                    <flux:button wire:click="markAsPaid({{ $period->id }})" 
                               wire:confirm="Mark this pay period as paid?"
                               variant="primary" 
                               size="sm">
                        Mark as Paid
                    </flux:button>
                    @endif

                    @if($period->timeEntries()->count() === 0)
                    <flux:button wire:click="deletePayPeriod({{ $period->id }})" 
                               wire:confirm="Delete this pay period?"
                               variant="danger" 
                               size="sm" 
                               icon="trash">
                    </flux:button>
                    @endif
                </div>
            </div>
        </flux:card>
        @empty
        <flux:card>
            <div class="text-center py-8 text-zinc-500">
                No pay periods found. Create one to get started.
            </div>
        </flux:card>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $this->payPeriods()->links() }}
    </div>

    @if($showCreateModal)
    <flux:modal wire:model="showCreateModal" name="create-pay-period">
        <form wire:submit="createPayPeriod">
            <flux:heading size="lg">Create Pay Period</flux:heading>

            <div class="space-y-4 mt-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="form.start_date" type="date" label="Start Date" required />
                    <flux:input wire:model="form.end_date" type="date" label="End Date" required />
                </div>

                <flux:select wire:model="form.frequency" label="Frequency" required>
                    <option value="weekly">Weekly</option>
                    <option value="biweekly">Biweekly</option>
                    <option value="semimonthly">Semi-monthly</option>
                    <option value="monthly">Monthly</option>
                </flux:select>

                <flux:textarea wire:model="form.notes" label="Notes" rows="3" />
            </div>

            <flux:button type="submit" variant="primary" class="mt-6">Create Pay Period</flux:button>
        </form>
    </flux:modal>
    @endif
</div>
