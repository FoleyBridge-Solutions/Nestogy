@extends('layouts.app')

@extends('layouts.app')

@section('title', 'Email Inbox')

@section('content')
@php
    $sidebarContext = 'email';
@endphp

<div class="container-fluid h-full flex flex-col">
    <!-- Compact Header with Actions -->
    <flux:card class="mb-3">
        <div class="flex items-center justify-between mb-3">
             <div>
                 <flux:heading>Email Inbox</flux:heading>
                 @if($selectedAccount)
                     <flux:text size="sm">{{ $selectedAccount->name }} ({{ $selectedAccount->email_address }})</flux:text>
                      @if($selectedAccount->sync_error)
                          <flux:alert variant="danger" class="mt-2">
                              Email sync failed: {{ $selectedAccount->sync_error }}
                              <flux:link href="{{ route('email.accounts.edit', $selectedAccount) }}">Fix Account Settings</flux:link>
                          </flux:alert>
                      @endif
                 @endif
             </div>
            <div class="flex gap-2">
                <flux:button
                    variant="subtle"
                    size="sm"
                    icon="pencil"
                    href="{{ route('email.compose.index') }}"
                >
                    Compose
                </flux:button>
                @if($selectedAccount)
                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="arrow-path"
wire:click="refreshInbox"
                    >
                        Refresh
                    </flux:button>
                @endif
            </div>
        </div>

        <!-- Search Bar -->
        @if($selectedAccount)
            <form method="GET" class="flex gap-2">
                <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                @if($selectedFolder)
                    <input type="hidden" name="folder_id" value="{{ $selectedFolder->id }}">
                @endif
                <flux:input
                    name="search"
                    placeholder="Search messages..."
                    icon="magnifying-glass"
                    size="sm"
                    class="flex-1 max-w-md"
                    value="{{ request('search') }}"
                />
                <flux:button type="submit" variant="primary" size="sm">
                    Search
                </flux:button>
                @if(request()->hasAny(['search']))
                    <flux:button
                        variant="ghost"
                        size="sm"
                        href="{{ route('email.inbox.index', ['account_id' => $selectedAccount->id] + ($selectedFolder ? ['folder_id' => $selectedFolder->id] : [])) }}"
                    >
                        Clear
                    </flux:button>
                @endif
            </form>
        @endif
    </flux:card>

    @if($accounts->isEmpty())
        <!-- No Accounts State -->
        <flux:card class="flex-1">
            <div class="text-center py-12">
                <flux:icon.inbox class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No email accounts found</flux:heading>
                <flux:text class="mt-2">
                    You need to add an email account to start using the webmail interface.
                </flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" icon="plus" href="{{ route('email.accounts.create') }}">
                        Add Email Account
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @else
        <div class="flex-1 grid grid-cols-12 gap-4">
            <!-- Left Sidebar - Accounts & Folders -->
            <div class="col-span-3 space-y-4">
                <!-- Account Selector -->
                <flux:card>
                    <flux:heading size="sm" class="mb-3">Accounts</flux:heading>
                    <div class="space-y-2">
                        @foreach($accounts as $account)
                            <div class="flex items-center justify-between p-2 rounded-lg {{ $account->id === $selectedAccount->id ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                                <div class="flex-1 min-w-0">
                                    <flux:link
                                        href="{{ route('email.inbox.index', ['account_id' => $account->id]) }}"
                                        class="text-sm font-medium block truncate {{ $account->id === $selectedAccount->id ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-900 dark:text-zinc-100' }}"
                                    >
                                        {{ $account->name }}
                                    </flux:link>
                                    <p class="text-xs text-zinc-500 truncate">
                                        {{ $account->email_address }}
                                    </p>
                                </div>
                                @if($account->is_default)
                                    <flux:badge size="xs" color="green">Default</flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </flux:card>

                <!-- Folders -->
                <flux:card>
                    <flux:heading size="sm" class="mb-3">Folders</flux:heading>
                    <div class="space-y-1">
                        @foreach($folderStats as $folder)
                            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                <flux:link
                                    href="{{ route('email.inbox.index', ['account_id' => $selectedAccount->id, 'folder_id' => $folder['id']]) }}"
                                    class="flex items-center space-x-2 text-sm flex-1 min-w-0 {{ $selectedFolder && $selectedFolder->id === $folder['id'] ? 'text-blue-600 dark:text-blue-400 font-medium' : 'text-zinc-700 dark:text-zinc-300' }}"
                                >
                                    <flux:icon name="{{ $folder['icon'] }}" class="w-4 h-4 flex-shrink-0" />
                                    <span class="truncate">{{ $folder['name'] }}</span>
                                </flux:link>
                                @if($folder['unread_count'] > 0)
                                    <flux:badge size="xs" color="blue">{{ $folder['unread_count'] }}</flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            </div>

            <!-- Center - Message List -->
            <div class="col-span-5">
                <flux:card class="h-full flex flex-col">
                    @if($messages->count() > 0)
                        <div class="flex-1 overflow-y-auto">
                            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($messages as $message)
                                    <div class="p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer {{ !$message->is_read ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}"
