@extends('layouts.app')

@section('title', 'Email Accounts')

@section('content')
@php
    $sidebarContext = 'email';
@endphp

<div class="container-fluid h-full flex flex-col">
    <!-- Compact Header with Actions -->
    <flux:card class="mb-3">
        <div class="flex items-center justify-between mb-3">
            <div>
                <flux:heading>Email Accounts</flux:heading>
                <flux:text size="sm">{{ $accounts->count() }} total accounts</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button
                    variant="primary"
                    size="sm"
                    icon="plus"
                    href="{{ route('email.accounts.create') }}"
                >
                    Add Account
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Accounts Table -->
    <flux:card class="flex-1">
        @if($accounts->count() > 0)
            <div class="overflow-x-auto h-full">
                <flux:table class="text-base">
                    <flux:table.columns>
                        <flux:table.column class="w-40">Account Name</flux:table.column>
                        <flux:table.column class="w-48">Email Address</flux:table.column>
                        <flux:table.column class="w-32">Provider & Status</flux:table.column>
                        <flux:table.column class="w-40">Server Details</flux:table.column>
                        <flux:table.column class="w-24">Last Synced</flux:table.column>
                        <flux:table.column class="w-16"></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($accounts as $account)
                            <flux:table.row class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <!-- Account Name -->
                                <flux:table.cell class="py-2">
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" class="flex-shrink-0">
                                            <flux:icon.envelope class="w-4 h-4" />
                                        </flux:avatar>
                                        <div class="min-w-0">
                                            <div class="font-medium truncate">{{ $account->name }}</div>
                                            @if($account->is_default)
                                                <flux:badge color="green" size="xs">Default</flux:badge>
                                            @endif
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <!-- Email Address -->
                                <flux:table.cell class="py-2">
                                    <flux:link href="mailto:{{ $account->email_address }}" class="text-sm truncate block">
                                        {{ $account->email_address }}
                                    </flux:link>
                                </flux:table.cell>

                                <!-- Provider & Status -->
                                <flux:table.cell class="py-2">
                                    <div class="flex flex-wrap gap-1">
                                        <flux:badge color="blue" size="xs">{{ ucfirst($account->provider) }}</flux:badge>
                                        @if(!$account->is_active)
                                            <flux:badge color="red" size="xs">Inactive</flux:badge>
                                        @endif
                                        @if($account->sync_error)
                                            <flux:tooltip content="{{ $account->sync_error }}">
                                                <flux:badge color="amber" size="xs">Error</flux:badge>
                                            </flux:tooltip>
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <!-- Server Details -->
                                <flux:table.cell class="py-2">
                                    <div class="space-y-0.5 text-sm text-zinc-600">
                                        @if($account->connection_type === 'oauth')
                                            <div class="flex items-center gap-1">
                                                <flux:icon.key class="w-3 h-3 flex-shrink-0" />
                                                <span class="text-sm font-medium text-emerald-600">OAuth Connection</span>
                                            </div>
                                            <div class="text-xs text-zinc-500">
                                                {{ ucfirst(str_replace('_', ' ', $account->oauth_provider)) }}
                                            </div>
                                        @else
                                            <div class="flex items-center gap-1">
                                                <flux:icon.server class="w-3 h-3 flex-shrink-0" />
                                                <span class="truncate">{{ $account->imap_host }}:{{ $account->imap_port }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <flux:icon.paper-airplane class="w-3 h-3 flex-shrink-0" />
                                                <span class="truncate">{{ $account->smtp_host }}:{{ $account->smtp_port }}</span>
                                            </div>
                                        @endif
                                        <div class="text-xs text-zinc-500">
                                            {{ $account->folders->count() }} folders
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <!-- Last Synced -->
                                <flux:table.cell class="py-2">
                                    @if($account->last_synced_at)
                                        <div class="text-sm" title="{{ $account->last_synced_at->format('M j, Y g:i A') }}">
                                            <div>{{ $account->last_synced_at->format('M j') }}</div>
                                            <div class="text-zinc-500">{{ $account->last_synced_at->format('g:i A') }}</div>
                                        </div>
                                    @else
                                        <span class="text-zinc-400 text-sm">Never</span>
                                    @endif
                                </flux:table.cell>

                                <!-- Actions -->
                                <flux:table.cell class="py-2">
                                    <flux:dropdown align="end">
                                        <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal">Actions</flux:button>
                                        <flux:menu>
                                            <flux:menu.item
                                                icon="eye"
                                                href="{{ route('email.accounts.show', $account) }}"
                                            >
                                                View Details
                                            </flux:menu.item>
                                            <flux:menu.item
                                                icon="pencil"
                                                href="{{ route('email.accounts.edit', $account) }}"
                                            >
                                                Edit Account
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                icon="wrench-screwdriver"
                                                wire:click="testConnection({{ $account->id }})"
                                            >
                                                Test Connection
                                            </flux:menu.item>
                                            <flux:menu.item
                                                icon="arrow-path"
                                                wire:click="syncAccount({{ $account->id }})"
                                            >
                                                Sync Now
                                            </flux:menu.item>
                                            @if(!$account->is_default)
                                                <flux:menu.item
                                                    icon="star"
                                                    wire:click="setDefault({{ $account->id }})"
                                                >
                                                    Set as Default
                                                </flux:menu.item>
                                            @endif
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                icon="trash"
                                                wire:click="deleteAccount({{ $account->id }})"
                                                variant="danger"
                                            >
                                                Delete Account
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @else
            <div class="text-center py-12">
                <flux:icon.envelope class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No email accounts found</flux:heading>
                <flux:text class="mt-2">
                    Get started by adding your first email account to use the integrated webmail system.
                </flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" icon="plus" href="{{ route('email.accounts.create') }}">
                        Add First Account
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:card>
</div>
@endsection

