@extends('layouts.app')

@section('title', 'Payment Methods')

@section('content')
<flux:main class="space-y-6">
    <flux:header>
        <flux:heading>Payment Methods</flux:heading>
        <flux:actions>
            <flux:button href="{{ route('financial.payment-methods.create') }}" variant="primary">
                <flux:icon name="plus" size="sm" />
                Add Payment Method
            </flux:button>
        </flux:actions>
    </flux:header>

    <flux:card>
        <flux:card.header>
            <flux:card.title>Payment Methods Overview</flux:card.title>
        </flux:card.header>
        
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <flux:stat>
                    <flux:stat.label>Total Methods</flux:stat.label>
                    <flux:stat.value>{{ $paymentMethods->count() ?? 0 }}</flux:stat.value>
                    <flux:stat.description>Configured payment methods</flux:stat.description>
                </flux:stat>
                
                <flux:stat>
                    <flux:stat.label>Active Methods</flux:stat.label>
                    <flux:stat.value>{{ $activeMethods ?? 0 }}</flux:stat.value>
                    <flux:stat.description>Currently accepting payments</flux:stat.description>
                </flux:stat>
                
                <flux:stat>
                    <flux:stat.label>Default Method</flux:stat.label>
                    <flux:stat.value>{{ $defaultMethod ?? 'None' }}</flux:stat.value>
                    <flux:stat.description>Primary payment method</flux:stat.description>
                </flux:stat>
                
                <flux:stat>
                    <flux:stat.label>Last Transaction</flux:stat.label>
                    <flux:stat.value>{{ $lastTransaction ?? 'Never' }}</flux:stat.value>
                    <flux:stat.description>Most recent payment</flux:stat.description>
                </flux:stat>
            </div>
        
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <flux:card>
            <flux:card.header>
                <flux:card.title>Credit Cards</flux:card.title>
            </flux:card.header>
            
                <div class="space-y-4">
                    @forelse($creditCards ?? [] as $card)
                    <div class="flex items-center justify-between p-4 border rounded-lg">
                        <div class="flex items-center space-x-4">
                            <flux:icon name="credit-card" size="lg" />
                            <div>
                                <div class="font-semibold">•••• •••• •••• {{ $card->last_four }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ $card->brand }} - Expires {{ $card->exp_month }}/{{ $card->exp_year }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($card->is_default)
                            <flux:badge variant="primary">Default</flux:badge>
                            @endif
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost">
                                    <flux:icon name="ellipsis-horizontal" />
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item wire:click="setDefault({{ $card->id }})" icon="star">
                                        Set as Default
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="editCard({{ $card->id }})" icon="pencil">
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="removeCard({{ $card->id }})" icon="trash" variant="danger">
                                        Remove
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500">
                        No credit cards configured
                    </div>
                    @endforelse
                </div>
            
        </flux:card>

        <flux:card>
            <flux:card.header>
                <flux:card.title>Bank Accounts</flux:card.title>
            </flux:card.header>
            
                <div class="space-y-4">
                    @forelse($bankAccounts ?? [] as $account)
                    <div class="flex items-center justify-between p-4 border rounded-lg">
                        <div class="flex items-center space-x-4">
                            <flux:icon name="building-library" size="lg" />
                            <div>
                                <div class="font-semibold">{{ $account->bank_name }}</div>
                                <div class="text-sm text-gray-500">
                                    •••• {{ $account->last_four }} - {{ $account->account_type }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($account->is_default)
                            <flux:badge variant="primary">Default</flux:badge>
                            @endif
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost">
                                    <flux:icon name="ellipsis-horizontal" />
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item wire:click="setDefault({{ $account->id }})" icon="star">
                                        Set as Default
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="verifyAccount({{ $account->id }})" icon="check-badge">
                                        Verify
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="editAccount({{ $account->id }})" icon="pencil">
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="removeAccount({{ $account->id }})" icon="trash" variant="danger">
                                        Remove
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500">
                        No bank accounts configured
                    </div>
                    @endforelse
                </div>
            
        </flux:card>
    </div>

    <flux:card>
        <flux:card.header>
            <flux:card.title>Other Payment Methods</flux:card.title>
        </flux:card.header>
        
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 border rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <flux:icon name="currency-dollar" size="md" />
                            <span class="font-semibold">PayPal</span>
                        </div>
                        <flux:toggle wire:model="paypalEnabled" />
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $paypalEmail ?? 'Not configured' }}
                    </div>
                </div>

                <div class="p-4 border rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <flux:icon name="device-mobile" size="md" />
                            <span class="font-semibold">Stripe</span>
                        </div>
                        <flux:toggle wire:model="stripeEnabled" />
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $stripeConnected ? 'Connected' : 'Not connected' }}
                    </div>
                </div>

                <div class="p-4 border rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <flux:icon name="square-3-stack-3d" size="md" />
                            <span class="font-semibold">Square</span>
                        </div>
                        <flux:toggle wire:model="squareEnabled" />
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $squareConnected ? 'Connected' : 'Not connected' }}
                    </div>
                </div>
            </div>
        
    </flux:card>
</flux:main>
@endsection