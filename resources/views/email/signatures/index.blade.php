<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Email Signatures') }}
            </h2>
            <flux:button href="{{ route('email.signatures.create') }}">
                <flux:icon.plus class="w-4 h-4" />
                Add Signature
            </flux:button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($signatures->isEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <flux:icon.pencil-square class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            No Email Signatures
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Create email signatures to automatically add to your outgoing emails.
                        </p>
                        <flux:button href="{{ route('email.signatures.create') }}">
                            Create Your First Signature
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="grid gap-6">
                    @foreach($signatures as $signature)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $signature->name }}
                                            </h3>
                                            @if($signature->is_default)
                                                <flux:badge color="green">Default</flux:badge>
                                            @endif
                                            @if($signature->email_account)
                                                <flux:badge color="blue">{{ $signature->email_account->name }}</flux:badge>
                                            @else
                                                <flux:badge color="gray">Global</flux:badge>
                                            @endif
                                        </div>

                                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                            @if($signature->email_account)
                                                Specific to: {{ $signature->email_account->email_address }}
                                            @else
                                                Available for all email accounts
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex space-x-2">
                                        <flux:button size="sm" href="{{ route('email.signatures.edit', $signature) }}" variant="outline">
                                            Edit
                                        </flux:button>
                                        @if(!$signature->is_default)
                                            <flux:button size="sm" variant="outline" wire:click="setDefault({{ $signature->id }})">
                                                Set Default
                                            </flux:button>
                                        @endif
                                        <flux:button size="sm" variant="danger" wire:click="deleteSignature({{ $signature->id }}, '{{ $signature->name }}')">
                                            Delete
                                        </flux:button>
                                    </div>
                                </div>

                                <!-- Signature Preview -->
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900">
                                    <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                        {!! nl2br(e($signature->content_html ?: $signature->content_text)) !!}
                                    </div>
                                </div>

                                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                    Created {{ $signature->created_at->diffForHumans() }}
                                    @if($signature->updated_at != $signature->created_at)
                                        • Updated {{ $signature->updated_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>


</x-app-layout></content>
</xai:function_call"> 

<xai:function_call name="write">
<parameter name="filePath">resources/views/email/signatures/create.blade.php