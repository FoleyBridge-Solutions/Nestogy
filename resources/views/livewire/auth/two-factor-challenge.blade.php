<div>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        @if ($showingRecoveryForm)
            Please confirm access to your account by entering one of your emergency recovery codes.
        @else
            Please confirm access to your account by entering the authentication code provided by your authenticator application.
        @endif
    </div>

    <form wire:submit="challenge">
        @csrf

        @if ($showingRecoveryForm)
            <div class="space-y-6">
                <flux:input 
                    wire:model="recovery_code" 
                    type="text" 
                    label="Recovery Code"
                    required 
                    autofocus 
                    autocomplete="one-time-code"
                />
            </div>
        @else
            <div class="space-y-6">
                <flux:input 
                    wire:model="code" 
                    type="text" 
                    label="Code"
                    inputmode="numeric"
                    required 
                    autofocus 
                    autocomplete="one-time-code"
                />
            </div>
        @endif

        <div class="flex items-center justify-end mt-6">
            <flux:button type="button" variant="ghost" wire:click="toggleRecoveryForm">
                @if ($showingRecoveryForm)
                    Use an authentication code
                @else
                    Use a recovery code
                @endif
            </flux:button>

            <flux:button type="submit" variant="primary" class="ml-4">Log in</flux:button>
        </div>
    </form>
</div>
