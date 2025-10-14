@extends('layouts.app')

@section('title', $client->name . ' - Dashboard')

@section('content')
<div class="w-full px-6" x-data="clientDashboard()">
    <!-- Client Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <flux:heading size="xl">{{ $client->name }}</flux:heading>
                <div class="flex items-center gap-3 mt-2">
                    <flux:badge 
                        color="{{ $dashboardData['overview']['status'] === 'active' ? 'green' : 'zinc' }}"
                        variant="solid"
                    >
                        {{ ucfirst($dashboardData['overview']['status']) }}
                    </flux:badge>
                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                        Client since {{ $client->created_at->format('M Y') }}
                    </flux:text>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" icon="arrow-path" @click="refreshDashboard">
                    Refresh
                </flux:button>
                <flux:dropdown>
                    <flux:button variant="primary" icon="arrow-down-tray">
                        Export
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item icon="document-text" href="{{ route('clients.dashboard.export', ['client' => $client, 'format' => 'pdf']) }}">
                            Export as PDF
                        </flux:menu.item>
                        <flux:menu.item icon="table-cells" href="{{ route('clients.dashboard.export', ['client' => $client, 'format' => 'excel']) }}">
                            Export as Excel
                        </flux:menu.item>
                        <flux:menu.item icon="document" href="{{ route('clients.dashboard.export', ['client' => $client, 'format' => 'csv']) }}">
                            Export as CSV
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Financial Card -->
        <flux:card>
            <div class="flex justify-between items-start">
                <div>
                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mb-1">Total Revenue</flux:text>
                    <flux:heading size="2xl">${{ number_format($dashboardData['financial']['total_revenue'], 2) }}</flux:heading>
                    <flux:text size="sm" class="text-green-600 dark:text-green-400 mt-2">
                        MRR: ${{ number_format($dashboardData['financial']['mrr'], 2) }}
                    </flux:text>
                </div>
                <flux:icon name="currency-dollar" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
        </flux:card>

        <!-- Tickets Card -->
        <flux:card>
            <div class="flex justify-between items-start">
                <div>
                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mb-1">Open Tickets</flux:text>
                    <flux:heading size="2xl">{{ $dashboardData['tickets']['open'] }}</flux:heading>
                    <div class="flex items-center gap-3 mt-2">
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            Total: {{ $dashboardData['tickets']['total'] }}
                        </flux:text>
                        <flux:text size="sm">
                            SLA: {{ $dashboardData['tickets']['sla_compliance'] }}%
                        </flux:text>
                    </div>
                </div>
                <flux:icon name="ticket" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            </div>
        </flux:card>

        <!-- Assets Card -->
        <flux:card>
            <div class="flex justify-between items-start">
                <div>
                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mb-1">Active Assets</flux:text>
                    <flux:heading size="2xl">{{ $dashboardData['assets']['active_assets'] }}</flux:heading>
                    <div class="flex items-center gap-3 mt-2">
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            Total: {{ $dashboardData['assets']['total_assets'] }}
                        </flux:text>
                        @if($dashboardData['assets']['maintenance_due'] > 0)
                            <flux:text size="sm" class="text-yellow-600 dark:text-yellow-400">
                                {{ $dashboardData['assets']['maintenance_due'] }} due
                            </flux:text>
                        @endif
                    </div>
                </div>
                <flux:icon name="server-stack" class="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
            </div>
        </flux:card>

        <!-- Projects Card -->
        <flux:card>
            <div class="flex justify-between items-start">
                <div>
                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mb-1">Active Projects</flux:text>
                    <flux:heading size="2xl">{{ $dashboardData['projects']['active_projects'] }}</flux:heading>
                    <div class="flex items-center gap-3 mt-2">
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            Completion: {{ $dashboardData['projects']['completion_rate'] }}%
                        </flux:text>
                        @if($dashboardData['projects']['overdue_projects'] > 0)
                            <flux:text size="sm" class="text-red-600 dark:text-red-400">
                                {{ $dashboardData['projects']['overdue_projects'] }} overdue
                            </flux:text>
                        @endif
                    </div>
                </div>
                <flux:icon name="clipboard-document-list" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
        </flux:card>
    </div>

    <!-- Main Content Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Recent Activity -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Recent Activity</flux:heading>
                <div class="space-y-4">
                    @foreach($dashboardData['recent_activity'] as $activity)
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-2.5 h-2.5 rounded-full 
                                {{ $activity['type'] === 'ticket' ? 'bg-yellow-500' : '' }}
                                {{ $activity['type'] === 'invoice' ? 'bg-blue-500' : '' }}
                                {{ $activity['type'] === 'payment' ? 'bg-green-500' : '' }}
                                {{ !in_array($activity['type'], ['ticket', 'invoice', 'payment']) ? 'bg-gray-400' : '' }}">
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <flux:text class="font-medium">{{ $activity['description'] }}</flux:text>
                            <div class="flex items-center gap-3 mt-1">
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}
                                </flux:text>
                                @if(isset($activity['amount']))
                                    <flux:text size="sm">${{ number_format($activity['amount'], 2) }}</flux:text>
                                @endif
                                @if(isset($activity['status']))
                                    <flux:badge 
                                        size="sm" 
                                        color="{{ $activity['status'] === 'completed' ? 'green' : 'yellow' }}"
                                    >
                                        {{ $activity['status'] }}
                                    </flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </flux:card>

            <!-- Financial Overview Chart -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Financial Overview</flux:heading>
                <div style="height: 320px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </flux:card>

            <!-- Recent Tickets -->
            <flux:card>
                <div class="flex justify-between items-center mb-4">
                    <flux:heading size="lg">Recent Tickets</flux:heading>
                    <flux:button variant="ghost" size="sm" href="{{ route('tickets.index') }}">
                        View All
                    </flux:button>
                </div>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>ID</flux:table.column>
                        <flux:table.column>Subject</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Priority</flux:table.column>
                        <flux:table.column>Created</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($dashboardData['tickets']['recent_tickets'] as $ticket)
                        <flux:table.row>
                            <flux:table.cell>#{{ $ticket->id }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:link href="{{ route('tickets.show', $ticket) }}">
                                    {{ Str::limit($ticket->subject, 40) }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge 
                                    size="sm"
                                    color="{{ $ticket->status === 'open' ? 'yellow' : ($ticket->status === 'closed' ? 'green' : 'blue') }}"
                                >
                                    {{ $ticket->status }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge 
                                    size="sm"
                                    color="{{ $ticket->priority === 'high' ? 'red' : ($ticket->priority === 'medium' ? 'yellow' : 'zinc') }}"
                                >
                                    {{ $ticket->priority }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $ticket->created_at->format('M d, Y') }}</flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>

        <!-- Right Column (1/3 width) -->
        <div class="space-y-6">
            <!-- Client Information -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Client Information</flux:heading>
                <div class="space-y-3">
                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Primary Contact</flux:text>
                        @if($dashboardData['overview']['primary_contact'])
                            <flux:text class="font-medium">{{ $dashboardData['overview']['primary_contact']->name }}</flux:text>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ $dashboardData['overview']['primary_contact']->email }}
                            </flux:text>
                        @else
                            <flux:text class="text-gray-600 dark:text-gray-400">Not set</flux:text>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Total Contacts</flux:text>
                            <flux:text class="font-medium">{{ $dashboardData['overview']['total_contacts'] }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Locations</flux:text>
                            <flux:text class="font-medium">{{ $dashboardData['overview']['total_locations'] }}</flux:text>
                        </div>
                    </div>

                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Outstanding Balance</flux:text>
                        <flux:text class="font-medium {{ $dashboardData['financial']['outstanding_balance'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            ${{ number_format($dashboardData['financial']['outstanding_balance'], 2) }}
                        </flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Credit Status</flux:text>
                        <flux:badge color="green" size="sm" class="mt-1">Good Standing</flux:badge>
                    </div>
                </div>
            </flux:card>

            <!-- Expiring Items -->
            @if(count($dashboardData['expiring_items']) > 0)
            <flux:card class="border-l-4 border-l-yellow-500">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon name="exclamation-triangle" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                    <flux:heading size="lg">Expiring Soon</flux:heading>
                </div>
                <div class="space-y-3">
                    @foreach($dashboardData['expiring_items'] as $type => $items)
                        @foreach($items as $item)
                        <div class="flex justify-between items-start">
                            <div>
                                <flux:text class="font-medium">
                                    {{ $item->name ?? $item->domain_name ?? 'Item' }}
                                </flux:text>
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                    {{ ucfirst($type) }}
                                </flux:text>
                            </div>
                            <flux:text size="sm" class="text-red-600 dark:text-red-400">
                                {{ \Carbon\Carbon::parse($item->expiry_date ?? $item->warranty_expiry)->format('M d, Y') }}
                            </flux:text>
                        </div>
                        @endforeach
                    @endforeach
                </div>
            </flux:card>
            @endif

            <!-- Upcoming Events -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Upcoming Events</flux:heading>
                @if($dashboardData['upcoming_events']->count() > 0)
                    <div class="space-y-3">
                        @foreach($dashboardData['upcoming_events'] as $event)
                        <div>
                            <flux:text class="font-medium">
                                {{ $event->title ?? $event->name }}
                            </flux:text>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($event->start_date ?? $event->due_date)->format('M d, Y') }}
                            </flux:text>
                        </div>
                        @endforeach
                    </div>
                @else
                    <flux:text class="text-gray-600 dark:text-gray-400">No upcoming events</flux:text>
                @endif
            </flux:card>

            <!-- Quick Actions -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>
                <div class="space-y-2">
                    <flux:button variant="primary" icon="ticket" href="{{ route('tickets.create') }}" class="w-full justify-start">
                        Create Ticket
                    </flux:button>
                    <flux:button variant="primary" icon="document-text" href="{{ route('invoices.create') }}" class="w-full justify-start">
                        Create Invoice
                    </flux:button>
                    <flux:button variant="primary" icon="clipboard-document-list" href="{{ route('projects.create') }}" class="w-full justify-start">
                        New Project
                    </flux:button>
                    <flux:button variant="primary" icon="server-stack" href="{{ route('assets.create') }}" class="w-full justify-start">
                        Add Asset
                    </flux:button>
                </div>
            </flux:card>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function clientDashboard() {
    return {
        refreshDashboard() {
            fetch('{{ route("clients.dashboard.refresh", $client) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                // Show success notification
                this.showNotification(data.message || 'Dashboard refreshed successfully', 'success');
                // Reload page to show fresh data
                setTimeout(() => window.location.reload(), 1500);
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('Failed to refresh dashboard', 'error');
            });
        },

        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    };
}

// Revenue Chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        const revenueChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 35000, 32000, 38000, 35000, 40000],
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(156, 163, 175, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + (value / 1000).toFixed(0) + 'k';
                            },
                            color: 'rgb(107, 114, 128)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgb(107, 114, 128)'
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush

@endsection