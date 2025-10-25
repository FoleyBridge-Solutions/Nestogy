<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Time Off Requests</flux:heading>
            <flux:subheading>Request time off and view request history</flux:subheading>
        </div>

        <flux:button wire:click="$set('showCreateModal', true)" variant="primary" icon="plus">
            New Request
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:tab.group>
            <flux:tabs wire:model.live="filterStatus">
                <flux:tab name="all">All</flux:tab>
                <flux:tab name="pending">Pending</flux:tab>
                <flux:tab name="approved">Approved</flux:tab>
                <flux:tab name="denied">Denied</flux:tab>
            </flux:tabs>
            
            <!-- Hidden panels - content is controlled by Livewire filter property -->
            <flux:tab.panel name="all" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="pending" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="approved" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="denied" class="hidden"></flux:tab.panel>
        </flux:tab.group>
    </div>

    <div class="grid gap-4">
        @forelse($this->requests as $request)
        <flux:card>
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <flux:heading size="lg">{{ $request->getTypeLabel() }}</flux:heading>
                        <flux:badge color="{{ $request->status === 'approved' ? 'green' : ($request->status === 'denied' ? 'red' : 'yellow') }}">
                            {{ ucfirst($request->status) }}
                        </flux:badge>
                    </div>
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <div>{{ $request->start_date->format('M d, Y') }} - {{ $request->end_date->format('M d, Y') }}</div>
                        <div>Duration: {{ $request->getDurationDays() }} day(s) ({{ $request->total_hours }} hours)</div>
                        @if($request->reason)
                        <div class="mt-1">Reason: {{ $request->reason }}</div>
                        @endif
                        @if($request->reviewed_at)
                        <div class="mt-1">
                            Reviewed by {{ $request->reviewedBy->name }} on {{ $request->reviewed_at->format('M d, Y') }}
                            @if($request->review_notes)
                            <div class="text-xs">Notes: {{ $request->review_notes }}</div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                @if($request->isPending())
                <div class="flex items-center space-x-2">
                    <flux:button wire:click="cancelRequest({{ $request->id }})" 
                               wire:confirm="Cancel this time off request?"
                               variant="danger" 
                               size="sm">
                        Cancel
                    </flux:button>
                </div>
                @endif
            </div>
        </flux:card>
        @empty
        <flux:card>
            <div class="text-center py-8 text-zinc-500">
                No time off requests found. Create one to get started.
            </div>
        </flux:card>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $this->requests->links() }}
    </div>

    @if($showCreateModal)
    <flux:modal wire:model="showCreateModal" name="create-time-off">
        <form wire:submit="createRequest">
            <flux:heading size="lg">Request Time Off</flux:heading>

            <div class="space-y-4 mt-6">
                <flux:select wire:model="form.type" label="Type" required>
                    <option value="vacation">Vacation</option>
                    <option value="sick">Sick Leave</option>
                    <option value="personal">Personal Day</option>
                    <option value="unpaid">Unpaid Leave</option>
                    <option value="bereavement">Bereavement</option>
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="form.start_date" type="date" label="Start Date" required />
                    <flux:input wire:model="form.end_date" type="date" label="End Date" required />
                </div>

                <flux:checkbox wire:model.live="form.is_full_day" label="Full Day(s)" />

                @if(!$form['is_full_day'])
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="form.start_time" type="time" label="Start Time" />
                    <flux:input wire:model="form.end_time" type="time" label="End Time" />
                </div>
                @endif

                <flux:textarea wire:model="form.reason" label="Reason (optional)" rows="3" />
            </div>

            <flux:button type="submit" variant="primary" class="mt-6">Submit Request</flux:button>
        </form>
    </flux:modal>
    @endif
</div>
