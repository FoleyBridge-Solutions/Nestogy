
<div>
    <flux:heading size="xl" class="mb-6">Billing Dashboard</flux:heading>

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <flux:card class="space-y-2">
            <flux:text>Total MRR</flux:text>
            <flux:heading size="xl" class="tabular-nums">${{ number_format($stats['mrr'], 2) }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text>Active Subscriptions</flux:text>
            <flux:heading size="xl" class="tabular-nums">{{ number_format($stats['active_subscriptions']) }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text>Trial Subscriptions</flux:text>
            <flux:heading size="xl" class="tabular-nums">{{ number_format($stats['trialing_subscriptions']) }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text>ARPU</flux:text>
            <flux:heading size="xl" class="tabular-nums">${{ number_format($stats['arpu'], 2) }}</flux:heading>
        </flux:card>
    </div>

    {{-- Failed Payments --}}
    @if(count($failedPayments) > 0)
        <flux:card class="mb-8">
            <flux:heading size="lg" class="mb-4">Failed Payments (Past Due)</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Company</flux:table.column>
                    <flux:table.column>Amount</flux:table.column>
                    <flux:table.column>Period End</flux:table.column>
                    <flux:table.column>Stripe Customer</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($failedPayments as $payment)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('admin.companies.show', $payment['company_id']) }}" class="text-blue-600 hover:underline">
                                    {{ $payment['company_name'] }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell class="tabular-nums">${{ number_format($payment['amount'], 2) }}</flux:table.cell>
                            <flux:table.cell>
                                {{ \Carbon\Carbon::parse($payment['current_period_end'])->format('M d, Y') }}
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-sm">{{ $payment['stripe_customer_id'] }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @else
        <flux:callout variant="success" class="mb-8">
            No failed payments at this time.
        </flux:callout>
    @endif

    {{-- Active Subscriptions --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Recent Active Subscriptions</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Company</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Users</flux:table.column>
                <flux:table.column>Period End</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($activeSubscriptions as $subscription)
                    <flux:table.row>
                        <flux:table.cell>
                            <a href="{{ route('admin.companies.show', $subscription->company_id) }}" class="text-blue-600 hover:underline">
                                {{ $subscription->company->name ?? 'N/A' }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">${{ number_format($subscription->monthly_amount, 2) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ $subscription->current_user_count }} / {{ $subscription->max_users }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $subscription->current_period_end?->format('M d, Y') ?? 'N/A' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="green">{{ ucfirst($subscription->status) }}</flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500">No active subscriptions found</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