wire:click="selectMessage({{ $message->id }})">
                                        <div class="flex justify-between items-start gap-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-sm font-medium truncate {{ !$message->is_read ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}">
                                                        {{ $message->from_name ?: $message->from_address }}
                                                    </span>
                                                    @if($message->has_attachments)
                                                        <flux:icon.paper-clip class="w-3 h-3 text-zinc-400 flex-shrink-0" />
                                                    @endif
                                                    @if($message->is_flagged)
                                                        <flux:icon.flag class="w-3 h-3 text-red-500 flex-shrink-0" />
                                                    @endif
                                                </div>
                                                <p class="text-sm {{ !$message->is_read ? 'text-zinc-900 dark:text-zinc-100 font-medium' : 'text-zinc-600 dark:text-zinc-400' }} truncate">
                                                    {{ Str::limit($message->subject, 60) }}
                                                </p>
                                                <p class="text-xs text-zinc-500 truncate mt-1">
                                                    {{ Str::limit($message->preview, 80) }}
                                                </p>
                                            </div>
                                            <div class="text-xs text-zinc-500 flex-shrink-0">
                                                {{ $message->sent_at ? $message->sent_at->format('M j, g:i A') : '' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if($messages->hasPages())
                            <div class="border-t border-zinc-200 dark:border-zinc-700 p-3">
                                {{ $messages->links() }}
                            </div>
                        @endif
                    @else
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center py-8">
                                <flux:icon.inbox class="mx-auto h-8 w-8 text-zinc-400 mb-2" />
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    @if(request()->hasAny(['search']))
                                        No messages match your search criteria.
                                    @else
                                        No messages in this folder.
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif
                </flux:card>
            </div>

            <!-- Right - Message Preview -->
            <div class="col-span-4">
                @if($selectedMessage)
                    <flux:card class="h-full flex flex-col">
                        <!-- Message Header -->
                        <div class="border-b border-zinc-200 dark:border-zinc-700 p-4">
                            <div class="flex justify-between items-start gap-2 mb-3">
                                <flux:heading size="sm" class="flex-1 min-w-0">
                                    {{ $selectedMessage->subject }}
                                </flux:heading>
                                <div class="flex gap-1 flex-shrink-0">
                                    <flux:button size="xs" variant="ghost" icon="arrow-left" wire:click="replyToMessage({{ $selectedMessage->id }})" />
                                    <flux:button size="xs" variant="ghost" icon="arrow-right" wire:click="forwardMessage({{ $selectedMessage->id }})" />
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="deleteMessage({{ $selectedMessage->id }})" />
                                </div>
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                                <div class="flex items-center gap-2">
                                    <flux:icon.user class="w-4 h-4" />
                                    <span>From: {{ $selectedMessage->from_name ?: $selectedMessage->from_address }}</span>
                                </div>
                                @if($selectedMessage->to_addresses)
                                    <div class="flex items-center gap-2">
                                        <flux:icon.users class="w-4 h-4" />
                                        <span>To: {{ implode(', ', $selectedMessage->to_addresses) }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center gap-2">
                                    <flux:icon.clock class="w-4 h-4" />
                                    <span>{{ $selectedMessage->sent_at ? $selectedMessage->sent_at->format('F j, Y \a\t g:i A') : '' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Message Body -->
                        <div class="flex-1 overflow-y-auto p-4">
                            <div class="prose dark:prose-invert max-w-none">
                                {!! $selectedMessage->body_html ?: nl2br(e($selectedMessage->body_text)) !!}
                            </div>

                            @if($selectedMessage->attachments->isNotEmpty())
                                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                    <flux:heading size="xs" class="mb-2">Attachments</flux:heading>
                                    <div class="space-y-2">
                                        @foreach($selectedMessage->attachments as $attachment)
                                            <div class="flex items-center gap-2 p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                                                <flux:icon name="{{ $attachment->getIconClass() }}" class="w-4 h-4 text-zinc-500" />
                                                <span class="text-sm flex-1 truncate">{{ $attachment->filename }}</span>
                                                <span class="text-xs text-zinc-500">({{ $attachment->getFormattedSize() }})</span>
                                                <flux:link href="{{ route('email.attachments.download', $attachment) }}" class="text-sm">
                                                    Download
                                                </flux:link>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </flux:card>
                @else
                    <flux:card class="h-full flex items-center justify-center">
                        <div class="text-center">
                            <flux:icon.envelope-open class="mx-auto h-8 w-8 text-zinc-400 mb-2" />
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">Select a message to view</p>
                        </div>
                    </flux:card>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

