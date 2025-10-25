<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Employee Schedules</flux:heading>
        <flux:subheading>Manage employee work schedules</flux:subheading>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <flux:button wire:click="previousWeek" variant="ghost" icon="chevron-left">Previous</flux:button>
            <flux:button wire:click="today" variant="outline">Today</flux:button>
            <flux:button wire:click="nextWeek" variant="ghost" icon-trailing="chevron-right">Next</flux:button>
            <div class="ml-4">
                <flux:heading size="lg">{{ \Carbon\Carbon::parse($selectedDate)->format('M d, Y') }}</flux:heading>
            </div>
        </div>

        @if($canManageHR)
        <flux:button wire:click="$set('showCreateModal', true)" variant="primary" icon="plus">
            Add Schedule
        </flux:button>
        @endif
    </div>

     <flux:card class="overflow-hidden">
         <flux:table>
             <flux:table.columns>
                 <flux:table.column>Employee</flux:table.column>
                 <flux:table.column>Date</flux:table.column>
                 <flux:table.column>Start Time</flux:table.column>
                 <flux:table.column>End Time</flux:table.column>
                 <flux:table.column>Duration</flux:table.column>
                 <flux:table.column>Status</flux:table.column>
                 @if($canManageHR)
                 <flux:table.column>Actions</flux:table.column>
                 @endif
             </flux:table.columns>

             <flux:table.rows>
                 @forelse($this->schedules as $schedule)
                 <flux:table.row>
                     <flux:table.cell>{{ $schedule->user->name }}</flux:table.cell>
                     <flux:table.cell>{{ $schedule->scheduled_date->format('M d, Y') }}</flux:table.cell>
                     <flux:table.cell>{{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}</flux:table.cell>
                     <flux:table.cell>{{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}</flux:table.cell>
                     <flux:table.cell>{{ round($schedule->getDurationMinutes() / 60, 2) }}h</flux:table.cell>
                     <flux:table.cell>
                         <flux:badge color="{{ $schedule->status === 'confirmed' ? 'green' : 'zinc' }}">
                             {{ ucfirst($schedule->status) }}
                         </flux:badge>
                     </flux:table.cell>
                     @if($canManageHR)
                     <flux:table.cell>
                         <flux:button wire:click="deleteSchedule({{ $schedule->id }})" 
                                    wire:confirm="Are you sure you want to delete this schedule?"
                                    variant="danger" 
                                    size="sm" 
                                    icon="trash">
                         </flux:button>
                     </flux:table.cell>
                     @endif
                 </flux:table.row>
                 @empty
                 <flux:table.row>
                     <flux:table.cell colspan="7" class="text-center py-8 text-zinc-500">
                         No schedules for this period
                     </flux:table.cell>
                 </flux:table.row>
                 @endforelse
             </flux:table.rows>
         </flux:table>
     </flux:card>

    @if($showCreateModal && $canManageHR)
    <flux:modal wire:model="showCreateModal" name="create-schedule">
        <form wire:submit="createSchedule">
            <flux:heading size="lg">Create Schedule</flux:heading>

            <div class="space-y-4 mt-6">
                <flux:select wire:model="form.user_id" label="Employee" placeholder="Select employee">
                    @foreach($this->employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="form.scheduled_date" type="date" label="Date" />
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="form.start_time" type="time" label="Start Time" />
                    <flux:input wire:model="form.end_time" type="time" label="End Time" />
                </div>

                <flux:textarea wire:model="form.notes" label="Notes" rows="3" />
            </div>

            <flux:button type="submit" variant="primary" class="mt-6">Create Schedule</flux:button>
        </form>
    </flux:modal>
    @endif
</div>
