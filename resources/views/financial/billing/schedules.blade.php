@extends('layouts.app')

@section('title', 'Billing Schedules')

@section('content')
<flux:main class="space-y-6">
    <flux:header>
        <flux:heading>Billing Schedules</flux:heading>
        <flux:actions>
            <flux:button href="{{ route('financial.billing.create-schedule') }}" variant="primary">
                <flux:icon name="plus" size="sm" />
                New Schedule
            </flux:button>
        </flux:actions>
    </flux:header>

    <flux:card>
        <flux:card.header>
            <flux:card.title>Upcoming Billing</flux:card.title>
        </flux:card.header>
        <flux:card.body>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <flux:stat>
                    <flux:stat.label>Due Today</flux:stat.label>
                    <flux:stat.value>${{ number_format($dueToday ?? 0, 2) }}</flux:stat.value>
                    <flux:stat.description>{{ $countDueToday ?? 0 }} invoices</flux:stat.description>
                </flux:stat>
                
                <flux:stat>
                    <flux:stat.label>Due This Week</flux:stat.label>
                    <flux:stat.value>${{ number_format($dueThisWeek ?? 0, 2) }}</flux:stat.value>
                    <flux:stat.description>{{ $countDueThisWeek ?? 0 }} invoices</flux:stat.description>
                </flux:stat>
                
                <flux:stat>
                    <flux:stat.label>Due This Month</flux:stat.label>
                    <flux:stat.value>${{ number_format($dueThisMonth ?? 0, 2) }}</flux:stat.value>
                    <flux:stat.description>{{ $countDueThisMonth ?? 0 }} invoices</flux:stat.description>
                </flux:stat>
                
                <flux:stat>
                    <flux:stat.label>Monthly Recurring</flux:stat.label>
                    <flux:stat.value>${{ number_format($monthlyRecurring ?? 0, 2) }}</flux:stat.value>
                    <flux:stat.description>MRR</flux:stat.description>
                </flux:stat>
            </div>
        </flux:card.body>
    </flux:card>

    <flux:card>
        <flux:card.header>
            <flux:card.title>Billing Schedules</flux:card.title>
            <flux:card.actions>
                <flux:input type="search" name="search" placeholder="Search schedules..." />
                <flux:select name="frequency">
                    <flux:select.option value="">All Frequencies</flux:select.option>
                    <flux:select.option value="monthly">Monthly</flux:select.option>
                    <flux:select.option value="quarterly">Quarterly</flux:select.option>
                    <flux:select.option value="semi-annual">Semi-Annual</flux:select.option>
                    <flux:select.option value="annual">Annual</flux:select.option>
                </flux:select>
                <flux:select name="status">
                    <flux:select.option value="">All Status</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="paused">Paused</flux:select.option>
                    <flux:select.option value="cancelled">Cancelled</flux:select.option>
                </flux:select>
            </flux:card.actions>
        </flux:card.header>
        <flux:card.body>
            <flux:table>
                <flux:table.header>
                    <flux:table.row>
                        <flux:table.head>Client</flux:table.head>
                        <flux:table.head>Service</flux:table.head>
                        <flux:table.head>Amount</flux:table.head>
                        <flux:table.head>Frequency</flux:table.head>
                        <flux:table.head>Next Billing</flux:table.head>
                        <flux:table.head>Status</flux:table.head>
                        <flux:table.head>Actions</flux:table.head>
                    </flux:table.row>
                </flux:table.header>
                <flux:table.body>
                    @forelse($schedules ?? [] as $schedule)
                    <flux:table.row>
                        <flux:table.cell>{{ $schedule->client->name }}</flux:table.cell>
                        <flux:table.cell>{{ $schedule->service->name }}</flux:table.cell>
                        <flux:table.cell>${{ number_format($schedule->amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge>{{ ucfirst($schedule->frequency) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $schedule->next_billing_date->format('M d, Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="{{ $schedule->status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($schedule->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost">
                                    <flux:icon name="ellipsis-horizontal" />
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item href="{{ route('financial.billing.show-schedule', $schedule) }}" icon="eye">
                                        View Details
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ route('financial.billing.edit-schedule', $schedule) }}" icon="pencil">
                                        Edit Schedule
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="pauseSchedule({{ $schedule->id }})" icon="pause">
                                        Pause Schedule
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="generateInvoice({{ $schedule->id }})" icon="document-text">
                                        Generate Invoice Now
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-gray-500">
                            No billing schedules found
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.body>
            </flux:table>
        </flux:card.body>
    </flux:card>
</flux:main>
@endsection