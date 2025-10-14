@extends('layouts.app')

@section('title', 'Compose Email')

@section('content')
@php
    $sidebarContext = 'email';
@endphp

<div class="container-fluid h-full flex flex-col">
    <!-- Header -->
    <flux:card class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Compose Email</flux:heading>
                <flux:text size="sm">Send a new email message</flux:text>
            </div>
            <flux:button variant="ghost" size="sm" href="{{ route('email.inbox.index') }}">
                <flux:icon.arrow-left class="w-4 h-4 mr-2" />
                Back to Inbox
            </flux:button>
        </div>
    </flux:card>

    <!-- Form -->
    <div class="flex-1 overflow-y-auto">
        <form method="POST" action="{{ route('email.compose.store') }}" enctype="multipart/form-data" id="compose-form" class="max-w-6xl mx-auto">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Main Compose Area -->
                <div class="lg:col-span-3">
                    <flux:card class="h-full flex flex-col">
                                <!-- Account Selector -->
                                <div class="mb-6">
                                    <flux:field>
                                        <flux:label for="account_id">From *</flux:label>
                                        <flux:select name="account_id" id="account_id" required>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" {{ $selectedAccount->id === $account->id ? 'selected' : '' }}>
                                                    {{ $account->name }} ({{ $account->email_address }})
                                                </option>
                                            @endforeach
                                        </flux:select>
                                        <flux:error name="account_id" />
                                    </flux:field>
                                </div>

                                <!-- Recipients -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <flux:field>
                                        <flux:label for="to">To *</flux:label>
                                        <flux:input type="text" name="to" id="to" value="{{ $prefill['to'] }}" placeholder="recipient@example.com" required />
                                        <flux:error name="to" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label for="cc">CC</flux:label>
                                        <flux:input type="text" name="cc" id="cc" value="{{ $prefill['cc'] }}" placeholder="cc@example.com" />
                                        <flux:error name="cc" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label for="bcc">BCC</flux:label>
                                        <flux:input type="text" name="bcc" id="bcc" value="{{ $prefill['bcc'] }}" placeholder="bcc@example.com" />
                                        <flux:error name="bcc" />
                                    </flux:field>
                                </div>

                                <!-- Subject -->
                                <div class="mb-6">
                                    <flux:field>
                                        <flux:label for="subject">Subject *</flux:label>
                                        <flux:input type="text" name="subject" id="subject" value="{{ $prefill['subject'] }}" placeholder="Email subject" required />
                                        <flux:error name="subject" />
                                    </flux:field>
                                </div>

                                <!-- Message Body -->
                                <div class="mb-6">
                                    <flux:field>
                                        <flux:label for="body">Message *</flux:label>
                                        <textarea
                                            name="body"
                                            id="body"
                                            rows="15"
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="Compose your message..."
                                            required
                                        >{{ $prefill['body'] }}</textarea>
                                        <flux:error name="body" />
                                    </flux:field>
                                </div>

                                <!-- Attachments -->
                                <div class="mb-6">
                                    <flux:field>
                                        <flux:label for="attachments">Attachments</flux:label>
                                        <input
                                            type="file"
                                            name="attachments[]"
                                            id="attachments"
                                            multiple
                                            class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        />
                                        <flux:description>
                                            Select multiple files to attach (max 10MB per file)
                                        </flux:description>
                                        <flux:error name="attachments" />
                                    </flux:field>
                                </div>

                                <!-- Signature Selector -->
                                @if($signatures->isNotEmpty())
                                    <div class="mb-6">
                                        <flux:field>
                                            <flux:label for="signature_id">Email Signature</flux:label>
                                            <flux:select name="signature_id" id="signature_id">
                                                <option value="">No signature</option>
                                                @foreach($signatures as $signature)
                                                    <option value="{{ $signature->id }}" {{ $signature->is_default ? 'selected' : '' }}>
                                                        {{ $signature->name }}
                                                        @if($signature->is_default) (Default) @endif
                                                        @if($signature->email_account_id) (Account Specific) @else (Global) @endif
                                                    </option>
                                                @endforeach
                                            </flux:select>
                                            <flux:description>
                                                Choose a signature to append to your email
                                            </flux:description>
                                            <flux:error name="signature_id" />
                                        </flux:field>
                                    </div>
                                @endif

                                <!-- Action Buttons -->
                                <div class="flex justify-between items-center">
                                    <div class="flex space-x-2">
                                        <flux:checkbox name="save_as_draft" id="save_as_draft" />
                                        <flux:label for="save_as_draft" class="text-sm">Save as Draft</flux:label>
                                    </div>

                                    <div class="flex space-x-2">
                                        <flux:button type="button" variant="outline" wire:click="saveDraft">
                                            <flux:icon.document class="w-4 h-4 mr-2" />
                                            Save Draft
                                        </flux:button>
                                        <flux:button type="submit" id="send-btn">
                                            <flux:icon.paper-airplane class="w-4 h-4 mr-2" />
                                            Send Email
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Quick Actions -->
                    <flux:card>
                        <flux:heading size="sm" class="mb-3">Quick Actions</flux:heading>
                                    <div class="space-y-3">
                                        <flux:button href="{{ route('email.compose.index') }}" variant="outline" class="w-full justify-start">
                                            <flux:icon.plus class="w-4 h-4 mr-2" />
                                            New Email
                                        </flux:button>
                                        <flux:button href="{{ route('email.inbox.index') }}" variant="outline" class="w-full justify-start">
                                            <flux:icon.inbox class="w-4 h-4 mr-2" />
                                            Inbox
                                        </flux:button>
                                        <flux:button href="{{ route('email.accounts.index') }}" variant="outline" class="w-full justify-start">
                                            <flux:icon.cog class="w-4 h-4 mr-2" />
                                            Accounts
                                        </flux:button>
                                        <flux:button href="{{ route('email.signatures.index') }}" variant="outline" class="w-full justify-start">
                                            <flux:icon.pencil-square class="w-4 h-4 mr-2" />
                                            Signatures
                                        </flux:button>
                                    </div>
                                </div>
                            </div>

                    <!-- Email Templates -->
                    <flux:card>
                        <flux:heading size="sm" class="mb-3">Templates</flux:heading>
                                    <div class="space-y-2">
                                        <flux:button variant="outline" size="sm" class="w-full justify-start" wire:click="loadTemplate('greeting')">
                                            Greeting
                                        </flux:button>
                                        <flux:button variant="outline" size="sm" class="w-full justify-start" wire:click="loadTemplate('follow-up')">
                                            Follow-up
                                        </flux:button>
                                        <flux:button variant="outline" size="sm" class="w-full justify-start" wire:click="loadTemplate('meeting')">
                                            Meeting Request
                                        </flux:button>
                                        <flux:button variant="outline" size="sm" class="w-full justify-start" wire:click="loadTemplate('support')">
                                            Support Response
                                        </flux:button>
                                    </div>
                                </div>
                            </div>

                    <!-- Tips -->
                    <flux:card variant="outline">
                        <div class="flex gap-3">
                            <flux:icon.information-circle class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" />
                            <div>
                                <flux:heading size="xs" class="mb-2">Tips</flux:heading>
                                <ul class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                                    <li>• Use CC for additional recipients</li>
                                    <li>• BCC to send copies privately</li>
                                    <li>• Save as draft to continue later</li>
                                    <li>• Signatures are added automatically</li>
                                </ul>
                            </div>
                        </div>
                    </flux:card>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection