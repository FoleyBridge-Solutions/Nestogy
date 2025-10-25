<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Overtime Report</flux:heading>
        <flux:subheading>Track and analyze employee overtime hours</flux:subheading>
    </div>

    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:input wire:model.live="startDate" type="date" label="Start Date" />
            <flux:input wire:model.live="endDate" type="date" label="End Date" />
            
            <flux:select wire:model.live="selectedUser" label="Employee" placeholder="All Employees">
                <option value="">All Employees</option>
                @foreach($this->users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <flux:card>
            <flux:heading size="sm">Total OT Hours</flux:heading>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                {{ $this->summary['total_overtime_hours'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Avg Per Employee</flux:heading>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                {{ $this->summary['average_overtime_per_employee'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Employees w/ OT</flux:heading>
            <div class="text-3xl font-bold text-zinc-600 dark:text-zinc-400">
                {{ $this->summary['employees_with_overtime'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Max OT Hours</flux:heading>
            <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                {{ $this->summary['max_overtime_hours'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Est. Cost</flux:heading>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                ${{ number_format($this->summary['total_cost_estimate'], 2) }}
            </div>
        </flux:card>
    </div>

    <div class="space-y-4">
        @forelse($this->overtimeData as $userId => $data)
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <flux:heading size="lg">{{ $data['user']->name }}</flux:heading>
                    <div class="text-sm text-zinc-500">
                        {{ $data['weeks_with_overtime'] }} week(s) with overtime
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                        {{ $data['total_overtime_hours'] }}h
                    </div>
                    <div class="text-sm text-zinc-500">
                        Total: {{ $data['total_hours'] }}h
                    </div>
                </div>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Week Starting</flux:table.column>
                    <flux:table.column>Total Hours</flux:table.column>
                    <flux:table.column>Overtime Hours</flux:table.column>
                    <flux:table.column>Entries</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($data['weekly_breakdown'] as $week)
                    <flux:table.row>
                        <flux:table.cell>{{ $week['week_start']->format('M d, Y') }}</flux:table.cell>
                        <flux:table.cell>{{ round($week['total_minutes'] / 60, 2) }}h</flux:table.cell>
                        <flux:table.cell class="font-bold text-orange-600 dark:text-orange-400">
                            {{ round($week['overtime_minutes'] / 60, 2) }}h
                        </flux:table.cell>
                        <flux:table.cell>{{ $week['entry_count'] }}</flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
        @empty
        <flux:card>
            <div class="text-center py-8 text-zinc-500">
                No overtime recorded for this period.
            </div>
        </flux:card>
        @endforelse
    </div>
</div>
