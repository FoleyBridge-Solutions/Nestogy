<!-- Assign Technician Modal -->
@can('assign', $ticket)
<flux:modal name="assign-technician" class="min-w-[500px]">
    <flux:heading size="lg">Assign Technician</flux:heading>
    
    <flux:text class="mt-2 mb-6">
        @if($ticket->assignee)
            Currently assigned to: <strong>{{ $ticket->assignee->name }}</strong>
        @else
            This ticket is currently unassigned
        @endif
    </flux:text>

    <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="space-y-6">
        @csrf
        @method('PATCH')
        
        <flux:select name="assigned_to" label="Assign To" required>
            <flux:select.option value="">Select a technician...</flux:select.option>
            @php
                $technicians = \App\Models\User::where('company_id', auth()->user()->company_id)
                    ->where('status', '1')
                    ->whereNull('archived_at')
                    ->orderBy('name')
                    ->get();
            @endphp
            @foreach($technicians as $technician)
                <flux:select.option value="{{ $technician->id }}">
                    {{ $technician->name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea 
            name="assignment_note" 
            label="Assignment Note (Optional)" 
            placeholder="Add any notes about this assignment..."
            rows="3" />

        <flux:field variant="inline">
            <flux:checkbox name="notify_assignee" value="1" checked />
            <flux:label>Send notification to assignee</flux:label>
        </flux:field>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button type="button" variant="ghost">
                    Cancel
                </flux:button>
            </flux:modal.close>
            
            <flux:button type="submit" variant="primary">
                Assign Ticket
            </flux:button>
        </div>
    </form>
</flux:modal>
@endcan