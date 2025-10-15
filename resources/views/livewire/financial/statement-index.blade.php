<div>
    <div class="mb-6">
        <flux:card>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <flux:field>
                        <flux:label>Client</flux:label>
                        <flux:select wire:model.live="client_id" placeholder="All Clients">
                            <option value="">All Clients</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Start Date</flux:label>
                        <flux:input type="date" wire:model.live="start_date" />
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>End Date</flux:label>
                        <flux:input type="date" wire:model.live="end_date" />
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>View</flux:label>
                        <flux:select wire:model.live="view_mode">
                            <option value="combined">Combined</option>
                            <option value="by_payment">By Payment</option>
                            <option value="by_invoice">By Invoice</option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>
        </flux:card>
    </div>

    <flux:card>
        @if($applications->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Client</flux:table.column>
                    <flux:table.column>Payment</flux:table.column>
                    <flux:table.column>Invoice</flux:table.column>
                    <flux:table.column>Amount</flux:table.column>
                    <flux:table.column>Payment Method</flux:table.column>
                    <flux:table.column>Applied By</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($applications as $application)
                    <flux:table.row :key="'app-' . $application->id">
                        <flux:table.cell>
                            {{ $application->applied_date ? \Carbon\Carbon::parse($application->applied_date)->format('M d, Y') : '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <a href="{{ route('clients.show', $application->payment->client) }}" class="text-blue-600 hover:text-blue-800">
                                {{ $application->payment->client->name }}
                            </a>
                        </flux:table.cell>

                        <flux:table.cell>
                            <a href="{{ route('financial.payments.show', $application->payment) }}" class="text-blue-600 hover:text-blue-800">
                                {{ $application->payment->payment_reference }}
                            </a>
                            <div class="text-xs text-zinc-500">
                                ${{ number_format($application->payment->amount, 2) }}
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($application->applicable_type === 'App\\Models\\Invoice' && $application->applicable)
                                <a href="{{ route('financial.invoices.show', $application->applicable) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $application->applicable->getFullNumber() }}
                                </a>
                                <div class="text-xs text-zinc-500">
                                    Due: {{ $application->applicable->due_date->format('M d, Y') }}
                                </div>
                            @else
                                <span class="text-zinc-500">-</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text variant="strong" class="text-green-600">
                                ${{ number_format($application->amount, 2) }}
                            </flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm">
                                {{ ucfirst(str_replace('_', ' ', $application->payment->payment_method)) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $application->appliedBy->name ?? 'System' }}
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $applications->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <flux:icon.document-text class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No Payment Applications</flux:heading>
                <flux:text class="mt-2">No payment applications found for the selected criteria.</flux:text>
            </div>
        @endif
    </flux:card>
</div>
