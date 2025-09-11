<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Create Email Signature') }}
            </h2>
            <flux:button href="{{ route('email.signatures.index') }}" variant="outline">
                <flux:icon.arrow-left class="w-4 h-4 mr-2" />
                Back to Signatures
            </flux:button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('email.signatures.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <flux:field>
                                <flux:label for="name">Signature Name *</flux:label>
                                <flux:input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="e.g., Professional, Casual" required />
                                <flux:error name="name" />
                            </flux:field>

                            <flux:field>
                                <flux:label for="email_account_id">Email Account</flux:label>
                                <flux:select name="email_account_id" id="email_account_id">
                                    <option value="">Global (All Accounts)</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ old('email_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} ({{ $account->email_address }})
                                        </option>
                                    @endforeach
                                </flux:select>
                                <flux:description>
                                    Leave empty to use this signature for all email accounts
                                </flux:description>
                                <flux:error name="email_account_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label for="is_default">Set as Default</flux:label>
                                <flux:checkbox name="is_default" id="is_default" {{ old('is_default') ? 'checked' : '' }} />
                                <flux:description>
                                    This will be the default signature for new emails
                                </flux:description>
                            </flux:field>

                            <flux:field>
                                <flux:label for="auto_append_replies">Auto-append to Replies</flux:label>
                                <flux:checkbox name="auto_append_replies" id="auto_append_replies" {{ old('auto_append_replies', true) ? 'checked' : '' }} />
                                <flux:description>
                                    Automatically add this signature to email replies
                                </flux:description>
                            </flux:field>

                            <flux:field>
                                <flux:label for="auto_append_forwards">Auto-append to Forwards</flux:label>
                                <flux:checkbox name="auto_append_forwards" id="auto_append_forwards" {{ old('auto_append_forwards', true) ? 'checked' : '' }} />
                                <flux:description>
                                    Automatically add this signature to forwarded emails
                                </flux:description>
                            </flux:field>
                        </div>

                        <flux:field>
                            <flux:label for="content_html">Signature Content (HTML) *</flux:label>
                            <textarea
                                name="content_html"
                                id="content_html"
                                rows="12"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Enter your email signature here. You can use HTML tags for formatting."
                                required
                            >{{ old('content_html') }}</textarea>
                            <flux:description>
                                Use HTML tags for formatting. Common tags: &lt;br&gt; for line breaks, &lt;strong&gt; for bold, &lt;em&gt; for italic, &lt;a href="..."&gt; for links.
                            </flux:description>
                            <flux:error name="content_html" />
                        </flux:field>

                        <flux:field>
                            <flux:label for="content_text">Signature Content (Plain Text)</flux:label>
                            <textarea
                                name="content_text"
                                id="content_text"
                                rows="8"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Plain text version of your signature (optional)"
                            >{{ old('content_text') }}</textarea>
                            <flux:description>
                                Plain text version for email clients that don't support HTML.
                            </flux:description>
                            <flux:error name="content_text" />
                        </flux:field>

                        <!-- Preview Section -->
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Preview</h4>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 min-h-[100px]">
                                <div id="signature-preview" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                    {!! nl2br(e(old('content_html'))) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-4 mt-6">
                            <flux:button href="{{ route('email.signatures.index') }}" variant="outline">
                                Cancel
                            </flux:button>
                            <flux:button type="submit">
                                <flux:icon.plus class="w-4 h-4 mr-2" />
                                Create Signature
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection