@extends('layouts.app')

@section('title', 'Add Email Account')

@section('content')
@php
    $sidebarContext = 'email';
@endphp

<div class="container-fluid h-full flex flex-col">
    <!-- Header -->
    <flux:card class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Add Email Account</flux:heading>
                <flux:text size="sm">Configure a new email account for the integrated webmail system</flux:text>
            </div>
            <flux:button variant="ghost" size="sm" href="{{ route('email.accounts.index') }}">
                <flux:icon.arrow-left class="w-4 h-4 mr-2" />
                Back to Accounts
            </flux:button>
        </div>
    </flux:card>

    <!-- Form -->
    <div class="flex-1 overflow-y-auto">
        <form method="POST" action="{{ route('email.accounts.store') }}" id="email-account-form" class="max-w-4xl mx-auto">
            @csrf
                    <form method="POST" action="{{ route('email.accounts.store') }}" id="email-account-form">
                        @csrf

            <!-- Account Details -->
            <flux:card class="mb-6">
                <flux:heading size="md" class="mb-4">Account Details</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label for="name">Account Name *</flux:label>
                                    <flux:input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="e.g., Work Email, Personal Gmail" required />
                                    <flux:error name="name" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="email_address">Email Address *</flux:label>
                                    <flux:input type="email" name="email_address" id="email_address" value="{{ old('email_address') }}" placeholder="your@email.com" required />
                                    <flux:error name="email_address" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="provider">Email Provider *</flux:label>
                                    <flux:select name="provider" id="provider" required>
                                        <option value="">Select Provider</option>
                                        @foreach($providers as $key => $provider)
                                            <option value="{{ $key }}" {{ old('provider') === $key ? 'selected' : '' }}>
                                                {{ $provider['name'] }}
                                            </option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="provider" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="is_default">Set as Default Account</flux:label>
                                    <flux:checkbox name="is_default" id="is_default" {{ old('is_default') ? 'checked' : '' }} />
                                    <flux:description>
                                        This will be your default account for sending emails
                                    </flux:description>
                                </flux:field>
                             </div>
            </flux:card>

            <!-- IMAP Settings -->
            <flux:card class="mb-6">
                <flux:heading size="md" class="mb-4">IMAP Settings (Incoming)</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label for="imap_host">IMAP Server *</flux:label>
                                    <flux:input type="text" name="imap_host" id="imap_host" value="{{ old('imap_host') }}" placeholder="imap.gmail.com" required />
                                    <flux:error name="imap_host" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="imap_port">IMAP Port *</flux:label>
                                    <flux:input type="number" name="imap_port" id="imap_port" value="{{ old('imap_port', 993) }}" min="1" max="65535" required />
                                    <flux:error name="imap_port" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="imap_encryption">IMAP Encryption *</flux:label>
                                    <flux:select name="imap_encryption" id="imap_encryption" required>
                                        <option value="ssl" {{ old('imap_encryption', 'ssl') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="tls" {{ old('imap_encryption', 'ssl') === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="none" {{ old('imap_encryption', 'ssl') === 'none' ? 'selected' : '' }}>None</option>
                                    </flux:select>
                                    <flux:error name="imap_encryption" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="imap_validate_cert">Validate SSL Certificate</flux:label>
                                    <flux:checkbox name="imap_validate_cert" id="imap_validate_cert" {{ old('imap_validate_cert', true) ? 'checked' : '' }} />
                                    <flux:description>
                                        Uncheck only for self-signed certificates
                                    </flux:description>
                                </flux:field>

                                <flux:field>
                                    <flux:label for="imap_username">IMAP Username *</flux:label>
                                    <flux:input type="text" name="imap_username" id="imap_username" value="{{ old('imap_username') }}" placeholder="Usually your email address" required />
                                    <flux:error name="imap_username" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="imap_password">IMAP Password *</flux:label>
                                    <flux:input type="password" name="imap_password" id="imap_password" placeholder="Your email password or app password" required />
                                    <flux:error name="imap_password" />
                                </flux:field>
                             </div>
            </flux:card>

            <!-- SMTP Settings -->
            <flux:card class="mb-6">
                <flux:heading size="md" class="mb-4">SMTP Settings (Outgoing)</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label for="smtp_host">SMTP Server *</flux:label>
                                    <flux:input type="text" name="smtp_host" id="smtp_host" value="{{ old('smtp_host') }}" placeholder="smtp.gmail.com" required />
                                    <flux:error name="smtp_host" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="smtp_port">SMTP Port *</flux:label>
                                    <flux:input type="number" name="smtp_port" id="smtp_port" value="{{ old('smtp_port', 587) }}" min="1" max="65535" required />
                                    <flux:error name="smtp_port" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="smtp_encryption">SMTP Encryption *</flux:label>
                                    <flux:select name="smtp_encryption" id="smtp_encryption" required>
                                        <option value="ssl" {{ old('smtp_encryption', 'tls') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="tls" {{ old('smtp_encryption', 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="none" {{ old('smtp_encryption', 'tls') === 'none' ? 'selected' : '' }}>None</option>
                                    </flux:select>
                                    <flux:error name="smtp_encryption" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="smtp_username">SMTP Username *</flux:label>
                                    <flux:input type="text" name="smtp_username" id="smtp_username" value="{{ old('smtp_username') }}" placeholder="Usually your email address" required />
                                    <flux:error name="smtp_username" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="smtp_password">SMTP Password *</flux:label>
                                    <flux:input type="password" name="smtp_password" id="smtp_password" placeholder="Your email password or app password" required />
                                    <flux:error name="smtp_password" />
                                </flux:field>
                             </div>
            </flux:card>

            <!-- Advanced Settings -->
            <flux:card class="mb-6">
                <flux:heading size="md" class="mb-4">Advanced Settings</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label for="sync_interval_minutes">Sync Interval (minutes)</flux:label>
                                    <flux:input type="number" name="sync_interval_minutes" id="sync_interval_minutes" value="{{ old('sync_interval_minutes', 5) }}" min="1" max="1440" />
                                    <flux:description>
                                        How often to check for new emails (1-1440 minutes)
                                    </flux:description>
                                    <flux:error name="sync_interval_minutes" />
                                </flux:field>

                                <flux:field>
                                    <flux:label for="auto_create_tickets">Auto-create Tickets from Emails</flux:label>
                                    <flux:checkbox name="auto_create_tickets" id="auto_create_tickets" {{ old('auto_create_tickets') ? 'checked' : '' }} />
                                    <flux:description>
                                        Automatically create support tickets from incoming emails
                                    </flux:description>
                                </flux:field>

                                <flux:field>
                                    <flux:label for="auto_log_communications">Auto-log Communications</flux:label>
                                    <flux:checkbox name="auto_log_communications" id="auto_log_communications" {{ old('auto_log_communications', true) ? 'checked' : '' }} />
                                    <flux:description>
                                        Automatically log email communications in client records
                                    </flux:description>
                                </flux:field>
                             </div>
            </flux:card>

            <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" href="{{ route('email.accounts.index') }}">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" id="submit-btn">
                        <flux:icon.plus class="w-4 h-4 mr-2" />
                        Add Email Account
                    </flux:button>
                </div>
            </flux:card>
        </form>
    </div>
</div>

@endsection

            // Fill IMAP settings
            document.getElementById('imap_host').value = config.imap_host;
            document.getElementById('imap_port').value = config.imap_port;
            document.getElementById('imap_encryption').value = config.imap_encryption;

            // Fill SMTP settings
            document.getElementById('smtp_host').value = config.smtp_host;
            document.getElementById('smtp_port').value = config.smtp_port;
            document.getElementById('smtp_encryption').value = config.smtp_encryption;

            // Auto-fill username fields if email is provided
            const emailField = document.getElementById('email_address');
            if (emailField.value) {
                document.getElementById('imap_username').value = emailField.value;
                document.getElementById('smtp_username').value = emailField.value;
            }
        }
    });

    // Auto-fill username when email changes
    document.getElementById('email_address').addEventListener('input', function() {
        const email = this.value;
        if (email && !document.getElementById('imap_username').value) {
            document.getElementById('imap_username').value = email;
            document.getElementById('smtp_username').value = email;
        }
    });

    // Form submission with loading state
    document.getElementById('email-account-form').addEventListener('submit', function() {
        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>Adding Account...';
    });
</script>
@endpush</content>
</xai:function_call"> 

<xai:function_call name="write">
<parameter name="filePath">resources/views/email/accounts/edit.blade.php