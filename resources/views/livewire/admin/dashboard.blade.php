<div>
    <flux:heading size="xl" class="mb-6">Platform Admin Dashboard</flux:heading>

    {{-- Key Metrics Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- MRR Card --}}
        <flux:card class="space-y-2">
            <flux:text>Monthly Recurring Revenue</flux:text>
            <flux:heading size="xl" class="tabular-nums">${{ number_format($stats['mrr'], 2) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">Active subscriptions only</flux:text>
        </flux:card>

        {{-- Total Companies --}}
        <flux:card class="space-y-2">
            <flux:text>Total Companies</flux:text>
            <flux:heading size="xl" class="tabular-nums">{{ number_format($stats['total_companies']) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">
                {{ $stats['active_companies'] }} active, {{ $stats['suspended_companies'] }} suspended
            </flux:text>
        </flux:card>

        {{-- Churn Rate --}}
        <flux:card class="space-y-2">
            <flux:text>Churn Rate (30d)</flux:text>
            <flux:heading size="xl" class="tabular-nums">{{ number_format($stats['churn_rate'], 2) }}%</flux:heading>
            <flux:text class="text-sm text-zinc-500">Last 30 days</flux:text>
        </flux:card>

        {{-- ARPU --}}
        <flux:card class="space-y-2">
            <flux:text>Avg Revenue Per User</flux:text>
            <flux:heading size="xl" class="tabular-nums">${{ number_format($stats['arpu'], 2) }}</flux:text>
            <flux:text class="text-sm text-zinc-500">LTV: ${{ number_format($stats['ltv'], 2) }}</flux:text>
        </flux:card>
    </div>

    {{-- Subscription Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <flux:card class="space-y-2">
            <flux:text>Active Subscriptions</flux:text>
            <flux:heading size="lg" class="tabular-nums">{{ number_format($stats['active_subscriptions']) }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text>Trial Subscriptions</flux:text>
            <flux:heading size="lg" class="tabular-nums">{{ number_format($stats['trialing_subscriptions']) }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text>Trial Conversion Rate</flux:text>
            <flux:heading size="lg" class="tabular-nums">{{ number_format($stats['trial_conversion_rate'], 2) }}%</flux:heading>
        </flux:card>
    </div>

    {{-- Revenue Trend Chart --}}
    <flux:card class="mb-8">
        <flux:heading size="lg" class="mb-4">Revenue Trends (12 Months)</flux:heading>
        <flux:chart wire:model="revenueTrends" class="aspect-[3/1]">
            <flux:chart.svg>
                <flux:chart.line field="mrr" class="text-blue-500 dark:text-blue-400" curve="none" />
                <flux:chart.area field="mrr" class="text-blue-200/50 dark:text-blue-400/30" curve="none" />
                <flux:chart.axis axis="x" field="label">
                    <flux:chart.axis.tick />
                    <flux:chart.axis.line />
                </flux:chart.axis>
                <flux:chart.axis axis="y" tick-prefix="$" :format="['notation' => 'compact']">
                    <flux:chart.axis.grid />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                <flux:chart.cursor />
            </flux:chart.svg>
            <flux:chart.tooltip>
                <flux:chart.tooltip.heading field="label" />
                <flux:chart.tooltip.value field="mrr" label="MRR" :format="['style' => 'currency', 'currency' => 'USD']" />
            </flux:chart.tooltip>
        </flux:chart>
    </flux:card>

    {{-- Signup vs Cancellation Trends --}}
    <flux:card class="mb-8">
        <flux:heading size="lg" class="mb-4">Signups vs Cancellations (12 Months)</flux:heading>
        <flux:chart wire:model="signupCancellationTrends" class="aspect-[3/1]">
            <flux:chart.svg>
                <flux:chart.line field="signups" class="text-green-500 dark:text-green-400" curve="none" />
                <flux:chart.line field="cancellations" class="text-red-500 dark:text-red-400" curve="none" />
                <flux:chart.axis axis="x" field="label">
                    <flux:chart.axis.tick />
                    <flux:chart.axis.line />
                </flux:chart.axis>
                <flux:chart.axis axis="y">
                    <flux:chart.axis.grid />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                <flux:chart.cursor />
            </flux:chart.svg>
            <flux:chart.tooltip>
                <flux:chart.tooltip.heading field="label" />
                <flux:chart.tooltip.value field="signups" label="Signups" />
                <flux:chart.tooltip.value field="cancellations" label="Cancellations" />
            </flux:chart.tooltip>
        </flux:chart>
        <div class="flex justify-center gap-4 pt-4">
            <flux:chart.legend label="Signups">
                <flux:chart.legend.indicator class="bg-green-400" />
            </flux:chart.legend>
            <flux:chart.legend label="Cancellations">
                <flux:chart.legend.indicator class="bg-red-400" />
            </flux:chart.legend>
        </div>
    </flux:card>

    {{-- Top Revenue Companies --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Top Revenue Generating Companies</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Company</flux:table.column>
                <flux:table.column>MRR</flux:table.column>
                <flux:table.column>Users</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($topCompanies as $company)
                    <flux:table.row>
                        <flux:table.cell>
                            <a href="{{ route('admin.companies.show', $company['id']) }}" class="text-blue-600 hover:underline">
                                {{ $company['name'] }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">${{ number_format($company['mrr'], 2) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ $company['users'] }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$company['status'] === 'active' ? 'green' : 'amber'">
                                {{ ucfirst($company['status']) }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center text-zinc-500">No companies found</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
        <a href="{{ route('admin.companies.index') }}" aria-label="Manage Companies">
            <flux:card size="sm" class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                <flux:heading class="flex items-center gap-2">
                    Manage Companies 
                    <flux:icon name="arrow-up-right" class="ml-auto text-zinc-400" variant="micro" />
                </flux:heading>
                <flux:text class="mt-2">View, suspend, or resume tenant companies</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('admin.billing.index') }}" aria-label="Billing Dashboard">
            <flux:card size="sm" class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                <flux:heading class="flex items-center gap-2">
                    Billing Dashboard 
                    <flux:icon name="arrow-up-right" class="ml-auto text-zinc-400" variant="micro" />
                </flux:heading>
                <flux:text class="mt-2">Manage subscriptions and failed payments</flux:text>
            </flux:card>
        </a>

        <a href="{{ route('admin.analytics.index') }}" aria-label="Analytics">
            <flux:card size="sm" class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                <flux:heading class="flex items-center gap-2">
                    Analytics 
                    <flux:icon name="arrow-up-right" class="ml-auto text-zinc-400" variant="micro" />
                </flux:heading>
                <flux:text class="mt-2">Cohort analysis and detailed insights</flux:text>
            </flux:card>
        </a>
    </div>
</div>
