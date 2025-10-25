<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Timesheet Report</flux:heading>
        <flux:subheading>View detailed employee time tracking data</flux:subheading>
    </div>

    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:input wire:model.live="startDate" type="date" label="Start Date" />
            <flux:input wire:model.live="endDate" type="date" label="End Date" />
            
            <flux:select wire:model.live="selectedUser" label="Employee" placeholder="All Employees">
                <option value="">All Employees</option>
                @foreach($this->users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="groupBy" label="Group By">
                <option value="user">By Employee</option>
                <option value="day">By Day</option>
            </flux:select>
        </div>

        <div class="mt-4">
            <flux:select wire:model.live="selectedPayPeriod" label="Quick Select Pay Period" placeholder="Select a pay period">
                <option value="">Custom Date Range</option>
                @foreach($this->payPeriods as $period)
                <option value="{{ $period->id }}">{{ $period->getLabel() }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <flux:card>
            <flux:heading size="sm">Total Hours</flux:heading>
            <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                {{ $this->totals['total_hours'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Regular Hours</flux:heading>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                {{ $this->totals['regular_hours'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Overtime Hours</flux:heading>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                {{ $this->totals['overtime_hours'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Employees</flux:heading>
            <div class="text-3xl font-bold text-zinc-600 dark:text-zinc-400">
                {{ $this->totals['employee_count'] }}
            </div>
        </flux:card>
    </div>

    @if($groupBy === 'user')
    <div class="space-y-4">
        @foreach($this->entries as $userId => $group)
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">{{ $group['user']->name }}</flux:heading>
                <div class="text-right">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ $group['total_hours'] }}h
                    </div>
                    <div class="text-sm text-zinc-500">
                        Regular: {{ round($group['regular_minutes'] / 60, 2) }}h | 
                        OT: {{ round($group['overtime_minutes'] / 60, 2) }}h
                    </div>
                </div>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Clock In</flux:table.column>
                    <flux:table.column>Clock Out</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($group['entries'] as $entry)
                    <flux:table.row>
                        <flux:table.cell>{{ $entry->clock_in->format('M d, Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $entry->clock_in->format('g:i A') }}</flux:table.cell>
                        <flux:table.cell>
                            @if($entry->clock_out)
                            {{ $entry->clock_out->format('g:i A') }}
                            @else
                            <flux:badge color="green">In Progress</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ round($entry->total_minutes / 60, 2) }}h</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $entry->status === 'approved' ? 'green' : 'zinc' }}">
                                {{ ucfirst($entry->status) }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
        @endforeach
    </div>
    @endif
</div>
