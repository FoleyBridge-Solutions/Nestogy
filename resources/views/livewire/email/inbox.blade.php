<div class="container-fluid h-full flex flex-col" wire:key="email-inbox">
    <flux:card class="mb-3">
        <div class="flex items-center justify-between mb-3">
            <div>
                <flux:heading>Email Inbox</flux:heading>
                @if($this->selectedAccount())
                    <flux:text size="sm">{{ $this->selectedAccount()->name }} ({{ $this->selectedAccount()->email_address }})</flux:text>
                @endif
            </div>
            <div class="flex gap-2">
                <flux:button variant="subtle" size="sm" icon="pencil" href="{{ route('email.compose.index') }}">Compose</flux:button>
                @if($this->selectedAccount())
                    <flux:button variant="ghost" size="sm" icon="arrow-path" wire:click="refreshInbox">Refresh</flux:button>
                @endif
            </div>
        </div>

        @if($this->selectedAccount())
            <div class="flex items-center gap-2">
                <flux:input wire:model.debounce.400ms="search" placeholder="Search messages..." icon="magnifying-glass" size="sm" class="flex-1 max-w-md" />

                <flux:dropdown>
                    <flux:button icon="adjustments-horizontal" icon:variant="micro" icon:class="text-zinc-400" icon-trailing="chevron-down" icon-trailing:variant="micro" icon-trailing:class="text-zinc-400" size="sm">Filters</flux:button>
                    <flux:popover class="flex flex-col gap-4 max-w-[22rem]">
                        <flux:radio.group wire:model="status" label="Status" label:class="text-zinc-500 dark:text-zinc-400">
                            <flux:radio value="" label="All" checked />
                            <flux:radio value="unread" label="Unread" />
                            <flux:radio value="read" label="Read" />
                            <flux:radio value="flagged" label="Flagged" />
                            <flux:radio value="attachments" label="Has attachments" />
                        </flux:radio.group>
                        <flux:separator variant="subtle" />
                        <div class="grid grid-cols-2 gap-3">
                            <flux:field>
                                <flux:label>From date</flux:label>
                                <flux:input type="date" wire:model="fromDate" size="sm" />
                            </flux:field>
                            <flux:field>
                                <flux:label>To date</flux:label>
                                <flux:input type="date" wire:model="toDate" size="sm" />
                            </flux:field>
                        </div>
                        <flux:field>
                            <flux:label>Sender</flux:label>
                            <flux:input wire:model="sender" size="sm" placeholder="[email protected]" />
                        </flux:field>
                        <flux:separator variant="subtle" />
                        <div class="flex justify-between">
                            <flux:button variant="subtle" size="sm" class="justify-start -m-2 px-2!" wire:click="$set('status','');$set('fromDate','');$set('toDate','');$set('sender','')">Clear</flux:button>
                            <flux:button size="sm" icon="check">Apply</flux:button>
                        </div>
                    </flux:popover>
                </flux:dropdown>
            </div>
        @endif
    </flux:card>

    @if($this->accounts()->isEmpty())
        <flux:card class="flex-1"><div class="text-center py-12">
            <flux:icon.inbox class="mx-auto h-12 w-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">No email accounts found</flux:heading>
            <flux:text class="mt-2">Add an email account to start using the webmail interface.</flux:text>
            <div class="mt-6"><flux:button variant="primary" icon="plus" href="{{ route('email.accounts.create') }}">Add Email Account</flux:button></div>
        </div></flux:card>
    @else
        <div class="flex-1 grid grid-cols-12 gap-4">
            <div class="col-span-3 space-y-4">
                <flux:card>
                    <flux:heading size="sm" class="mb-3">Accounts</flux:heading>
                    <div class="space-y-2">
                        @foreach($this->accounts() as $account)
                            <div class="flex items-center justify-between p-2 rounded-lg {{ $account->id === $this->accountId ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                                <div class="flex items-center gap-2 min-w-0">
                                    <flux:avatar size="xs" name="{{ $account->name }}" color="auto" color:seed="{{ $account->id }}" />
                                    <button type="button" class="text-left min-w-0" wire:click="$set('accountId', {{ $account->id }})">
                                        <div class="text-sm font-medium truncate {{ $account->id === $this->accountId ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ $account->name }}</div>
                                        <div class="text-xs text-zinc-500 truncate">{{ $account->email_address }}</div>
                                    </button>
                                </div>
                                @if($account->is_default)
                                    <flux:badge size="xs" color="green">Default</flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </flux:card>

                <flux:card>
                    <flux:heading size="sm" class="mb-3">Folders</flux:heading>
                    <div class="space-y-1">
                        @foreach($this->folderStats() as $folder)
                            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                <button type="button" class="flex items-center space-x-2 text-sm flex-1 min-w-0 {{ $this->selectedFolder() && $this->selectedFolder()->id === $folder['id'] ? 'text-blue-600 dark:text-blue-400 font-medium' : 'text-zinc-700 dark:text-zinc-300' }}" wire:click="$set('folderId', {{ $folder['id'] }})">
                                    <flux:icon name="{{ $folder['icon'] }}" class="w-4 h-4 flex-shrink-0" />
                                    <span class="truncate">{{ $folder['name'] }}</span>
                                </button>
                                @if($folder['unread_count'] > 0)
                                    <flux:badge size="xs" color="blue">{{ $folder['unread_count'] }}</flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            </div>

            <div class="col-span-5">
                <flux:card class="h-full flex flex-col">
                    @if($this->messages()->count() > 0)
                        <div class="border-b border-zinc-200 dark:border-zinc-700 p-2 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <flux:checkbox :checked="$selectPage" wire:click="toggleSelectPage" />
                                <flux:dropdown>
                                    <flux:button size="xs" variant="subtle" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="bulkMarkRead" icon="envelope-open">Mark as read</flux:menu.item>
                                        <flux:menu.item wire:click="bulkMarkUnread" icon="envelope">Mark as unread</flux:menu.item>
                                        <flux:menu.item wire:click="bulkFlag" icon="flag">Flag</flux:menu.item>
                                        <flux:menu.item wire:click="bulkUnflag" icon="flag">Unflag</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="bulkDelete" icon="trash" variant="danger">Delete</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-zinc-500">Sorted by</span>
                                <flux:dropdown>
                                    <flux:button size="xs" variant="ghost" icon:trailing="chevron-down">{{ Str::headline($sortBy) }}</flux:button>
                                    <flux:menu>
                                        <flux:menu.radio.group>
                                            <flux:menu.radio wire:click="sort('sent_at')" @class(['font-medium' => $sortBy==='sent_at'])>Date</flux:menu.radio>
                                            <flux:menu.radio wire:click="sort('from_name')" @class(['font-medium' => $sortBy==='from_name'])>Sender</flux:menu.radio>
                                            <flux:menu.radio wire:click="sort('subject')" @class(['font-medium' => $sortBy==='subject'])>Subject</flux:menu.radio>
                                        </flux:menu.radio.group>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto">
                            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($this->messages() as $message)
                                    <div class="p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer {{ !$message->is_read ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}" wire:click="selectMessage({{ $message->id }})">
                                        <div class="flex items-start gap-3">
                                            <flux:checkbox wire:click.stop="toggleSelect({{ $message->id }})" :checked="in_array((string) $message->id, $selected)" />
                                            <flux:avatar size="sm" name="{{ $message->from_name ?: $message->from_address }}" color="auto" color:seed="{{ md5($message->from_address) }}" />
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium truncate {{ !$message->is_read ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}">{{ $message->from_name ?: $message->from_address }}</span>
                                                    @if($message->has_attachments)
                                                        <flux:icon.paper-clip class="w-3 h-3 text-zinc-400" />
                                                    @endif
                                                    @if($message->is_flagged)
                                                        <flux:icon.flag class="w-3 h-3 text-red-500" />
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm {{ !$message->is_read ? 'text-zinc-900 dark:text-zinc-100 font-medium' : 'text-zinc-600 dark:text-zinc-400' }} truncate">{{ Str::limit($message->subject, 80) }}</p>
                                                    @if($message->emailFolder && $message->emailFolder->type !== 'inbox')
                                                        <flux:badge size="xs" color="zinc">{{ $message->emailFolder->getDisplayName() }}</flux:badge>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-zinc-500 truncate mt-1">{{ Str::limit($message->preview, 100) }}</p>
                                            </div>
                                            <div class="text-xs text-zinc-500 flex-shrink-0">{{ $message->sent_at ? $message->sent_at->diffForHumans() : '' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if($this->messages()->hasPages())
                            <div class="border-t border-zinc-200 dark:border-zinc-700 p-3">{{ $this->messages()->links() }}</div>
                        @endif
                    @else
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center py-8">
                                <flux:icon.inbox class="mx-auto h-8 w-8 text-zinc-400 mb-2" />
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">No messages found.</p>
                            </div>
                        </div>
                    @endif
                </flux:card>
            </div>

            <div class="col-span-4">
                @if($this->selectedMessage())
                    <flux:card class="h-full flex flex-col">
                        <div class="border-b border-zinc-200 dark:border-zinc-700 p-4">
                            <div class="flex justify-between items-start gap-2 mb-3">
                                <flux:heading size="sm" class="flex-1 min-w-0">{{ $this->selectedMessage()->subject }}</flux:heading>
                                <div class="flex gap-1 flex-shrink-0">
                                    <flux:button size="xs" variant="ghost" icon="arrow-left" href="{{ route('email.compose.reply', $this->selectedMessage()) }}" />
                                    <flux:button size="xs" variant="ghost" icon="arrow-right" href="{{ route('email.compose.forward', $this->selectedMessage()) }}" />
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="bulkDelete" />
                                </div>
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                                <div class="flex items-center gap-2">
                                    <flux:avatar size="xs" name="{{ $this->selectedMessage()->from_name ?: $this->selectedMessage()->from_address }}" color="auto" color:seed="{{ md5($this->selectedMessage()->from_address) }}" />
                                    <span>From: {{ $this->selectedMessage()->from_name ?: $this->selectedMessage()->from_address }}</span>
                                </div>
                                @if($this->selectedMessage()->to_addresses)
                                    <div class="flex items-center gap-2">
                                        <flux:icon.users class="w-4 h-4" />
                                        <span>To: {{ implode(', ', $this->selectedMessage()->to_addresses) }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center gap-2">
                                    <flux:icon.clock class="w-4 h-4" />
                                    <span>{{ $this->selectedMessage()->sent_at ? $this->selectedMessage()->sent_at->format('F j, Y \a\t g:i A') : '' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4">
                            <flux:tab.group>
                                <flux:tab.group>
                                <flux:tabs>
                                    <flux:tab name="content">Content</flux:tab>
                                    @if($this->selectedMessage()->attachments->isNotEmpty())
                                        <flux:tab name="attachments">Attachments ({{ $this->selectedMessage()->attachments->count() }})</flux:tab>
                                    @endif
                                    <flux:tab name="headers">Headers</flux:tab>
                                </flux:tabs>
                                </flux:tab.group>

                                <flux:tab.panel name="content" class="prose dark:prose-invert max-w-none">
                                    {!! $this->selectedMessage()->body_html ?: nl2br(e($this->selectedMessage()->body_text)) !!}
                                </flux:tab.panel>

                                @if($this->selectedMessage()->attachments->isNotEmpty())
                                    <flux:tab.panel name="attachments">
                                        <div class="space-y-2">
                                            @foreach($this->selectedMessage()->attachments as $attachment)
                                                <div class="flex items-center gap-2 p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                                                    <flux:icon name="{{ $attachment->getIconClass() }}" class="w-4 h-4 text-zinc-500" />
                                                    <span class="text-sm flex-1 truncate">{{ $attachment->filename }}</span>
                                                    <span class="text-xs text-zinc-500">({{ $attachment->getFormattedSize() }})</span>
                                                    <flux:link href="{{ route('email.attachments.download', $attachment) }}" class="text-sm">Download</flux:link>
                                                </div>
                                            @endforeach
                                        </div>
                                    </flux:tab.panel>
                                @endif

                                <flux:tab.panel name="headers">
                                    <div class="text-xs text-zinc-500">
                                        <pre class="whitespace-pre-wrap">Message-ID: {{ $this->selectedMessage()->message_id }}
 From: {{ $this->selectedMessage()->from_name }} &lt;{{ $this->selectedMessage()->from_address }}&gt;
 To: {{ $this->selectedMessage()->to_addresses ? implode(', ', $this->selectedMessage()->to_addresses) : '' }}
 Date: {{ $this->selectedMessage()->sent_at }}</pre>
                                    </div>
                                </flux:tab.panel>
                            </flux:tab.group>
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
