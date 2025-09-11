<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Email Account Details') }}
            </h2>
            <div class="flex space-x-2">
                <flux:button href="{{ route('email.accounts.edit', $account) }}" variant="outline">
                    <flux:icon.pencil class="w-4 h-4 mr-2" />
                    Edit Account
                </flux:button>
                <flux:button href="{{ route('email.accounts.index') }}" variant="outline">
                    <flux:icon.arrow-left class="w-4 h-4 mr-2" />
                    Back to Accounts
                </flux:button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Account Overview -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center space-x-3 mb-4">
                                <flux:icon.envelope class="w-8 h-8 text-blue-500" />
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        {{ $account->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $account->email_address }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Status</span>
                                    @if($account->is_active)
                                        <flux:badge color="green">Active</flux:badge>
                                    @else
                                        <flux:badge color="red">Inactive</flux:badge>
                                    @endif
                                </div>

                                @if($account->is_default)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Default</span>
                                        <flux:badge color="blue">Yes</flux:badge>
                                    </div>
                                @endif

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Provider</span>
                                    <flux:badge color="gray">{{ ucfirst($account->provider) }}</flux:badge>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Folders</span>
                                    <span class="text-sm font-medium">{{ $account->folders->count() }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Messages</span>
                                    <span class="text-sm font-medium">{{ $account->messages()->count() }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Last Synced</span>
                                    <span class="text-sm font-medium">
                                        {{ $account->last_synced_at ? $account->last_synced_at->diffForHumans() : 'Never' }}
                                    </span>
                                </div>
                            </div>

                            @if($account->sync_error)
                                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                    <div class="flex items-start">
                                        <flux:icon.exclamation-triangle class="w-5 h-5 text-red-500 mt-0.5 mr-2" />
                                        <div>
                                            <p class="text-sm font-medium text-red-800 dark:text-red-200">Sync Error</p>
                                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $account->sync_error }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Quick Actions -->
                            <div class="mt-6 space-y-2">
                                <flux:button size="sm" variant="outline" class="w-full justify-start" wire:click="testConnection">
                                    <flux:icon.wifi class="w-4 h-4 mr-2" />
                                    Test Connection
                                </flux:button>

                                <flux:button size="sm" variant="outline" class="w-full justify-start" wire:click="syncAccount">
                                    <flux:icon.arrow-path class="w-4 h-4 mr-2" />
                                    Sync Now
                                </flux:button>

                                @if(!$account->is_default)
                                    <flux:button size="sm" variant="outline" class="w-full justify-start" wire:click="setDefault">
                                        <flux:icon.star class="w-4 h-4 mr-2" />
                                        Set as Default
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Server Settings -->
                <div class="lg:col-span-2">
                    <div class="space-y-6">
                        <!-- IMAP Settings -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">IMAP Settings (Incoming)</h4>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->imap_host }}:{{ $account->imap_port }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Encryption</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($account->imap_encryption) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->imap_username }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SSL Validation</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->imap_validate_cert ? 'Enabled' : 'Disabled' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SMTP Settings -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">SMTP Settings (Outgoing)</h4>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->smtp_host }}:{{ $account->smtp_port }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Encryption</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($account->smtp_encryption) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->smtp_username }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Settings -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Advanced Settings</h4>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sync Interval</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->sync_interval_minutes }} minutes</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Auto-create Tickets</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->auto_create_tickets ? 'Enabled' : 'Disabled' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Auto-log Communications</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->auto_log_communications ? 'Enabled' : 'Disabled' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Created</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $account->created_at->format('M j, Y g:i A') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        @if($account->messages()->exists())
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Recent Messages</h4>
                                </div>
                                <div class="p-6">
                                    <div class="space-y-3">
                                        @foreach($account->messages()->latest()->limit(5)->get() as $message)
                                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                        {{ $message->subject ?: '(No Subject)' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        From: {{ $message->from_name ?: $message->from_email }}
                                                    </p>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $message->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-4">
                                        <flux:button href="{{ route('email.inbox.index', ['account_id' => $account->id]) }}" variant="outline" size="sm">
                                            View All Messages
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>


</x-app-layout></content>
</xai:function_call"> 

<xai:function_call name="write">
<parameter name="filePath">resources/views/email/compose/index.blade.php