<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">HR Dashboard</flux:heading>
            <flux:subheading>Time tracking and workforce management</flux:subheading>
        </div>
        <div class="flex gap-2">
            @if($activeEntry)
                <flux:badge color="green" size="lg" variant="solid" icon="clock">
                    Clocked In
                </flux:badge>
            @else
                <flux:badge color="zinc" size="lg">
                    Clocked Out
                </flux:badge>
            @endif
        </div>
    </div>

    {{-- Quick Actions --}}
    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">Quick Actions</flux:heading>
                <flux:subheading class="mt-1">Common tasks and shortcuts</flux:subheading>
            </div>
            <div class="flex gap-2">
                @if($activeEntry)
                    <flux:button variant="danger" size="base" icon="arrow-right-end-on-rectangle" wire:click="clockOut">
                        Clock Out
                    </flux:button>
                @else
                    <flux:button variant="primary" size="base" icon="arrow-right-end-on-rectangle" wire:click="clockIn">
                        Clock In
                    </flux:button>
                @endif
                <flux:button variant="ghost" icon="calendar" href="{{ route('hr.time-clock.history') }}">
                    View History
                </flux:button>
            </div>
        </div>

        @if($activeEntry)
            <flux:separator class="my-4" />
            <div class="flex items-center gap-4">
                <flux:icon.clock class="w-12 h-12 text-green-500" />
                <div>
                    <flux:text>Clocked in at <strong>{{ $activeEntry->clock_in->format('g:i A') }}</strong></flux:text>
                    <flux:text class="text-sm text-zinc-500">{{ $activeEntry->getFormattedDuration() }} elapsed</flux:text>
                </div>
            </div>
        @endif
    </flux:card>

    {{-- My Stats --}}
    <div>
        <flux:heading size="lg" class="mb-4">My Time</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- This Week --}}
            <flux:card>
                <div class="flex justify-between items-start">
                    <div>
                        <flux:text class="text-sm text-zinc-500">This Week</flux:text>
                        <flux:heading size="2xl" class="mt-1">
                            {{ round($this->myStats['week_total_minutes'] / 60, 1) }}
                        </flux:heading>
                        <flux:text class="text-sm">
                            Regular: {{ round($this->myStats['week_regular_minutes'] / 60, 1) }} hrs
                        </flux:text>
                        @if($this->myStats['week_overtime_minutes'] > 0)
                            <flux:text class="text-sm text-orange-600 dark:text-orange-400">
                                OT: {{ round($this->myStats['week_overtime_minutes'] / 60, 1) }} hrs
                            </flux:text>
                        @endif
                    </div>
                    <flux:icon.calendar class="w-6 h-6 text-blue-500" />
                </div>
            </flux:card>

            {{-- This Month --}}
            <flux:card>
                <div class="flex justify-between items-start">
                    <div>
                        <flux:text class="text-sm text-zinc-500">This Month</flux:text>
                        <flux:heading size="2xl" class="mt-1">
                            {{ round($this->myStats['month_total_minutes'] / 60, 1) }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Total hours</flux:text>
                    </div>
                    <flux:icon.chart-bar class="w-6 h-6 text-green-500" />
                </div>
            </flux:card>

            {{-- Pending Approval --}}
            <flux:card>
                <div class="flex justify-between items-start">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Pending Approval</flux:text>
                        <flux:heading size="2xl" class="mt-1">
                            {{ $this->myStats['pending_entries'] }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500">Time entries</flux:text>
                    </div>
                    <flux:icon.exclamation-circle class="w-6 h-6 text-yellow-500" />
                </div>
            </flux:card>

            {{-- Current Status --}}
            <flux:card>
                <div class="flex justify-between items-start">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Status</flux:text>
                        @if($activeEntry)
                            <flux:badge color="green" variant="solid" size="lg" class="mt-2">
                                Active
                            </flux:badge>
                            <flux:text class="text-sm mt-2">
                                {{ $activeEntry->getFormattedDuration() }}
                            </flux:text>
                        @else
                            <flux:badge color="zinc" size="lg" class="mt-2">
                                Off Duty
                            </flux:badge>
                        @endif
                    </div>
                    <flux:icon.user class="w-6 h-6 text-purple-500" />
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Team Stats (Managers Only) --}}
    @if($canManageHR && $this->teamStats)
        <div>
            <flux:heading size="lg" class="mb-4">Team Overview</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                {{-- Clocked In --}}
                <flux:card>
                    <div class="flex justify-between items-start">
                        <div>
                            <flux:text class="text-sm text-zinc-500">Clocked In</flux:text>
                            <flux:heading size="2xl" class="mt-1">
                                {{ $this->teamStats['clocked_in'] }}<span class="text-base text-zinc-400">/{{ $this->teamStats['total_employees'] }}</span>
                            </flux:heading>
                            <flux:text class="text-sm text-zinc-500">Employees</flux:text>
                        </div>
                        <flux:icon.user-group class="w-6 h-6 text-blue-500" />
                    </div>
                </flux:card>

                {{-- Pending Approvals --}}
                <flux:card>
                    <div class="flex justify-between items-start">
                        <div>
                            <flux:text class="text-sm text-zinc-500">Pending Approvals</flux:text>
                            <flux:heading size="2xl" class="mt-1">
                                {{ $this->teamStats['pending_approvals'] }}
                            </flux:heading>
                            <flux:text class="text-sm">
                                <a href="{{ route('hr.time-entries.index') }}" class="text-blue-600 hover:underline">
                                    Review entries
                                </a>
                            </flux:text>
                        </div>
                        <flux:icon.check-circle class="w-6 h-6 text-yellow-500" />
                    </div>
                </flux:card>

                {{-- Team Hours This Week --}}
                <flux:card>
                    <div class="flex justify-between items-start">
                        <div>
                            <flux:text class="text-sm text-zinc-500">Team Hours</flux:text>
                            <flux:heading size="2xl" class="mt-1">
                                {{ round($this->teamStats['team_week_minutes'] / 60, 1) }}
                            </flux:heading>
                            <flux:text class="text-sm text-zinc-500">This week</flux:text>
                        </div>
                        <flux:icon.chart-bar class="w-6 h-6 text-green-500" />
                    </div>
                </flux:card>

                {{-- Overtime Alerts --}}
                <flux:card>
                    <div class="flex justify-between items-start">
                        <div>
                            <flux:text class="text-sm text-zinc-500">Overtime Alerts</flux:text>
                            <flux:heading size="2xl" class="mt-1">
                                {{ $this->teamStats['overtime_alerts'] }}
                            </flux:heading>
                            <flux:text class="text-sm text-zinc-500">Employees</flux:text>
                        </div>
                        <flux:icon.fire class="w-6 h-6 text-orange-500" />
                    </div>
                </flux:card>

                {{-- Quick Link --}}
                <flux:card class="flex items-center justify-center">
                    <a href="{{ route('hr.time-entries.index') }}" class="text-center">
                        <flux:icon.table-cells class="w-8 h-8 mx-auto text-zinc-400 mb-2" />
                        <flux:text class="text-sm text-blue-600 hover:underline">
                            Manage All Entries
                        </flux:text>
                    </a>
                </flux:card>
            </div>
        </div>
    @endif

    {{-- Recent Activity --}}
    <div>
        <flux:heading size="lg" class="mb-4">Recent Activity</flux:heading>
        <flux:card>
            @if($this->recentEntries->isEmpty())
                <div class="text-center py-8">
                    <flux:icon.clock class="w-12 h-12 mx-auto text-zinc-400 mb-2" />
                    <flux:text class="text-zinc-500">No recent time entries</flux:text>
                </div>
            @else
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->recentEntries as $entry)
                        <div class="py-3 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 text-center">
                                    <div class="text-xs text-zinc-500">{{ $entry->clock_in->format('M') }}</div>
                                    <div class="text-lg font-bold">{{ $entry->clock_in->format('d') }}</div>
                                </div>
                                <div>
                                    <flux:text class="font-medium">
                                        {{ $entry->clock_in->format('g:i A') }} 
                                        @if($entry->clock_out)
                                            - {{ $entry->clock_out->format('g:i A') }}
                                        @else
                                            <flux:badge color="green" size="sm">In Progress</flux:badge>
                                        @endif
                                    </flux:text>
                                    <flux:text class="text-sm text-zinc-500">
                                        @if($entry->clock_out)
                                            {{ $entry->getTotalHours() }} hrs
                                            @if($entry->getOvertimeHours() > 0)
                                                · <span class="text-orange-600">{{ $entry->getOvertimeHours() }} OT</span>
                                            @endif
                                        @else
                                            {{ $entry->getFormattedDuration() }} elapsed
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @php
                                    $statusColors = [
                                        'in_progress' => 'green',
                                        'completed' => 'blue',
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        'paid' => 'purple',
                                    ];
                                    $color = $statusColors[$entry->status] ?? 'zinc';
                                @endphp
                                <flux:badge :color="$color" size="sm">
                                    {{ ucfirst(str_replace('_', ' ', $entry->status)) }}
                                </flux:badge>
                            </div>
                        </div>
                    @endforeach
                </div>

                <flux:separator class="my-4" />

                <div class="text-center">
                    <flux:button variant="ghost" icon="arrow-right" href="{{ route('hr.time-clock.history') }}">
                        View All History
                    </flux:button>
                </div>
            @endif
        </flux:card>
    </div>
</div>
