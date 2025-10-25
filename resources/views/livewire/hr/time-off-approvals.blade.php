<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Time Off Approvals</flux:heading>
        <flux:subheading>Review and approve employee time off requests</flux:subheading>
    </div>

    <div class="mb-4">
        <flux:tab.group>
            <flux:tabs wire:model.live="filterStatus">
                <flux:tab name="pending">Pending</flux:tab>
                <flux:tab name="approved">Approved</flux:tab>
                <flux:tab name="denied">Denied</flux:tab>
                <flux:tab name="all">All</flux:tab>
            </flux:tabs>
            
            <!-- Hidden panels - content is controlled by Livewire filter property -->
            <flux:tab.panel name="pending" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="approved" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="denied" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="all" class="hidden"></flux:tab.panel>
        </flux:tab.group>
    </div>

    <div class="grid gap-4">
        @forelse($this->requests as $request)
        <flux:card>
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <flux:heading size="lg">{{ $request->user->name }}</flux:heading>
                        <flux:badge color="{{ $request->status === 'approved' ? 'green' : ($request->status === 'denied' ? 'red' : 'yellow') }}">
                            {{ ucfirst($request->status) }}
                        </flux:badge>
                        <flux:badge variant="outline">{{ $request->getTypeLabel() }}</flux:badge>
                    </div>
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <div class="font-medium">{{ $request->start_date->format('M d, Y') }} - {{ $request->end_date->format('M d, Y') }}</div>
                        <div>Duration: {{ $request->getDurationDays() }} day(s) ({{ $request->total_hours }} hours)</div>
                        @if($request->reason)
                        <div class="mt-1">Reason: {{ $request->reason }}</div>
                        @endif
                        @if($request->reviewed_at)
                        <div class="mt-2 text-xs">
                            Reviewed by {{ $request->reviewedBy->name }} on {{ $request->reviewed_at->format('M d, Y g:i A') }}
                            @if($request->review_notes)
                            <div>Notes: {{ $request->review_notes }}</div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                @if($request->isPending())
                <div class="flex items-center space-x-2">
                    <flux:button wire:click="approveRequest({{ $request->id }})" 
                               variant="primary" 
                               size="sm"
                               icon="check">
                        Approve
                    </flux:button>
                    <flux:button wire:click="$set('selectedRequest', {{ $request->id }})" 
                               variant="danger" 
                               size="sm"
                               icon="x-mark">
                        Deny
                    </flux:button>
                </div>
                @endif
            </div>

            @if($selectedRequest === $request->id && $request->isPending())
            <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:textarea wire:model="reviewNotes" 
                             label="Reason for Denial" 
                             placeholder="Provide a reason for denying this request..."
                             rows="2" />
                <div class="mt-2 flex items-center space-x-2">
                    <flux:button wire:click="denyRequest({{ $request->id }})" 
                               variant="danger" 
                               size="sm">
                        Confirm Denial
                    </flux:button>
                    <flux:button wire:click="$set('selectedRequest', null)" 
                               variant="ghost" 
                               size="sm">
                        Cancel
                    </flux:button>
                </div>
            </div>
            @endif
        </flux:card>
        @empty
        <flux:card>
            <div class="text-center py-8 text-zinc-500">
                No time off requests to review.
            </div>
        </flux:card>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $this->requests->links() }}
    </div>
</div>
