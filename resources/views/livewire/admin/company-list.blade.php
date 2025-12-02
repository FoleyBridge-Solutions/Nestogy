
<div>
    <flux:heading size="xl" class="mb-6">Manage Companies</flux:heading>

    {{-- Flash Messages --}}
    @if (session('success'))
        <flux:callout variant="success" class="mb-4">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" class="mb-4">
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <flux:field>
                <flux:label>Search</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Company name or email..." />
            </flux:field>

            {{-- Status Filter --}}
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model.live="statusFilter">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </flux:select>
            </flux:field>

            {{-- Subscription Filter --}}
            <flux:field>
                <flux:label>Subscription</flux:label>
                <flux:select wire:model.live="subscriptionFilter">
                    <option value="all">All Subscriptions</option>
                    <option value="active">Active</option>
                    <option value="trialing">Trialing</option>
                    <option value="past_due">Past Due</option>
                    <option value="canceled">Canceled</option>
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    {{-- Companies Table --}}
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Company</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>MRR</flux:table.column>
                <flux:table.column>Users</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Subscription</flux:table.column>
                <flux:table.column>Created</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($companies as $company)
                    <flux:table.row :key="$company->id">
                        <flux:table.cell>
                            <a href="{{ route('admin.companies.show', $company) }}" class="text-blue-600 hover:underline font-medium">
                                {{ $company->name }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $company->email ?? 'N/A' }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            ${{ number_format($company->subscription?->monthly_amount ?? 0, 2) }}
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            {{ $company->subscription?->current_user_count ?? 0 }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$company->is_active ? 'green' : 'red'">
                                {{ $company->is_active ? 'Active' : 'Suspended' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($company->subscription)
                                <flux:badge :color="match($company->subscription->status) {
                                    'active' => 'green',
                                    'trialing' => 'blue',
                                    'past_due' => 'amber',
                                    default => 'zinc'
                                }">
                                    {{ ucfirst($company->subscription->status) }}
                                </flux:badge>
                            @else
                                <flux:badge color="zinc">None</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="text-sm">{{ $company->created_at->format('M d, Y') }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                @if($company->is_active)
                                    <flux:button 
                                        size="sm" 
                                        variant="danger" 
                                        wire:click="openSuspendModal({{ $company->id }})"
                                    >
                                        Suspend
                                    </flux:button>
                                @else
                                    <flux:button 
                                        size="sm" 
                                        variant="primary" 
                                        wire:click="resumeCompany({{ $company->id }})"
                                    >
                                        Resume
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center text-zinc-500 py-8">
                            No companies found matching your filters.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $companies->links() }}
        </div>
    </flux:card>

    {{-- Suspend Modal --}}
    <flux:modal wire:model="showSuspendModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Suspend Company</flux:heading>
            <flux:text class="mt-2">
                Please provide a reason for suspending this company. This will immediately log out all users and prevent access.
            </flux:text>
        </div>

        <flux:field>
            <flux:label>Suspension Reason</flux:label>
            <flux:textarea 
                wire:model="suspensionReason" 
                rows="4" 
                placeholder="e.g., Payment failed after multiple retry attempts"
            />
            <flux:error name="suspensionReason" />
        </flux:field>

        <div class="flex gap-4">
            <flux:spacer />
            <flux:button variant="ghost" wire:click="closeSuspendModal">Cancel</flux:button>
            <flux:button variant="danger" wire:click="suspendCompany">Suspend Company</flux:button>
        </div>
    </flux:modal>
</div>
