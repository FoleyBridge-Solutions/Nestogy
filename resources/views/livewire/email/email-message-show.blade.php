<div class="space-y-6">
    {{-- Header --}}
    <flux:card>
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold">{{ $message->subject }}</h1>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                        <span><strong>From:</strong> {{ $message->from_name }} &lt;{{ $message->from_address }}&gt;</span>
                        <flux:separator vertical class="h-4" />
                        <span><strong>To:</strong> {{ implode(', ', $message->to_addresses ?? []) }}</span>
                        <flux:separator vertical class="h-4" />
                        <span><strong>Date:</strong> {{ $message->sent_at?->format('M d, Y g:i A') }}</span>
                    </div>
                    @if($message->cc_addresses)
                        <div class="mt-1 text-sm text-gray-600">
                            <strong>CC:</strong> {{ implode(', ', $message->cc_addresses) }}
                        </div>
                    @endif
                </div>
                
                <div class="flex items-center gap-2">
                    <flux:button variant="ghost" size="sm" wire:click="toggleFlag">
                        <flux:icon.flag class="{{ $message->is_flagged ? 'text-yellow-500' : 'text-gray-400' }}" />
                    </flux:button>
                    
                    @if(!$message->ticket_id)
                        <flux:button variant="outline" size="sm" wire:click="createTicketFromEmail">
                            <flux:icon.ticket class="mr-2" />
                            Create Ticket
                        </flux:button>
                    @else
                        <flux:button variant="ghost" size="sm" href="{{ route('tickets.show', $message->ticket) }}">
                            <flux:icon.ticket class="mr-2" />
                            View Ticket
                        </flux:button>
                    @endif
                    
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm">
                            <flux:icon.ellipsis-horizontal />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="logCommunication">
                                <flux:icon.clipboard-document class="mr-2" />
                                Log Communication
                            </flux:menu.item>
                            <flux:menu.item href="{{ route('email.reply', $message) }}">
                                <flux:icon.arrow-uturn-left class="mr-2" />
                                Reply
                            </flux:menu.item>
                            <flux:menu.item href="{{ route('email.forward', $message) }}">
                                <flux:icon.arrow-right class="mr-2" />
                                Forward
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

    {{-- Email Body --}}
    <flux:card>
        <div class="p-6">
            <div class="prose max-w-none">
                @if($message->body_html)
                    {!! $message->body_html !!}
                @else
                    <pre class="whitespace-pre-wrap font-sans">{{ $message->body_text }}</pre>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- Attachments --}}
    @if($message->attachments->count() > 0)
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Attachments ({{ $message->attachments->count() }})</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($message->attachments as $attachment)
                        <div class="flex items-center gap-3 p-3 border rounded-lg">
                            <flux:icon.document class="text-gray-400" />
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $attachment->filename }}</div>
                                <div class="text-sm text-gray-500">{{ number_format($attachment->size / 1024, 1) }} KB</div>
                            </div>
                            <flux:button variant="ghost" size="xs" href="{{ route('email.attachment.download', $attachment) }}">
                                <flux:icon.arrow-down-tray />
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            </div>
        </flux:card>
    @endif
</div>
