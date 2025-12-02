<div class="space-y-6">
    {{-- Header --}}
    <flux:card>
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold">{{ $contract->title }}</h1>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                        <span><strong>Contract #:</strong> {{ $contract->contract_number }}</span>
                        <flux:separator vertical class="h-4" />
                        <span><strong>Client:</strong> {{ $contract->client?->name }}</span>
                        <flux:separator vertical class="h-4" />
                        <span><strong>Type:</strong> {{ ucfirst($contract->contract_type) }}</span>
                        <flux:separator vertical class="h-4" />
                        <span><strong>Value:</strong> ${{ number_format($contract->contract_value, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <flux:badge variant="{{ 
                            $contract->status === 'active' ? 'success' : 
                            ($contract->status === 'draft' ? 'warning' : 
                            ($contract->status === 'expired' ? 'danger' : 'info'))
                        }}">
                            {{ ucfirst($contract->status) }}
                        </flux:badge>
                        <flux:badge variant="outline">
                            {{ ucfirst($contract->signature_status) }}
                        </flux:badge>
                        @if($contract->auto_renewal)
                            <flux:badge variant="outline" color="green">
                                <flux:icon.arrow-path class="mr-1" />
                                Auto-renewal
                            </flux:badge>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <flux:button variant="outline" size="sm" href="{{ route('contracts.edit', $contract) }}">
                        <flux:icon.pencil class="mr-2" />
                        Edit
                    </flux:button>
                    
                    @if($contract->status === 'draft' && Auth::user()->can('approve', $contract))
                        <flux:button variant="primary" size="sm" wire:click="approveContract">
                            <flux:icon.check class="mr-2" />
                            Approve
                        </flux:button>
                    @endif
                    
                    @if($contract->signature_status === 'pending' && Auth::user()->can('sendForSignature', $contract))
                        <flux:button variant="primary" size="sm" wire:click="sendForSignature">
                            <flux:icon.paper-airplane class="mr-2" />
                            Send for Signature
                        </flux:button>
                    @endif
                    
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm">
                            <flux:icon.ellipsis-horizontal />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item href="{{ route('contracts.edit', $contract) }}">
                                <flux:icon.pencil class="mr-2" />
                                Edit Contract
                            </flux:menu.item>
                            <flux:menu.item wire:click="downloadPdf">
                                <flux:icon.arrow-down-tray class="mr-2" />
                                Download PDF
                            </flux:menu.item>
                            <flux:menu.item href="{{ route('contracts.version-history', $contract) }}">
                                <flux:icon.clock class="mr-2" />
                                Version History
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item variant="danger" wire:click="$set('showDeleteModal', true)">
                                <flux:icon.trash class="mr-2" />
                                Delete Contract
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>
    </flux:card>

    {{-- AI Insights Widget --}}
    <x-ai-insights 
        :enabled="$aiEnabled"
        :loading="$aiLoading"
        :insights="$aiInsights"
    />

    {{-- Contract Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Overview --}}
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Contract Overview</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Contract Number:</span>
                        <span class="font-medium">{{ $contract->contract_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Start Date:</span>
                        <span class="font-medium">{{ $contract->start_date?->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">End Date:</span>
                        <span class="font-medium">{{ $contract->end_date?->format('M d, Y') ?? 'Open-ended' }}</span>
                    </div>
                    @if($contract->term_months)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Term:</span>
                            <span class="font-medium">{{ $contract->term_months }} months</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-600">Contract Value:</span>
                        <span class="font-medium">${{ number_format($contract->contract_value, 2) }}</span>
                    </div>
                    @if($contract->payment_terms)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Payment Terms:</span>
                            <span class="font-medium">{{ $contract->payment_terms }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>

        {{-- Parties --}}
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Parties</h3>
                <div class="space-y-4">
                    <div>
                        <div class="font-medium text-gray-900">{{ config('app.name') }}</div>
                        <div class="text-sm text-gray-600">Service Provider</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">{{ $contract->client?->name }}</div>
                        <div class="text-sm text-gray-600">Client</div>
                        <div class="text-sm text-gray-500">{{ $contract->client?->email }}</div>
                    </div>
                    @if($contract->createdBy)
                        <div class="pt-3 border-t">
                            <div class="text-sm text-gray-600">Created by:</div>
                            <div class="font-medium">{{ $contract->createdBy->name }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Description --}}
    @if($contract->description)
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Description</h3>
                <div class="prose max-w-none">
                    {{ $contract->description }}
                </div>
            </div>
        </flux:card>
    @endif
</div>
