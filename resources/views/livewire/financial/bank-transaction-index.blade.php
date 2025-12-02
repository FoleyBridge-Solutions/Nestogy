<div>
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Bank Transactions</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Review and reconcile transactions from your connected bank accounts</p>
            </div>
            <div class="flex gap-2">
                @if (!empty($selectedTransactions))
                    <flux:button wire:click="bulkAutoReconcile" variant="primary">
                        <flux:icon.check-circle class="w-4 h-4" />
                        Reconcile Selected ({{ count($selectedTransactions) }})
                    </flux:button>
                @endif
                <flux:button href="{{ route('financial.bank-connections.index') }}" variant="ghost">
                    <flux:icon.cog-6-tooth class="w-4 h-4" />
                    Manage Connections
                </flux:button>
            </div>
        </div>

        @if (session()->has('success'))
            <flux:callout variant="success" class="mb-4">
                {{ session('success') }}
            </flux:callout>
        @endif

        @if (session()->has('error'))
            <flux:callout variant="danger" class="mb-4">
                {{ session('error') }}
            </flux:callout>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <flux:card class="bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Total</div>
                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ number_format($summary['total']) }}</div>
            </flux:card>
            <flux:card class="bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800">
                <div class="text-sm font-medium text-orange-600 dark:text-orange-400">Unreconciled</div>
                <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ number_format($summary['unreconciled']) }}</div>
            </flux:card>
            <flux:card class="bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
                <div class="text-sm font-medium text-green-600 dark:text-green-400">Reconciled</div>
                <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ number_format($summary['reconciled']) }}</div>
            </flux:card>
            <flux:card class="bg-zinc-50 dark:bg-zinc-900/20 border-zinc-200 dark:border-zinc-800">
                <div class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Ignored</div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($summary['ignored']) }}</div>
            </flux:card>
        </div>

        <!-- Filters -->
        <flux:card>
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <flux:field>
                    <flux:label>Account</flux:label>
                    <flux:select wire:model.live="accountId">
                        <option value="">All Accounts</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->plaid_name ?? $account->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model.live="status">
                        <option value="">All</option>
                        <option value="unreconciled">Unreconciled</option>
                        <option value="reconciled">Reconciled</option>
                        <option value="ignored">Ignored</option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Type</flux:label>
                    <flux:select wire:model.live="type">
                        <option value="">All</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>From Date</flux:label>
                    <flux:input type="date" wire:model.live="dateFrom" />
                </flux:field>

                <flux:field>
                    <flux:label>To Date</flux:label>
                    <flux:input type="date" wire:model.live="dateTo" />
                </flux:field>

                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search transactions..." />
                </flux:field>
            </div>
            
            <div class="mt-4 flex justify-end">
                <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                    Reset Filters
                </flux:button>
            </div>
        </flux:card>
    </div>

    <!-- Transactions Table -->
    <flux:card>
        @if ($transactions->isEmpty())
            <div class="text-center py-12">
                <flux:icon.document-text class="w-16 h-16 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" />
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">No Transactions Found</h3>
                <p class="text-zinc-600 dark:text-zinc-400">Try adjusting your filters or connect a bank account</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox" wire:model.live="selectAll" class="rounded border-zinc-300 dark:border-zinc-600">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Description
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Account
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Category
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($transactions as $transaction)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <input type="checkbox" wire:model="selectedTransactions" value="{{ $transaction->id }}" class="rounded border-zinc-300 dark:border-zinc-600">
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $transaction->date->format('M d, Y') }}
                                    @if ($transaction->pending)
                                        <flux:badge variant="warning" size="sm">Pending</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $transaction->getDisplayName() }}
                                    </div>
                                    @if ($transaction->category)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                            {{ $transaction->getCategoriesString() }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $transaction->account->plaid_name ?? $transaction->account->name }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">
                                    @if ($transaction->categoryModel)
                                        <flux:badge variant="outline">{{ $transaction->categoryModel->name }}</flux:badge>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-medium">
                                    <span class="{{ $transaction->isIncome() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $transaction->isIncome() ? '+' : '-' }}{{ $transaction->getFormattedAmount() }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    @if ($transaction->is_ignored)
                                        <flux:badge color="zinc">Ignored</flux:badge>
                                    @elseif ($transaction->is_reconciled)
                                        <div class="flex items-center gap-1">
                                            <flux:badge color="green">Reconciled</flux:badge>
                                            @if ($transaction->reconciledPayment)
                                                <span class="text-xs text-zinc-500">with Payment #{{ $transaction->reconciledPayment->id }}</span>
                                            @elseif ($transaction->reconciledExpense)
                                                <span class="text-xs text-zinc-500">with Expense #{{ $transaction->reconciledExpense->id }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <flux:badge color="orange">Unreconciled</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                    @if ($transaction->is_ignored)
                                        <flux:button wire:click="unignoreTransaction({{ $transaction->id }})" variant="ghost" size="sm">
                                            Restore
                                        </flux:button>
                                    @elseif ($transaction->is_reconciled)
                                        <flux:button wire:click="unreconcile({{ $transaction->id }})" variant="ghost" size="sm">
                                            Unreconcile
                                        </flux:button>
                                    @else
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                            
                                            <flux:menu>
                                                <flux:menu.item wire:click="openReconcileModal({{ $transaction->id }})">
                                                    <flux:icon.check-circle class="w-4 h-4" />
                                                    Reconcile
                                                </flux:menu.item>
                                                @if ($transaction->isIncome())
                                                    <flux:menu.item wire:click="openCreateModal({{ $transaction->id }}, 'payment')">
                                                        <flux:icon.plus-circle class="w-4 h-4" />
                                                        Create Payment
                                                    </flux:menu.item>
                                                @else
                                                    <flux:menu.item wire:click="openCreateModal({{ $transaction->id }}, 'expense')">
                                                        <flux:icon.plus-circle class="w-4 h-4" />
                                                        Create Expense
                                                    </flux:menu.item>
                                                @endif
                                                <flux:menu.separator />
                                                <flux:menu.item wire:click="ignoreTransaction({{ $transaction->id }})">
                                                    <flux:icon.eye-slash class="w-4 h-4" />
                                                    Ignore
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </flux:card>

    <!-- Reconcile Modal -->
    @if ($showReconcileModal && $transactionToReconcile)
        <flux:modal wire:model="showReconcileModal" variant="flyout">
            <flux:card>
                <flux:heading size="lg">Reconcile Transaction</flux:heading>
                
                <div class="mt-4 space-y-4">
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Transaction</div>
                        <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $transactionToReconcile->getDisplayName() }}
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $transactionToReconcile->date->format('M d, Y') }} • 
                            <span class="{{ $transactionToReconcile->isIncome() ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transactionToReconcile->isIncome() ? '+' : '-' }}{{ $transactionToReconcile->getFormattedAmount() }}
                            </span>
                        </div>
                    </div>

                    @if (!empty($suggestedMatches))
                        <div>
                            <flux:label>Suggested Matches</flux:label>
                            <div class="space-y-2 mt-2">
                                @foreach ($suggestedMatches as $match)
                                    <div class="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer"
                                         wire:click="$set('reconcileType', '{{ $match['type'] }}'); $set('reconcileId', {{ $match['model']->id }})">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $match['type'] === 'payment' ? 'Payment' : ($match['type'] === 'expense' ? 'Expense' : 'Invoice') }} #{{ $match['model']->id }}
                                                </div>
                                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                                    {{ isset($match['model']->payment_date) ? $match['model']->payment_date->format('M d, Y') : (isset($match['model']->expense_date) ? $match['model']->expense_date->format('M d, Y') : $match['model']->created_at->format('M d, Y')) }}
                                                    • ${{ number_format($match['model']->amount ?? $match['model']->total, 2) }}
                                                </div>
                                            </div>
                                            <flux:badge :color="$match['confidence'] >= 80 ? 'green' : ($match['confidence'] >= 50 ? 'yellow' : 'zinc')">
                                                {{ $match['confidence'] }}% match
                                            </flux:badge>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <flux:label>Manual Selection</flux:label>
                        <div class="flex gap-2 mt-2">
                            <flux:select wire:model="reconcileType" class="flex-1">
                                <option value="payment">Payment</option>
                                <option value="expense">Expense</option>
                            </flux:select>
                            <flux:input wire:model="reconcileId" type="number" placeholder="Enter ID" class="flex-1" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <flux:button wire:click="$set('showReconcileModal', false)" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="reconcile" variant="primary">
                        Reconcile
                    </flux:button>
                </div>
            </flux:card>
        </flux:modal>
    @endif

    <!-- Create Modal -->
    @if ($showCreateModal && $transactionToReconcile)
        <flux:modal wire:model="showCreateModal" variant="flyout">
            <flux:card>
                <flux:heading size="lg">Create {{ $createType === 'payment' ? 'Payment' : 'Expense' }}</flux:heading>
                
                <div class="mt-4 space-y-4">
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">From Transaction</div>
                        <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $transactionToReconcile->getDisplayName() }}
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $transactionToReconcile->getFormattedAmount() }} on {{ $transactionToReconcile->date->format('M d, Y') }}
                        </div>
                    </div>

                    @if ($createType === 'payment')
                        <flux:field>
                            <flux:label>Client ID (Optional)</flux:label>
                            <flux:input wire:model="createData.client_id" type="number" placeholder="Enter client ID" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Invoice ID (Optional)</flux:label>
                            <flux:input wire:model="createData.invoice_id" type="number" placeholder="Enter invoice ID" />
                        </flux:field>
                    @else
                        <flux:field>
                            <flux:label>Category ID (Optional)</flux:label>
                            <flux:input wire:model="createData.category_id" type="number" placeholder="Enter category ID" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Client ID (Optional)</flux:label>
                            <flux:input wire:model="createData.client_id" type="number" placeholder="Enter client ID" />
                        </flux:field>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="create" variant="primary">
                        Create & Reconcile
                    </flux:button>
                </div>
            </flux:card>
        </flux:modal>
    @endif
</div>
