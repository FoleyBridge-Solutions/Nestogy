@extends('layouts.app')

@section('title', 'Billing Schedules')

@section('content')
<flux:main class="space-y-6">
    <flux:header>
        <flux:heading>Billing Schedules</flux:heading>
        
            <flux:button href="{{ route('financial.billing.create-schedule') }}" variant="primary">
                <flux:icon name="plus" size="sm" />
                New Schedule
            </flux:button>
        
    </flux:header>

    <flux:card>
        <div class="border-b pb-4 mb-4">
            <flux:heading size="lg">Upcoming Billing</flux:heading>
        </div>
        
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg border p-4">
                    <div class="text-sm font-medium text-gray-500">Due Today</div>
                    <div class="text-2xl font-bold text-gray-900">${{ number_format($dueToday ?? 0, 2) }}</div>
                    <div class="text-xs text-gray-400">{{ $countDueToday ?? 0 }} invoices</div>
                </div>
                
                <div class="bg-white rounded-lg border p-4">
                    <div class="text-sm font-medium text-gray-500">Due This Week</div>
                    <div class="text-2xl font-bold text-gray-900">${{ number_format($dueThisWeek ?? 0, 2) }}</div>
                    <div class="text-xs text-gray-400">{{ $countDueThisWeek ?? 0 }} invoices</div>
                </div>
                
                <div class="bg-white rounded-lg border p-4">
                    <div class="text-sm font-medium text-gray-500">Due This Month</div>
                    <div class="text-2xl font-bold text-gray-900">${{ number_format($dueThisMonth ?? 0, 2) }}</div>
                    <div class="text-xs text-gray-400">{{ $countDueThisMonth ?? 0 }} invoices</div>
                </div>
                
                <div class="bg-white rounded-lg border p-4">
                    <div class="text-sm font-medium text-gray-500">Monthly Recurring</div>
                    <div class="text-2xl font-bold text-gray-900">${{ number_format($monthlyRecurring ?? 0, 2) }}</div>
                    <div class="text-xs text-gray-400">MRR</div>
                </div>
            </div>
        
    </flux:card>

    <flux:card>
        <div class="border-b pb-4 mb-4">
            <flux:heading size="lg">Billing Schedules</flux:heading>
            <div class="flex gap-2 mt-4">
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
            </div>
        </div>
        
            <flux:table>
                <div class="mb-4">
                    <flux:table.row>
                        <flux:table.columns>Client</flux:table.columns>
                        <flux:table.columns>Service</flux:table.columns>
                        <flux:table.columns>Amount</flux:table.columns>
                        <flux:table.columns>Frequency</flux:table.columns>
                        <flux:table.columns>Next Billing</flux:table.columns>
                        <flux:table.columns>Status</flux:table.columns>
                        <flux:table.columns>Actions</flux:table.columns>
                    </flux:table.row>
                </div>
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
        
    </flux:card>
</flux:main>
@endsection