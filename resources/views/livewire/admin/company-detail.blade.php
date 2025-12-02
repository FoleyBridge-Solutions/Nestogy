
<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $company->name }}</flux:heading>
        <flux:text class="mt-2">Company ID: {{ $company->id }} | Created: {{ $company->created_at->format('M d, Y') }}</flux:text>
    </div>

    {{-- Status Badge --}}
    <div class="mb-6">
        <flux:badge :color="$company->is_active ? 'green' : 'red'" class="text-lg px-4 py-2">
            {{ $company->is_active ? 'Active' : 'Suspended' }}
        </flux:badge>
    </div>

    {{-- Tabs --}}
    <flux:tabs wire:model="activeTab" class="mb-6">
        <flux:tab name="overview">Overview</flux:tab>
        <flux:tab name="subscription">Subscription</flux:tab>
        <flux:tab name="users">Users</flux:tab>
    </flux:tabs>

    {{-- Overview Tab --}}
    @if($activeTab === 'overview')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Company Information</flux:heading>
                <div class="space-y-2">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Name</flux:text>
                        <flux:text class="font-medium">{{ $company->name }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500">Email</flux:text>
                        <flux:text class="font-medium">{{ $company->email ?? 'N/A' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500">Phone</flux:text>
                        <flux:text class="font-medium">{{ $company->phone ?? 'N/A' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500">Address</flux:text>
                        <flux:text class="font-medium">{{ $company->address ?? 'N/A' }}</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Subscription Summary</flux:heading>
                @if($subscription)
                    <div class="space-y-2">
                        <div>
                            <flux:text class="text-sm text-zinc-500">Status</flux:text>
                            <flux:badge :color="match($subscription->status) {
                                'active' => 'green',
                                'trialing' => 'blue',
                                'past_due' => 'amber',
                                default => 'zinc'
                            }">
                                {{ ucfirst($subscription->status) }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500">Monthly Amount</flux:text>
                            <flux:text class="font-medium text-lg">${{ number_format($subscription->monthly_amount, 2) }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500">Users</flux:text>
                            <flux:text class="font-medium">{{ $subscription->current_user_count }} / {{ $subscription->max_users }}</flux:text>
                        </div>
                        @if($subscription->trial_ends_at)
                            <div>
                                <flux:text class="text-sm text-zinc-500">Trial Ends</flux:text>
                                <flux:text class="font-medium">{{ $subscription->trial_ends_at->format('M d, Y') }}</flux:text>
                            </div>
                        @endif
                    </div>
                @else
                    <flux:text>No active subscription</flux:text>
                @endif
            </flux:card>
        </div>
    @endif

    {{-- Subscription Tab --}}
    @if($activeTab === 'subscription')
        <flux:card class="space-y-4">
            <flux:heading size="lg">Subscription Details</flux:heading>
            @if($subscription)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div>
                            <flux:text class="text-sm text-zinc-500">Status</flux:text>
                            <flux:badge :color="match($subscription->status) {
                                'active' => 'green',
                                'trialing' => 'blue',
                                'past_due' => 'amber',
                                default => 'zinc'
                            }">
                                {{ ucfirst($subscription->status) }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500">Monthly Amount</flux:text>
                            <flux:text class="font-medium">${{ number_format($subscription->monthly_amount, 2) }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500">Max Users</flux:text>
                            <flux:text class="font-medium">{{ $subscription->max_users }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500">Current Users</flux:text>
                            <flux:text class="font-medium">{{ $subscription->current_user_count }}</flux:text>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <flux:text class="text-sm text-zinc-500">Stripe Customer ID</flux:text>
                            <flux:text class="font-mono text-sm">{{ $subscription->stripe_customer_id ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm text-zinc-500">Stripe Subscription ID</flux:text>
                            <flux:text class="font-mono text-sm">{{ $subscription->stripe_subscription_id ?? 'N/A' }}</flux:text>
                        </div>
                        @if($subscription->current_period_start)
                            <div>
                                <flux:text class="text-sm text-zinc-500">Current Period Start</flux:text>
                                <flux:text class="font-medium">{{ $subscription->current_period_start->format('M d, Y') }}</flux:text>
                            </div>
                        @endif
                        @if($subscription->current_period_end)
                            <div>
                                <flux:text class="text-sm text-zinc-500">Current Period End</flux:text>
                                <flux:text class="font-medium">{{ $subscription->current_period_end->format('M d, Y') }}</flux:text>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <flux:text>No active subscription for this company.</flux:text>
            @endif
        </flux:card>
    @endif

    {{-- Users Tab --}}
    @if($activeTab === 'users')
        <flux:card>
            <flux:heading size="lg" class="mb-4">Company Users</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($allUsers as $user)
                        <flux:table.row>
                            <flux:table.cell>{{ $user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$user->status ? 'green' : 'red'">
                                    {{ $user->status ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $user->created_at->format('M d, Y') }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500">No users found</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- Actions --}}
    <div class="mt-6">
        <flux:button href="{{ route('admin.companies.index') }}" variant="ghost">
            &larr; Back to Companies
        </flux:button>
    </div>
</div>
