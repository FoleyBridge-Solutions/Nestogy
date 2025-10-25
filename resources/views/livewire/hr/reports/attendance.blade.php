<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Attendance Report</flux:heading>
        <flux:subheading>Monitor employee attendance and punctuality</flux:subheading>
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

            <flux:select wire:model.live="viewMode" label="View Mode">
                <option value="summary">Summary</option>
                <option value="daily">Daily Breakdown</option>
            </flux:select>
        </div>
    </flux:card>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <flux:card>
            <flux:heading size="sm">Avg Attendance</flux:heading>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                {{ $this->summary['average_attendance_rate'] }}%
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Days Worked</flux:heading>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                {{ $this->summary['total_days_worked'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Days Absent</flux:heading>
            <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                {{ $this->summary['total_days_absent'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Late Arrivals</flux:heading>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                {{ $this->summary['total_late_incidents'] }}
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="sm">Perfect Attendance</flux:heading>
            <div class="text-3xl font-bold text-zinc-600 dark:text-zinc-400">
                {{ $this->summary['perfect_attendance_count'] }}
            </div>
        </flux:card>
    </div>

     @if($viewMode === 'summary')
     <flux:card>
         <flux:table>
             <flux:table.columns>
                 <flux:table.column>Employee</flux:table.column>
                 <flux:table.column>Days Worked</flux:table.column>
                 <flux:table.column>Days Absent</flux:table.column>
                 <flux:table.column>Attendance Rate</flux:table.column>
                 <flux:table.column>Late Arrivals</flux:table.column>
                 <flux:table.column>Early Departures</flux:table.column>
                 <flux:table.column>Total Hours</flux:table.column>
                 <flux:table.column>Avg Daily Hours</flux:table.column>
             </flux:table.columns>

             <flux:table.rows>
                 @foreach($this->attendanceData as $record)
                 <flux:table.row>
                     <flux:table.cell>{{ $record['user']->name }}</flux:table.cell>
                     <flux:table.cell>{{ $record['days_worked'] }}</flux:table.cell>
                     <flux:table.cell>
                         <span class="{{ $record['days_absent'] > 0 ? 'text-red-600 dark:text-red-400 font-bold' : '' }}">
                             {{ $record['days_absent'] }}
                         </span>
                     </flux:table.cell>
                     <flux:table.cell>
                         <div class="flex items-center space-x-2">
                             <span class="font-bold {{ $record['attendance_rate'] >= 95 ? 'text-green-600' : ($record['attendance_rate'] >= 85 ? 'text-yellow-600' : 'text-red-600') }}">
                                 {{ $record['attendance_rate'] }}%
                             </span>
                         </div>
                     </flux:table.cell>
                     <flux:table.cell>{{ $record['late_count'] }}</flux:table.cell>
                     <flux:table.cell>{{ $record['early_departure_count'] }}</flux:table.cell>
                     <flux:table.cell>{{ $record['total_hours'] }}h</flux:table.cell>
                     <flux:table.cell>{{ $record['average_daily_hours'] }}h</flux:table.cell>
                 </flux:table.row>
                 @endforeach
             </flux:table.rows>
         </flux:table>
     </flux:card>
     @else
     <flux:card>
         <flux:table>
             <flux:table.columns>
                 <flux:table.column>Date</flux:table.column>
                 <flux:table.column>Present</flux:table.column>
                 <flux:table.column>Absent</flux:table.column>
                 <flux:table.column>Attendance Rate</flux:table.column>
                 <flux:table.column>Total Hours</flux:table.column>
             </flux:table.columns>

             <flux:table.rows>
                 @foreach($this->dailyAttendance as $day)
                 <flux:table.row>
                     <flux:table.cell>{{ $day['date']->format('M d, Y (D)') }}</flux:table.cell>
                     <flux:table.cell>{{ $day['present_count'] }}</flux:table.cell>
                     <flux:table.cell>{{ $day['absent_count'] }}</flux:table.cell>
                     <flux:table.cell>
                         <span class="font-bold {{ $day['attendance_rate'] >= 90 ? 'text-green-600' : ($day['attendance_rate'] >= 75 ? 'text-yellow-600' : 'text-red-600') }}">
                             {{ $day['attendance_rate'] }}%
                         </span>
                     </flux:table.cell>
                     <flux:table.cell>{{ $day['total_hours'] }}h</flux:table.cell>
                 </flux:table.row>
                 @endforeach
             </flux:table.rows>
         </flux:table>
     </flux:card>
     @endif
</div>
