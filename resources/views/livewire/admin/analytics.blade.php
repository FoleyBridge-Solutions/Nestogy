
<div>
    <flux:heading size="xl" class="mb-6">Platform Analytics</flux:heading>

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <flux:card class="space-y-2">
            <flux:text>ARPU</flux:text>
            <flux:heading size="xl" class="tabular-nums">${{ number_format($stats['arpu'], 2) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">Avg Revenue Per User</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text>LTV</flux:text>
            <flux:heading size="xl" class="tabular-nums">${{ number_format($stats['ltv'], 2) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">Customer Lifetime Value</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text>Churn Rate</flux:text>
            <flux:heading size="xl" class="tabular-nums">{{ number_format($stats['churn_rate'], 2) }}%</flux:heading>
            <flux:text class="text-sm text-zinc-500">Last 30 days</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text>Trial Conversion</flux:text>
            <flux:heading size="xl" class="tabular-nums">{{ number_format($stats['trial_conversion_rate'], 2) }}%</flux:heading>
            <flux:text class="text-sm text-zinc-500">Trial to paid conversion</flux:text>
        </flux:card>
    </div>

    {{-- Cohort Analysis --}}
    <flux:card class="mb-8">
        <flux:heading size="lg" class="mb-4">Cohort Analysis (Retention by Signup Month)</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Cohort Month</flux:table.column>
                <flux:table.column>Signups</flux:table.column>
                <flux:table.column>Still Active</flux:table.column>
                <flux:table.column>Retention Rate</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($cohortAnalysis as $cohort)
                    <flux:table.row>
                        <flux:table.cell>{{ $cohort['cohort_month'] }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format($cohort['signups']) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format($cohort['still_active']) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            <flux:badge :color="$cohort['retention_rate'] >= 80 ? 'green' : ($cohort['retention_rate'] >= 60 ? 'amber' : 'red')">
                                {{ number_format($cohort['retention_rate'], 1) }}%
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center text-zinc-500">No cohort data available</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Top Revenue Companies --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Top 10 Revenue Generating Companies</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Rank</flux:table.column>
                <flux:table.column>Company</flux:table.column>
                <flux:table.column>MRR</flux:table.column>
                <flux:table.column>Users</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($topCompanies as $index => $company)
                    <flux:table.row>
                        <flux:table.cell class="font-bold">{{ $index + 1 }}</flux:table.cell>
                        <flux:table.cell>
                            <a href="{{ route('admin.companies.show', $company['id']) }}" class="text-blue-600 hover:underline">
                                {{ $company['name'] }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums font-medium">${{ number_format($company['mrr'], 2) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ $company['users'] }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$company['status'] === 'active' ? 'green' : 'amber'">
                                {{ ucfirst($company['status']) }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500">No companies found</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
