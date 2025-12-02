<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Bank Connections</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Connect and manage your bank accounts for automatic transaction syncing</p>
            </div>
            <flux:button wire:click="initiateConnection" icon="plus" variant="primary" :disabled="$isLoading">
                Connect Bank Account
            </flux:button>
        </div>

        @if (session()->has('success'))
            <flux:alert variant="success" class="mb-4">
                {{ session('success') }}
            </flux:alert>
        @endif

        @if (session()->has('error'))
            <flux:alert variant="danger" class="mb-4">
                {{ session('error') }}
            </flux:alert>
        @endif
    </div>

    @if ($items->isEmpty())
        <flux:card class="text-center py-12">
            <flux:icon.building-library class="w-16 h-16 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" />
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">No Bank Connections</h3>
            <p class="text-zinc-600 dark:text-zinc-400 mb-4">Connect your bank account to automatically sync transactions</p>
            <flux:button wire:click="initiateConnection" icon="plus" :disabled="$isLoading">
                Connect Your First Bank
            </flux:button>
        </flux:card>
    @else
        <div class="grid gap-4">
            @foreach ($items as $item)
                <flux:card>
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4">
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                <flux:icon.building-library class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $item->institution_name }}
                                    </h3>
                                    <flux:badge :color="$item->getStatusBadgeColor()">
                                        {{ $item->getStatusLabel() }}
                                    </flux:badge>
                                </div>
                                
                                <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    <div class="flex items-center gap-4">
                                        <span>{{ $item->accounts->count() }} account(s)</span>
                                        <span>•</span>
                                        <span>{{ $item->bank_transactions_count }} transactions</span>
                                        @if ($item->unreconciled_count > 0)
                                            <span>•</span>
                                            <span class="text-orange-600 dark:text-orange-400 font-medium">
                                                {{ $item->unreconciled_count }} unreconciled
                                            </span>
                                        @endif
                                    </div>
                                    @if ($item->last_synced_at)
                                        <div>Last synced: {{ $item->last_synced_at->diffForHumans() }}</div>
                                    @endif
                                </div>

                                @if ($item->hasError())
                                    <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 rounded text-sm">
                                        <p class="text-red-800 dark:text-red-200 font-medium">{{ $item->error_code }}</p>
                                        <p class="text-red-600 dark:text-red-300">{{ $item->error_message }}</p>
                                    </div>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($item->accounts as $account)
                                        <flux:badge variant="outline">
                                            {{ $account->plaid_name }}
                                            @if ($account->plaid_mask)
                                                ****{{ $account->plaid_mask }}
                                            @endif
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($item->needsReauth())
                                <flux:button wire:click="reauthorize({{ $item->id }})" variant="warning" size="sm">
                                    <flux:icon.arrow-path class="w-4 h-4" />
                                    Reauthorize
                                </flux:button>
                            @else
                                <flux:button wire:click="syncConnection({{ $item->id }})" variant="ghost" size="sm">
                                    <flux:icon.arrow-path class="w-4 h-4" />
                                    Sync
                                </flux:button>
                            @endif
                            
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                
                                <flux:menu>
                                    <flux:menu.item href="{{ route('financial.bank-connections.show', $item) }}">
                                        View Details
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ route('financial.bank-transactions.index', ['accountId' => $item->accounts->first()?->id]) }}">
                                        View Transactions
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="confirmDelete({{ $item->id }})" variant="danger">
                                        Remove Connection
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" variant="flyout">
        <flux:card>
            <flux:heading size="lg">Remove Bank Connection</flux:heading>
            
            <div class="mt-4">
                <p class="text-zinc-700 dark:text-zinc-300">
                    Are you sure you want to remove this bank connection? This will disconnect the bank account from Plaid.
                </p>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">
                    Note: Historical transactions and reconciliation data will be preserved.
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="deleteConnection" variant="danger">
                    Remove Connection
                </flux:button>
            </div>
        </flux:card>
    </flux:modal>

    @script
    <script>
        // Plaid Link Handler
        let plaidHandler = null;

        Livewire.on('openPlaidLink', (event) => {
            const linkToken = event.linkToken;
            const isUpdate = event.update || false;

            if (plaidHandler) {
                plaidHandler.destroy();
            }

            plaidHandler = Plaid.create({
                token: linkToken,
                onSuccess: (public_token, metadata) => {
                    // Send to backend
                    fetch('{{ route('financial.bank-connections.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            public_token: public_token,
                            institution_id: metadata.institution.institution_id,
                            institution_name: metadata.institution.name
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Failed to connect bank account');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to connect bank account');
                    });
                },
                onExit: (err, metadata) => {
                    @this.set('isLoading', false);
                    if (err != null) {
                        console.error('Plaid Link error:', err);
                    }
                }
            });

            plaidHandler.open();
        });
    </script>
    @endscript
</div>
